<?php

namespace App\Http\Controllers;

use App\Http\Requests\LogKegiatanStep1Request;
use App\Http\Requests\LogKegiatanStep2Request;
use App\Http\Requests\LogKegiatanStep3Request;
use App\Models\LogKegiatan;
use App\Models\LogKegiatanFile;
use App\Models\MasterKegiatan;
use App\Models\Peserta;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogKegiatanController extends Controller
{
    /**
     * Tampilkan riwayat log kegiatan mahasiswa atau seluruh log untuk admin.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in($isAdmin
                ? ['menunggu', 'disetujui', 'ditolak']
                : ['draft', 'menunggu', 'disetujui', 'ditolak'])],
            'master_kegiatan_id' => ['nullable', 'integer', 'exists:master_kegiatans,id'],
            'sort' => ['nullable', Rule::in(['mahasiswa', 'kegiatan', 'tanggal_kehadiran', 'status', 'current_step'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $search = $filters['search'] ?? '';
        $status = $filters['status'] ?? '';
        $masterId = $filters['master_kegiatan_id'] ?? null;
        $sort = $filters['sort'] ?? 'tanggal_kehadiran';
        $direction = $filters['direction'] ?? 'desc';

        $query = LogKegiatan::query()
            ->with(['masterKegiatan', 'peserta.user'])
            ->when(! $isAdmin, function ($q) use ($user) {
                $pesertaId = $user?->peserta?->id ?? 0;
                $q->where('peserta_id', $pesertaId);
            })
            ->when($isAdmin, fn ($q) => $q->whereIn('status', ['menunggu', 'disetujui', 'ditolak']))
            ->when($status !== '', function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($masterId, function ($q) use ($masterId) {
                $q->where('master_kegiatan_id', $masterId);
            })
            ->when($search !== '', function ($q) use ($search, $isAdmin) {
                $q->where(function ($sub) use ($search, $isAdmin) {
                    $sub->where('deskripsi_kegiatan', 'like', "%{$search}%")
                        ->orWhere('deskripsi_luaran', 'like', "%{$search}%")
                        ->orWhereHas('masterKegiatan', function ($mq) use ($search) {
                            $mq->where('nama_kegiatan', 'like', "%{$search}%")
                                ->orWhere('kode_kegiatan', 'like', "%{$search}%");
                        });

                    if ($isAdmin) {
                        $sub->orWhereHas('peserta', function ($pq) use ($search) {
                            $pq->where('nama_lengkap', 'like', "%{$search}%")
                                ->orWhere('nim', 'like', "%{$search}%");
                        });
                    }
                });
            });

        if ($sort === 'mahasiswa') {
            $query->orderBy(Peserta::query()
                ->select('nama_lengkap')
                ->whereColumn('pesertas.id', 'log_kegiatans.peserta_id'), $direction);
        } elseif ($sort === 'kegiatan') {
            $query->orderBy(MasterKegiatan::query()
                ->select('nama_kegiatan')
                ->whereColumn('master_kegiatans.id', 'log_kegiatans.master_kegiatan_id'), $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        $logs = $query->paginate(10)->withQueryString();
        $masterKegiatans = MasterKegiatan::query()
            ->when(! $isAdmin, fn ($query) => $query->where('kode_prodi', $user?->peserta?->kode_prodi))
            ->orderBy('nama_kegiatan')
            ->get(['id', 'kode_kegiatan', 'nama_kegiatan', 'kategori', 'kode_prodi']);

        return Inertia::render('LogKegiatan/Index', [
            'logs' => $logs,
            'masterKegiatans' => $masterKegiatans,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'master_kegiatan_id' => $masterId,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'isAdmin' => $isAdmin,
        ]);
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
        ]);
    }

    /**
     * Simpan data berdasarkan step (1 s.d. 7) dengan dukungan simpan draft.
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

        // STEP 1: IDENTITY
        if ($step === 1) {
            $validated = $request->validate([
                'kode_prodi' => ['required', 'string', 'exists:prodi,kode_prodi'],
            ], [
                'kode_prodi.required' => 'Pilih program studi terlebih dahulu.',
                'kode_prodi.exists' => 'Program studi yang dipilih tidak ditemukan.',
            ]);

            if ($peserta->kode_prodi !== null && $peserta->kode_prodi !== $validated['kode_prodi']) {
                throw ValidationException::withMessages([
                    'kode_prodi' => 'Program studi peserta sudah ditetapkan dan tidak dapat diubah dari log kegiatan.',
                ]);
            }

            $peserta->update(['kode_prodi' => $validated['kode_prodi']]);

            $logKegiatan->current_step = max($logKegiatan->current_step, 2);
            $logKegiatan->save();

            if ($isDraftOnly) {
                return redirect()->back()->with('success', 'Draft identitas berhasil disimpan.');
            }

            return redirect()->route('log-kegiatan.edit', [
                'log_kegiatan' => $logKegiatan,
                'step' => 2,
            ]);
        }

        // STEP 2: SCHEDULE
        if ($step === 2) {
            $validated = $request->validate([
                'tanggal_kehadiran' => ['required', 'date'],
                'jam_mulai' => ['required', 'string', 'max:10'],
                'jam_selesai' => ['required', 'string', 'max:10'],
            ], [
                'tanggal_kehadiran.required' => 'Tanggal kehadiran wajib diisi.',
                'jam_mulai.required' => 'Jam mulai wajib diisi.',
                'jam_selesai.required' => 'Jam selesai wajib diisi.',
            ]);

            $logKegiatan->update([
                'tanggal_kehadiran' => $validated['tanggal_kehadiran'],
                'jam_mulai' => $validated['jam_mulai'],
                'jam_selesai' => $validated['jam_selesai'],
                'jam_kehadiran' => "{$validated['jam_mulai']} - {$validated['jam_selesai']}",
                'current_step' => max($logKegiatan->current_step, 3),
            ]);

            if ($isDraftOnly) {
                return redirect()->back()->with('success', 'Draft jadwal berhasil disimpan.');
            }

            return redirect()->route('log-kegiatan.edit', [
                'log_kegiatan' => $logKegiatan,
                'step' => 3,
            ]);
        }

        // STEP 3: ACTIVITY (FACULTY & PROJECT CODE)
        if ($step === 3) {
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
                'current_step' => max($logKegiatan->current_step, 4),
            ]);

            if ($isDraftOnly) {
                return redirect()->back()->with('success', 'Draft aktivitas kegiatan berhasil disimpan.');
            }

            return redirect()->route('log-kegiatan.edit', [
                'log_kegiatan' => $logKegiatan,
                'step' => 4,
            ]);
        }

        // STEP 4: DETAILS (LOCATION, GPS, DESCRIPTION)
        if ($step === 4) {
            $validated = $request->validate([
                'lokasi_kegiatan' => ['required', 'string'],
                'deskripsi_kegiatan' => ['required', 'string', 'min:5'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            ], [
                'lokasi_kegiatan.required' => 'Pilih lokasi kegiatan.',
                'deskripsi_kegiatan.required' => 'Deskripsi rincian kegiatan wajib diisi.',
            ]);

            $logKegiatan->update([
                'lokasi_kegiatan' => $validated['lokasi_kegiatan'],
                'deskripsi_kegiatan' => $validated['deskripsi_kegiatan'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'current_step' => max($logKegiatan->current_step, 5),
            ]);

            if ($isDraftOnly) {
                return redirect()->back()->with('success', 'Draft rincian detail berhasil disimpan.');
            }

            return redirect()->route('log-kegiatan.edit', [
                'log_kegiatan' => $logKegiatan,
                'step' => 5,
            ]);
        }

        // STEP 5: EVIDENCE (PHOTO UPLOAD MAX 50MB)
        if ($step === 5) {
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

            $logKegiatan->current_step = max($logKegiatan->current_step, 6);
            $logKegiatan->save();

            if ($isDraftOnly) {
                return redirect()->back()->with('success', 'Draft bukti foto berhasil disimpan.');
            }

            return redirect()->route('log-kegiatan.edit', [
                'log_kegiatan' => $logKegiatan,
                'step' => 6,
            ]);
        }

        // STEP 6: OUTPUTS (DELIVERABLES MAX 50MB)
        if ($step === 6) {
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
            $logKegiatan->current_step = max($logKegiatan->current_step, 7);
            $logKegiatan->save();

            if ($isDraftOnly) {
                return redirect()->back()->with('success', 'Draft berkas luaran berhasil disimpan.');
            }

            return redirect()->route('log-kegiatan.edit', [
                'log_kegiatan' => $logKegiatan,
                'step' => 7,
            ]);
        }

        // STEP 7: REVIEW & SUBMIT
        if ($step === 7) {
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
                    return redirect()->back()->with('error', 'Semua bagian (Step 1 s.d. 6) wajib dilengkapi sebelum submit akhir.');
                }

                $logKegiatan->update([
                    'status' => 'menunggu',
                    'current_step' => 7,
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

        $logKegiatan->load(['masterKegiatan', 'peserta.prodi', 'files']);
        $peserta = $logKegiatan->peserta ?? $user->peserta;
        $masterKegiatans = MasterKegiatan::query()
            ->where('kode_prodi', $peserta?->kode_prodi)
            ->orderBy('kode_kegiatan')
            ->get();

        $requestedStep = (int) $request->input('step', $logKegiatan->current_step ?: 1);
        $step = in_array($requestedStep, [1, 2, 3, 4, 5, 6, 7], true) ? $requestedStep : 1;

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
        ]);
    }

    /**
     * Tampilkan detail log kegiatan.
     */
    public function show(Request $request, LogKegiatan $logKegiatan): Response
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            $this->authorizeOwner($user, $logKegiatan);
        }

        $logKegiatan->load(['masterKegiatan', 'peserta.user', 'peserta.prodi', 'files']);

        return Inertia::render('LogKegiatan/Show', [
            'log' => $logKegiatan,
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    /**
     * Display an uploaded file to an authorized log participant or administrator.
     */
    public function showFile(Request $request, LogKegiatan $logKegiatan, LogKegiatanFile $file): BinaryFileResponse
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
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
    public function approve(Request $request, LogKegiatan $logKegiatan): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Hanya admin yang dapat menyetujui laporan kegiatan.');
        }

        if (! $logKegiatan->isWaiting()) {
            return redirect()->back()->with('error', 'Hanya laporan berstatus menunggu yang dapat disetujui.');
        }

        $logKegiatan->update(['status' => 'disetujui']);

        return redirect()->back()->with('success', 'Laporan kegiatan berhasil disetujui.');
    }

    public function reject(Request $request, LogKegiatan $logKegiatan): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Hanya admin yang dapat menolak laporan kegiatan.');
        }

        if (! $logKegiatan->isWaiting()) {
            return redirect()->back()->with('error', 'Hanya laporan berstatus menunggu yang dapat ditolak.');
        }

        $logKegiatan->update(['status' => 'ditolak']);

        return redirect()->back()->with('success', 'Laporan kegiatan ditolak dan dapat diperbaiki oleh mahasiswa.');
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
}
