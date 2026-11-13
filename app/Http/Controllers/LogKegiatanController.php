<?php

namespace App\Http\Controllers;

use App\Exports\ApprovedLogKegiatanExport;
use App\Http\Controllers\Mahasiswa\RencanaKehadiranController;
use App\Http\Requests\LogKegiatanStep1Request;
use App\Http\Requests\LogKegiatanStep2Request;
use App\Http\Requests\LogKegiatanStep3Request;
use App\Models\LogKegiatan;
use App\Models\LogKegiatanFile;
use App\Models\MasterKegiatan;
use App\Models\Peserta;
use App\Models\Prodi;
use App\Models\User;
use App\Services\PythonAnalyticsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogKegiatanController extends Controller
{
    /**
     * Tampilkan riwayat log kegiatan mahasiswa atau seluruh log untuk admin.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $isAdmin = $user?->isAdmin() ?? false;
        $isDpp = $user?->isDpp() ?? false;
        $isReviewer = $isAdmin || $isDpp;

        $dppKodeProdi = $isDpp
            ? $this->getDppKodeProdi($user)
            : null;

        $filters = $this->validateLogFilters($request, $isReviewer);

        if ($isDpp) {
            $filters['kode_prodi'] = $dppKodeProdi;
        }

        $sort = $filters['sort'] ?? 'tanggal_kehadiran';
        $direction = $filters['direction'] ?? 'desc';

        $query = $this->filteredLogsQuery(
            $filters,
            $isReviewer,
            null,
            $dppKodeProdi
        );

        if ($sort === 'mahasiswa') {
            $query->orderBy(
                Peserta::query()
                    ->select('nama_lengkap')
                    ->whereColumn('pesertas.id', 'log_kegiatans.peserta_id'),
                $direction
            );
        } elseif ($sort === 'kegiatan') {
            $query->orderBy(
                MasterKegiatan::query()
                    ->select('nama_kegiatan')
                    ->whereColumn(
                        'master_kegiatans.id',
                        'log_kegiatans.master_kegiatan_id'
                    ),
                $direction
            );
        } else {
            $query->orderBy($sort, $direction);
        }

        $logs = $query->paginate(10)->withQueryString();

        $pendingCount = $isReviewer
            ? $this->filteredLogsQuery(
                $filters,
                true,
                'menunggu',
                $dppKodeProdi
            )->count()
            : 0;

        $masterKegiatans = MasterKegiatan::query()
            ->when(
                $isDpp,
                fn ($query) => $query->where('kode_prodi', $dppKodeProdi)
            )
            ->when(
                ! $isReviewer,
                fn ($query) => $query->where(
                    'kode_prodi',
                    $user?->peserta?->kode_prodi
                )
            )
            ->orderBy('nama_kegiatan')
            ->get([
                'id',
                'kode_kegiatan',
                'nama_kegiatan',
                'kategori',
                'kode_prodi',
            ]);

        $prodis = $isAdmin
            ? Prodi::query()->orderBy('nama_prodi')
                ->get(['kode_prodi', 'nama_prodi'])
            : ($isDpp
                ? Prodi::query()->where('kode_prodi', $dppKodeProdi)
                    ->get(['kode_prodi', 'nama_prodi'])
                : []);

        return Inertia::render('LogKegiatan/Index', [
            'logs' => $logs,
            'masterKegiatans' => $masterKegiatans,
            'prodis' => $prodis,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
                'master_kegiatan_id' => $filters['master_kegiatan_id'] ?? null,
                'kode_prodi' => $filters['kode_prodi'] ?? '',
                'tanggal' => $filters['tanggal'] ?? '',
                'sesi' => $filters['sesi'] ?? '',
                'sort' => $sort,
                'direction' => $direction,
            ],
            'isAdmin' => $isAdmin,
            'isDpp' => $isDpp,
            'isReviewer' => $isReviewer,
            'sessions' => RencanaKehadiranController::VALID_SESSIONS,
            'pendingCount' => $pendingCount,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $user = $request->user();

        abort_unless(
            $user && ($user->isAdmin() || $user->isDpp()),
            403
        );

        $isDpp = $user->isDpp();
        $dppKodeProdi = $isDpp
            ? $this->getDppKodeProdi($user)
            : null;

        $filters = $this->validateLogFilters($request, true);

        if ($isDpp) {
            $filters['kode_prodi'] = $dppKodeProdi;
        }

        $query = $this->filteredLogsQuery(
            $filters,
            true,
            'disetujui',
            $dppKodeProdi
        )
            ->orderByDesc('tanggal_kehadiran')
            ->orderByDesc('id');

        return Excel::download(
            new ApprovedLogKegiatanExport($query),
            'laporan-kegiatan-disetujui-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function exportPythonSummary(Request $request, PythonAnalyticsService $python): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user && ($user->isAdmin() || $user->isDpp()), 403);

        $isDpp = $user->isDpp();
        $dppKodeProdi = $isDpp ? $this->getDppKodeProdi($user) : null;
        $filters = $this->validateLogFilters($request, true);

        if ($isDpp) {
            $filters['kode_prodi'] = $dppKodeProdi;
        }

        $logs = $this->filteredLogsQuery(
            $filters,
            true,
            'disetujui',
            $dppKodeProdi
        )->get();

        $activities = [];

        foreach ($logs as $log) {
            if (! $log->peserta) {
                continue;
            }

            $activities[] = [
                'nim' => $log->peserta->nim,
                'nama' => $log->peserta->nama_lengkap,
                'durasi_jam' => $log->durasiJam(),
            ];
        }

        try {
            $summary = $python->run('rekap-jam', $activities);
        } catch (RuntimeException $exception) {
            Log::error('Python activity summary failed.', ['exception' => $exception]);
            abort(503, 'Fitur rekap Python belum tersedia. Pastikan Python terpasang di server.');
        }

        return response()->streamDownload(function () use ($summary): void {
            $output = fopen('php://output', 'w');

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['NIM', 'Nama Mahasiswa', 'Total Jam Disetujui'], ',', '"', '');

            foreach ($summary as $mahasiswa) {
                fputcsv($output, [
                    $this->safeCsvCell((string) ($mahasiswa['nim'] ?? '')),
                    $this->safeCsvCell((string) ($mahasiswa['nama'] ?? '')),
                    (float) ($mahasiswa['total_jam'] ?? 0),
                ], ',', '"', '');
            }

            fclose($output);
        }, 'rekap-jam-mahasiswa-python-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function approveAll(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user && ($user->isAdmin() || $user->isDpp()),
            403
        );

        $isDpp = $user->isDpp();
        $dppKodeProdi = $isDpp
            ? $this->getDppKodeProdi($user)
            : null;

        $filters = $this->validateLogFilters($request, true);

        if ($isDpp) {
            $filters['kode_prodi'] = $dppKodeProdi;
        }

        $approvedCount = $this->filteredLogsQuery(
            $filters,
            true,
            'menunggu',
            $dppKodeProdi
        )->update([
            'status' => 'disetujui',
            'updated_at' => now(),
        ]);

        if ($approvedCount === 0) {
            return back()->with(
                'error',
                'Tidak ada laporan menunggu yang cocok dengan filter aktif.'
            );
        }

        return back()->with(
            'success',
            "{$approvedCount} laporan kegiatan berhasil disetujui."
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validateLogFilters(Request $request, bool $isAdmin): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in($isAdmin
                ? ['menunggu', 'disetujui', 'ditolak']
                : ['draft', 'menunggu', 'disetujui', 'ditolak'])],
            'master_kegiatan_id' => ['nullable', 'integer', 'exists:master_kegiatans,id'],
            'kode_prodi' => ['nullable', 'string', 'exists:prodi,kode_prodi'],
            'tanggal' => ['nullable', 'date'],
            'sesi' => ['nullable', Rule::in(RencanaKehadiranController::VALID_SESSIONS)],
            'sort' => ['nullable', Rule::in(['mahasiswa', 'kegiatan', 'tanggal_kehadiran', 'status', 'current_step'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
    }

    private function filteredLogsQuery(
        array $filters,
        bool $isReviewer,
        ?string $forcedStatus = null,
        ?string $scopeKodeProdi = null
    ): Builder {
        $search = $filters['search'] ?? '';

        return LogKegiatan::query()
            ->with([
                'masterKegiatan',
                'peserta.user',
                'peserta.prodi',
                'rencanaKehadiran',
            ])
            ->when(! $isReviewer, function (Builder $query) {
                $query->where(
                    'peserta_id',
                    auth()->user()?->peserta?->id ?? 0
                );
            })
            ->when(
                $isReviewer,
                fn (Builder $query) => $query->whereIn(
                    'status',
                    ['menunggu', 'disetujui', 'ditolak']
                )
            )
            ->when(
                $scopeKodeProdi !== null,
                fn (Builder $query) => $this->constrainLogsToProdi(
                    $query,
                    $scopeKodeProdi
                )
            )
            ->when(
                $forcedStatus !== null,
                fn (Builder $query) => $query->where('status', $forcedStatus)
            )
            ->when(
                $forcedStatus === null && ($filters['status'] ?? '') !== '',
                fn (Builder $query) => $query->where(
                    'status',
                    $filters['status']
                )
            )
            ->when(
                $forcedStatus !== null
                && ($filters['status'] ?? '') !== ''
                && $filters['status'] !== $forcedStatus,
                fn (Builder $query) => $query->whereRaw('1 = 0')
            )
            ->when(
                ($filters['master_kegiatan_id'] ?? null) !== null,
                fn (Builder $query) => $query->where(
                    'master_kegiatan_id',
                    $filters['master_kegiatan_id']
                )
            )
            ->when(
                ($filters['kode_prodi'] ?? '') !== '',
                fn (Builder $query) => $this->constrainLogsToProdi(
                    $query,
                    $filters['kode_prodi']
                )
            )
            ->when(
                ($filters['tanggal'] ?? '') !== '',
                fn (Builder $query) => $query->whereDate(
                    'tanggal_kehadiran',
                    $filters['tanggal']
                )
            )
            ->when(
                ($filters['sesi'] ?? '') !== '',
                fn (Builder $query) => $query->where(
                    'jam_kehadiran',
                    $filters['sesi']
                )
            )
            ->when($search !== '', function (Builder $query) use (
                $search,
                $isReviewer
            ): void {
                $query->where(function (Builder $subquery) use (
                    $search,
                    $isReviewer
                ): void {
                    $subquery
                        ->where('deskripsi_kegiatan', 'like', "%{$search}%")
                        ->orWhere('deskripsi_luaran', 'like', "%{$search}%")
                        ->orWhereHas('masterKegiatan', function (
                            Builder $masterQuery
                        ) use ($search): void {
                            $masterQuery
                                ->where('nama_kegiatan', 'like', "%{$search}%")
                                ->orWhere('kode_kegiatan', 'like', "%{$search}%");
                        });

                    if ($isReviewer) {
                        $subquery->orWhereHas(
                            'peserta',
                            function (Builder $pesertaQuery) use ($search): void {
                                $pesertaQuery
                                    ->where('nama_lengkap', 'like', "%{$search}%")
                                    ->orWhere('nim', 'like', "%{$search}%");
                            }
                        );
                    }
                });
            });
    }

    /**
     * Tampilkan formulir awal stepper untuk membuat log kegiatan (Step 1).
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user?->isAdmin()) {
            return redirect()->route('log-kegiatan.index')
                ->with('error', 'Admin hanya dapat memantau data log kegiatan mahasiswa.');
        }

        if (! $user?->peserta) {
            abort(403, 'Akun Anda belum terdaftar sebagai peserta KKN.');
        }

        $peserta = $user->peserta;
        $masterKegiatans = MasterKegiatan::query()
            ->where('kode_prodi', $peserta->kode_prodi)
            ->orderBy('kode_kegiatan')
            ->get();
        $student = [
            'nama_lengkap' => $peserta?->nama_lengkap ?? $user->name,
            'nim' => $peserta?->nim ?? $user->nim,
            'kode_prodi' => $peserta?->kode_prodi,
            'email' => $user->email ?? ($user->nim ? "{$user->nim}@itenas.ac.id" : 'student@itenas.ac.id'),
        ];

        return Inertia::render('LogKegiatan/Stepper', [
            'log' => null,
            'currentStep' => 1,
            'student' => $student,
            'masterKegiatans' => $masterKegiatans,
            'prodis' => Prodi::query()->orderBy('nama_prodi')->get(['kode_prodi', 'nama_prodi']),
            'rencanaKehadirans' => $peserta->rencanaKehadirans()
                ->orderByDesc('tanggal_rencana')
                ->orderByDesc('id')
                ->get(['id', 'tanggal_rencana', 'jam_kehadiran']),
        ]);
    }

    /**
     * Simpan data berdasarkan step (1 s.d. 6) dengan dukungan simpan draft.
     */
    public function saveStep(Request $request, ?LogKegiatan $logKegiatan = null): RedirectResponse
    {
        $user = $request->user();
        $peserta = $user?->peserta;

        if (! $peserta) {
            if ($user && $user->isMahasiswa()) {
                $peserta = Peserta::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nama_lengkap' => $user->name,
                        'nim' => $user->nim ?? '152024000',
                    ]
                );
            } else {
                abort(403, 'Peserta tidak ditemukan.');
            }
        }

        $step = (int) $request->input('step', 1);
        $isDraftOnly = $request->boolean('is_draft_only') || $request->input('action') === 'draft';

        // Jika ID log dikirim via request body
        if (! $logKegiatan && $request->filled('log_id')) {
            $logKegiatan = LogKegiatan::find($request->input('log_id'));
        }

        // Jika record log belum ada, buat draft awal di step 1
        if (! $logKegiatan || ! $logKegiatan->exists) {
            $logKegiatan = LogKegiatan::create([
                'peserta_id' => $peserta->id,
                'status' => 'draft',
                'current_step' => 1,
            ]);
        } else {
            $this->authorizeOwner($user, $logKegiatan);
            $this->ensureDraft($logKegiatan);
        }

        // STEP 1: SCHEDULE
        if ($step === 1) {
            if ($request->exists('rencana_kehadiran_id')) {
                $validated = $request->validate([
                    'rencana_kehadiran_id' => [
                        'required',
                        'integer',
                        Rule::exists('rencana_kehadirans', 'id')
                            ->where(fn ($query) => $query->where('peserta_id', $peserta->id)),
                    ],
                ], [
                    'rencana_kehadiran_id.required' => 'Pilih jadwal kehadiran terlebih dahulu.',
                    'rencana_kehadiran_id.exists' => 'Jadwal kehadiran tidak ditemukan untuk akun Anda.',
                ]);

                $rencanaKehadiran = $peserta->rencanaKehadirans()
                    ->findOrFail($validated['rencana_kehadiran_id']);
                $scheduleData = [
                    'rencana_kehadiran_id' => $rencanaKehadiran->id,
                    'tanggal_kehadiran' => $rencanaKehadiran->tanggal_rencana->toDateString(),
                    'jam_mulai' => substr($rencanaKehadiran->jam_kehadiran, 0, 5),
                    'jam_selesai' => substr($rencanaKehadiran->jam_kehadiran, 8, 5),
                    'jam_kehadiran' => $rencanaKehadiran->jam_kehadiran,
                ];
            } else {
                $validated = $request->validate([
                    'tanggal_kehadiran' => ['required', 'date'],
                    'jam_mulai' => ['required', 'string', 'max:10'],
                    'jam_selesai' => ['required', 'string', 'max:10'],
                ], [
                    'tanggal_kehadiran.required' => 'Tanggal kehadiran wajib diisi.',
                    'jam_mulai.required' => 'Jam mulai wajib diisi.',
                    'jam_selesai.required' => 'Jam selesai wajib diisi.',
                ]);

                $scheduleData = [
                    'rencana_kehadiran_id' => $logKegiatan->rencana_kehadiran_id,
                    'tanggal_kehadiran' => $validated['tanggal_kehadiran'],
                    'jam_mulai' => $validated['jam_mulai'],
                    'jam_selesai' => $validated['jam_selesai'],
                    'jam_kehadiran' => "{$validated['jam_mulai']} - {$validated['jam_selesai']}",
                ];
            }

            $logKegiatan->update([
                ...$scheduleData,
                'current_step' => max($logKegiatan->current_step, 2),
            ]);

            if ($isDraftOnly) {
                return redirect()->back()->with('success', 'Draft jadwal berhasil disimpan.');
            }

            return redirect()->route('log-kegiatan.edit', [
                'log_kegiatan' => $logKegiatan,
                'step' => 2,
            ]);
        }

        // STEP 2: ACTIVITY (FACULTY & PROJECT CODE)
        if ($step === 2) {
            $validated = $request->validate([
                'master_kegiatan_id' => [
                    'required',
                    Rule::exists('master_kegiatans', 'id')
                        ->where(fn ($query) => $query->where('kode_prodi', $peserta->kode_prodi)),
                ],
            ], [
                'master_kegiatan_id.required' => 'Pilih kode aktivitas kegiatan.',
            ]);

            $logKegiatan->update([
                'master_kegiatan_id' => $validated['master_kegiatan_id'],
                'current_step' => max($logKegiatan->current_step, 3),
            ]);

            if ($isDraftOnly) {
                return redirect()->back()->with('success', 'Draft aktivitas kegiatan berhasil disimpan.');
            }

            return redirect()->route('log-kegiatan.edit', [
                'log_kegiatan' => $logKegiatan,
                'step' => 3,
            ]);
        }

        // STEP 3: DETAILS (LOCATION, PLACE NOTE, GPS, DESCRIPTION)
        if ($step === 3) {
            $validated = $request->validate([
                'lokasi_kegiatan' => ['required', 'string'],
                'keterangan_tempat' => ['required', 'string', 'max:255'],
                'deskripsi_kegiatan' => ['required', 'string', 'min:5'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            ], [
                'lokasi_kegiatan.required' => 'Pilih lokasi kegiatan.',
                'keterangan_tempat.required' => 'Keterangan tempat wajib diisi.',
                'deskripsi_kegiatan.required' => 'Deskripsi rincian kegiatan wajib diisi.',
            ]);

            $logKegiatan->update([
                'lokasi_kegiatan' => $validated['lokasi_kegiatan'],
                'keterangan_tempat' => $validated['keterangan_tempat'],
                'deskripsi_kegiatan' => $validated['deskripsi_kegiatan'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'current_step' => max($logKegiatan->current_step, 4),
            ]);

            if ($isDraftOnly) {
                return redirect()->back()->with('success', 'Draft rincian detail berhasil disimpan.');
            }

            return redirect()->route('log-kegiatan.edit', [
                'log_kegiatan' => $logKegiatan,
                'step' => 4,
            ]);
        }

        // STEP 4: EVIDENCE (PHOTO UPLOAD MAX 50MB)
        if ($step === 4) {
            $hasExisting = $logKegiatan->fotoKegiatanFiles()->exists();
            $validated = $request->validate([
                'foto_kegiatan' => [
                    $hasExisting ? 'nullable' : 'required',
                    'array',
                    'min:1',
                ],
                'foto_kegiatan.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:51200'],
            ], [
                'foto_kegiatan.required' => 'Foto bukti dokumentasi wajib diunggah.',
                'foto_kegiatan.max' => 'Ukuran foto maksimal 50MB.',
            ]);

            $this->storeUploadedFiles($logKegiatan, $request->file('foto_kegiatan'), 'foto_kegiatan', 'kegiatan/foto');

            $logKegiatan->current_step = max($logKegiatan->current_step, 5);
            $logKegiatan->save();

            if ($isDraftOnly) {
                return redirect()->back()->with('success', 'Draft bukti foto berhasil disimpan.');
            }

            return redirect()->route('log-kegiatan.edit', [
                'log_kegiatan' => $logKegiatan,
                'step' => 5,
            ]);
        }

        // STEP 5: OUTPUTS (DELIVERABLES MAX 50MB)
        if ($step === 5) {
            $hasExisting = $logKegiatan->fileLuaranFiles()->exists();
            $validated = $request->validate([
                'deskripsi_luaran' => ['required', 'string', 'min:3'],
                'file_luaran' => [
                    $hasExisting ? 'nullable' : 'required',
                    'array',
                    'min:1',
                ],
                'file_luaran.*' => ['file', 'mimes:pdf,doc,docx,zip,rar,jpg,jpeg,png', 'max:51200'],
            ], [
                'deskripsi_luaran.required' => 'Deskripsi luaran kegiatan wajib diisi.',
                'file_luaran.required' => 'Berkas luaran kegiatan wajib diunggah.',
                'file_luaran.max' => 'Ukuran berkas luaran maksimal 50MB.',
            ]);

            $this->storeUploadedFiles($logKegiatan, $request->file('file_luaran'), 'file_luaran', 'kegiatan/luaran');

            $logKegiatan->deskripsi_luaran = $validated['deskripsi_luaran'];
            $logKegiatan->current_step = max($logKegiatan->current_step, 6);
            $logKegiatan->save();

            if ($isDraftOnly) {
                return redirect()->back()->with('success', 'Draft berkas luaran berhasil disimpan.');
            }

            return redirect()->route('log-kegiatan.edit', [
                'log_kegiatan' => $logKegiatan,
                'step' => 6,
            ]);
        }

        // STEP 6: REVIEW & SUBMIT
        if ($step === 6) {
            if ($request->input('action') === 'submit') {
                if (
                    empty($logKegiatan->tanggal_kehadiran) ||
                    empty($logKegiatan->master_kegiatan_id) ||
                    empty($logKegiatan->lokasi_kegiatan) ||
                    empty($logKegiatan->deskripsi_kegiatan) ||
                    ! $logKegiatan->fotoKegiatanFiles()->exists() ||
                    empty($logKegiatan->deskripsi_luaran) ||
                    ! $logKegiatan->fileLuaranFiles()->exists()
                ) {
                    return redirect()->back()->with('error', 'Semua bagian (Step 1 s.d. 5) wajib dilengkapi sebelum submit akhir.');
                }

                $logKegiatan->update([
                    'status' => 'menunggu',
                    'current_step' => 6,
                ]);

                return redirect()->route('log-kegiatan.index')->with('success', 'Laporan kegiatan KKN berhasil disubmit!');
            }

            return redirect()->route('log-kegiatan.index')->with('success', 'Draft log kegiatan berhasil disimpan.');
        }

        return redirect()->back();
    }

    /**
     * Simpan atau perbarui data Step 1 (Legacy endpoint compatibility).
     */
    public function saveStep1(LogKegiatanStep1Request $request, ?LogKegiatan $logKegiatan = null): RedirectResponse
    {
        $user = $request->user();
        $peserta = $user->peserta;

        if (! $peserta) {
            abort(403, 'Peserta tidak ditemukan.');
        }

        $validated = $request->validated();

        if ($logKegiatan && $logKegiatan->exists) {
            $this->authorizeOwner($user, $logKegiatan);
            $this->ensureDraft($logKegiatan);

            $logKegiatan->update([
                'master_kegiatan_id' => $validated['master_kegiatan_id'],
                'tanggal_kehadiran' => $validated['tanggal_kehadiran'],
                'jam_kehadiran' => $validated['jam_kehadiran'],
                'lokasi_kegiatan' => $validated['lokasi_kegiatan'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'current_step' => max($logKegiatan->current_step, 2),
            ]);

            return redirect()->route('log-kegiatan.edit', [
                'log_kegiatan' => $logKegiatan,
                'step' => 2,
            ])->with('success', 'Step 1 berhasil disimpan.');
        }

        $newLog = LogKegiatan::create([
            'peserta_id' => $peserta->id,
            'master_kegiatan_id' => $validated['master_kegiatan_id'],
            'tanggal_kehadiran' => $validated['tanggal_kehadiran'],
            'jam_kehadiran' => $validated['jam_kehadiran'],
            'lokasi_kegiatan' => $validated['lokasi_kegiatan'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'current_step' => 2,
            'status' => 'draft',
        ]);

        return redirect()->route('log-kegiatan.edit', [
            'log_kegiatan' => $newLog,
            'step' => 2,
        ])->with('success', 'Draft Step 1 berhasil dibuat.');
    }

    /**
     * Simpan data Step 2 (Legacy endpoint compatibility).
     */
    public function saveStep2(LogKegiatanStep2Request $request, LogKegiatan $logKegiatan): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeOwner($user, $logKegiatan);
        $this->ensureDraft($logKegiatan);

        $validated = $request->validated();
        $payload = [
            'deskripsi_kegiatan' => $validated['deskripsi_kegiatan'],
            'current_step' => max($logKegiatan->current_step, 3),
        ];

        $this->storeUploadedFiles($logKegiatan, $request->file('foto_kegiatan'), 'foto_kegiatan', 'kegiatan/foto');

        $logKegiatan->update($payload);

        return redirect()->route('log-kegiatan.edit', [
            'log_kegiatan' => $logKegiatan,
            'step' => 3,
        ])->with('success', 'Step 2 dokumentasi berhasil disimpan.');
    }

    /**
     * Simpan data Step 3 (Legacy endpoint compatibility).
     */
    public function saveStep3(LogKegiatanStep3Request $request, LogKegiatan $logKegiatan): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeOwner($user, $logKegiatan);
        $this->ensureDraft($logKegiatan);

        $validated = $request->validated();
        $payload = [
            'deskripsi_luaran' => $validated['deskripsi_luaran'],
            'current_step' => 3,
        ];

        $this->storeUploadedFiles($logKegiatan, $request->file('file_luaran'), 'file_luaran', 'kegiatan/luaran');

        $action = $validated['submit_action'];

        if ($action === 'submit') {
            if (
                empty($logKegiatan->deskripsi_kegiatan) ||
                ! $logKegiatan->fotoKegiatanFiles()->exists() ||
                ! $logKegiatan->fileLuaranFiles()->exists()
            ) {
                return redirect()->back()->with('error', 'Lengkapi seluruh data dokumentasi dan luaran sebelum melakukan submit akhir.');
            }

            $payload['status'] = 'menunggu';
            $logKegiatan->update($payload);

            return redirect()->route('log-kegiatan.index')->with('success', 'Log kegiatan KKN berhasil disubmit!');
        }

        $payload['status'] = 'draft';
        $logKegiatan->update($payload);

        return redirect()->route('log-kegiatan.index')->with('success', 'Draft log kegiatan berhasil disimpan.');
    }

    /**
     * Tampilkan halaman Stepper untuk melanjutkan atau mengedit log kegiatan.
     */
    public function edit(Request $request, LogKegiatan $logKegiatan): Response
    {
        $user = $request->user();
        $this->authorizeOwner($user, $logKegiatan);

        if (! $logKegiatan->isEditable()) {
            return redirect()->route('log-kegiatan.show', $logKegiatan)
                ->with('error', 'Laporan yang sudah dikirim tidak dapat diedit.');
        }

        $logKegiatan->load(['masterKegiatan', 'peserta.prodi', 'files', 'rencanaKehadiran']);
        $peserta = $logKegiatan->peserta ?? $user->peserta;
        $masterKegiatans = MasterKegiatan::query()
            ->where('kode_prodi', $peserta?->kode_prodi)
            ->orderBy('kode_kegiatan')
            ->get();

        $requestedStep = (int) $request->input('step', $logKegiatan->current_step ?: 1);
        $step = in_array($requestedStep, [1, 2, 3, 4, 5, 6], true) ? $requestedStep : 1;

        $student = [
            'nama_lengkap' => $peserta?->nama_lengkap ?? $user->name,
            'nim' => $peserta?->nim ?? $user->nim,
            'kode_prodi' => $peserta?->kode_prodi,
            'email' => $user->email ?? ($user->nim ? "{$user->nim}@itenas.ac.id" : 'student@itenas.ac.id'),
        ];

        return Inertia::render('LogKegiatan/Stepper', [
            'log' => $logKegiatan,
            'currentStep' => $step,
            'student' => $student,
            'masterKegiatans' => $masterKegiatans,
            'prodis' => Prodi::query()->orderBy('nama_prodi')->get(['kode_prodi', 'nama_prodi']),
            'rencanaKehadirans' => $peserta
                ? $peserta->rencanaKehadirans()
                    ->orderByDesc('tanggal_rencana')
                    ->orderByDesc('id')
                    ->get(['id', 'tanggal_rencana', 'jam_kehadiran'])
                : [],
        ]);
    }

    public function destroy(Request $request, LogKegiatan $logKegiatan): RedirectResponse
    {
        $user = $request->user();

        if (! $user?->isMahasiswa()) {
            abort(403, 'Hanya mahasiswa yang dapat menghapus draft kegiatan.');
        }

        $this->authorizeOwner($user, $logKegiatan);

        if (! $logKegiatan->isDraft()) {
            abort(403, 'Hanya kegiatan berstatus draft yang dapat dihapus.');
        }

        $logKegiatan->loadMissing('files');
        Storage::disk('public')->delete($logKegiatan->files->pluck('file_path')->all());
        $logKegiatan->delete();

        return redirect()
            ->route('log-kegiatan.index')
            ->with('success', 'Draft kegiatan berhasil dihapus.');
    }

    /**
     * Tampilkan detail log kegiatan.
     */
    public function show(Request $request, LogKegiatan $logKegiatan): Response
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            // Superadmin dapat melihat seluruh laporan.
        } elseif ($user->isDpp()) {
            $this->authorizeReviewerLog($user, $logKegiatan);
        } else {
            $this->authorizeOwner($user, $logKegiatan);
        }

        $logKegiatan->load([
            'masterKegiatan',
            'peserta.user',
            'peserta.prodi',
            'files',
        ]);

        return Inertia::render('LogKegiatan/Show', [
            'log' => $logKegiatan,
            'isAdmin' => $user->isAdmin(),
            'isDpp' => $user->isDpp(),
            'isReviewer' => $user->isAdmin() || $user->isDpp(),
        ]);
    }

    /**
     * Display an uploaded file to an authorized log participant or administrator.
     */
    public function showFile(
        Request $request,
        LogKegiatan $logKegiatan,
        LogKegiatanFile $file
    ): BinaryFileResponse {
        $user = $request->user();

        if ($user->isAdmin()) {
            // Superadmin dapat melihat seluruh berkas.
        } elseif ($user->isDpp()) {
            $this->authorizeReviewerLog($user, $logKegiatan);
        } else {
            $this->authorizeOwner($user, $logKegiatan);
        }

        abort_unless($file->log_kegiatan_id === $logKegiatan->id, 404);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($file->file_path), 404);

        return response()->file($disk->path($file->file_path));
    }

    /**
     * Konfirmasi laporan kegiatan yang telah dikirim mahasiswa.
     */
    public function approve(
        Request $request,
        LogKegiatan $logKegiatan
    ): RedirectResponse {
        $this->authorizeReviewerLog($request->user(), $logKegiatan);

        if (! $logKegiatan->isWaiting()) {
            return back()->with(
                'error',
                'Hanya laporan berstatus menunggu yang dapat disetujui.'
            );
        }

        $logKegiatan->update([
            'status' => 'disetujui',
        ]);

        return back()->with(
            'success',
            'Laporan kegiatan berhasil disetujui.'
        );
    }

    public function reject(
        Request $request,
        LogKegiatan $logKegiatan
    ): RedirectResponse {
        $this->authorizeReviewerLog($request->user(), $logKegiatan);

        if (! $logKegiatan->isWaiting()) {
            return back()->with(
                'error',
                'Hanya laporan berstatus menunggu yang dapat ditolak.'
            );
        }

        $logKegiatan->update([
            'status' => 'ditolak',
        ]);

        return back()->with(
            'success',
            'Laporan kegiatan ditolak dan dapat diperbaiki oleh mahasiswa.'
        );
    }

    private function getDppKodeProdi(User $user): string
    {
        $kodeProdi = $user->dppProfile?->kode_prodi;

        abort_unless(
            is_string($kodeProdi) && $kodeProdi !== '',
            403,
            'Profil DPP belum memiliki program studi.'
        );

        return $kodeProdi;
    }

    private function constrainLogsToProdi(
        Builder $query,
        string $kodeProdi
    ): Builder {
        $namaProdi = Prodi::query()
            ->where('kode_prodi', $kodeProdi)
            ->value('nama_prodi');

        return $query->whereHas(
            'peserta',
            function (Builder $pesertaQuery) use (
                $kodeProdi,
                $namaProdi
            ): void {
                $pesertaQuery->where('kode_prodi', $kodeProdi);

                if ($namaProdi) {
                    $pesertaQuery->orWhere(function (
                        Builder $legacyQuery
                    ) use ($namaProdi): void {
                        $legacyQuery
                            ->whereNull('kode_prodi')
                            ->where('program_studi', $namaProdi);
                    });
                }
            }
        );
    }

    private function storeUploadedFiles(
        LogKegiatan $logKegiatan,
        UploadedFile|array|null $uploads,
        string $type,
        string $directory,
    ): void {
        if (! $uploads) {
            return;
        }

        foreach (is_array($uploads) ? $uploads : [$uploads] as $upload) {
            $logKegiatan->files()->create([
                'jenis_file' => $type,
                'file_path' => $upload->store($directory, 'public'),
                'nama_file_asli' => $upload->getClientOriginalName(),
                'ukuran_file' => $upload->getSize(),
            ]);
        }
    }

    /**
     * Pastikan mahasiswa hanya dapat mengelola log miliknya sendiri.
     */
    private function authorizeOwner(?User $user, LogKegiatan $logKegiatan): void
    {
        if (! $user || ! $user->peserta || $logKegiatan->peserta_id !== $user->peserta->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk log kegiatan ini.');
        }
    }

    private function ensureDraft(LogKegiatan $logKegiatan): void
    {
        if (! $logKegiatan->isEditable()) {
            abort(403, 'Laporan yang sedang menunggu atau sudah disetujui tidak dapat diubah.');
        }
    }

    private function safeCsvCell(string $value): string
    {
        return preg_match('/^[\t\r\n=+\-@]/', $value) === 1
            ? "'{$value}"
            : $value;
    }

    private function authorizeReviewerLog(
        ?User $user,
        LogKegiatan $logKegiatan
    ): void {
        abort_unless(
            $user && ($user->isAdmin() || $user->isDpp()),
            403
        );

        if ($user->isAdmin()) {
            return;
        }

        $kodeProdi = $this->getDppKodeProdi($user);
        $namaProdi = Prodi::query()
            ->where('kode_prodi', $kodeProdi)
            ->value('nama_prodi');

        $belongsToDpp = $logKegiatan->peserta()
            ->where(function (Builder $query) use (
                $kodeProdi,
                $namaProdi
            ): void {
                $query->where('kode_prodi', $kodeProdi);

                if ($namaProdi) {
                    $query->orWhere(function (
                        Builder $legacyQuery
                    ) use ($namaProdi): void {
                        $legacyQuery
                            ->whereNull('kode_prodi')
                            ->where('program_studi', $namaProdi);
                    });
                }
            })
            ->exists();

        abort_unless(
            $belongsToDpp,
            403,
            'Anda tidak memiliki akses ke laporan dari program studi lain.'
        );
    }
}
