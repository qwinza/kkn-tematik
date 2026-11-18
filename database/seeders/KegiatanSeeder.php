<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KegiatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $prodi = [
            ['kode_prodi' => 'TI', 'nama_prodi' => 'Teknik Industri'],
            ['kode_prodi' => 'TK', 'nama_prodi' => 'Teknik Kimia'],
            ['kode_prodi' => 'MS', 'nama_prodi' => 'Teknik Mesin'],
            ['kode_prodi' => 'EL', 'nama_prodi' => 'Teknik Elektro'],
            ['kode_prodi' => 'IF', 'nama_prodi' => 'Informatika'],
            ['kode_prodi' => 'SI', 'nama_prodi' => 'Sistem Informasi'],
            ['kode_prodi' => 'TG', 'nama_prodi' => 'Teknik Geodesi'],
            ['kode_prodi' => 'TL', 'nama_prodi' => 'Teknik Lingkungan'],
            ['kode_prodi' => 'TS', 'nama_prodi' => 'Teknik Sipil'],
            ['kode_prodi' => 'PWK', 'nama_prodi' => 'Perencanaan Wilayah dan Kota'],
            ['kode_prodi' => 'ARS', 'nama_prodi' => 'Arsitektur'],
            ['kode_prodi' => 'DKV', 'nama_prodi' => 'Desain Komunikasi Visual'],
            ['kode_prodi' => 'DI', 'nama_prodi' => 'Desain Interior'],
            ['kode_prodi' => 'DP', 'nama_prodi' => 'Desain Produk'],
        ];

        DB::table('prodi')->insert($prodi);

        $kegiatans = [
            ['kode_kegiatan' => 'ARS-01', 'nama_kegiatan' => 'Identifikasi & Pemetaan Spasial Foto Udara Drone', 'kategori' => 'Pemetaan & Riset', 'kode_prodi' => 'ARS'],
            ['kode_kegiatan' => 'ARS-02', 'nama_kegiatan' => 'Perancangan Master Plan Kawasan & Zonasi Buffer', 'kategori' => 'Perancangan', 'kode_prodi' => 'ARS'],
            ['kode_kegiatan' => 'ARS-03', 'nama_kegiatan' => 'Detail Selubung Bangunan TPS & Utilitas Lindi/Ventilasi', 'kategori' => 'Perancangan', 'kode_prodi' => 'ARS'],
            ['kode_kegiatan' => 'ARS-04', 'nama_kegiatan' => 'Pembuatan Maket Detail 1:100 & Penyelenggaraan Expo', 'kategori' => 'Perancangan', 'kode_prodi' => 'ARS'],
            ['kode_kegiatan' => 'DKV-01', 'nama_kegiatan' => 'Stage 01 - Discover: Riset Etnografi & Observasi Audiens', 'kategori' => 'Pemetaan & Riset', 'kode_prodi' => 'DKV'],
            ['kode_kegiatan' => 'DKV-02', 'nama_kegiatan' => 'Stage 02 - Planning: Strategi Kampanye Visual 3R', 'kategori' => 'Pelatihan & Sosialisasi', 'kode_prodi' => 'DKV'],
            ['kode_kegiatan' => 'DKV-03', 'nama_kegiatan' => 'Stage 03 - Create: Perancangan Video, Buku Saku & Ambient Media', 'kategori' => 'Perancangan', 'kode_prodi' => 'DKV'],
            ['kode_kegiatan' => 'DKV-04', 'nama_kegiatan' => 'Stage 04 - Action: Pendampingan Lapangan & Video Dokumenter Warga', 'kategori' => 'Pelatihan & Sosialisasi', 'kode_prodi' => 'DKV'],
            ['kode_kegiatan' => 'DI-01', 'nama_kegiatan' => 'Evaluasi Tata Ruang Mikro Publik & Ruang Transisi TPS', 'kategori' => 'Evaluasi & Pengujian', 'kode_prodi' => 'DI'],
            ['kode_kegiatan' => 'DI-02', 'nama_kegiatan' => 'Perancangan Layout Interior Pemilahan TPS Ergonomis', 'kategori' => 'Perancangan', 'kode_prodi' => 'DI'],
            ['kode_kegiatan' => 'DI-03', 'nama_kegiatan' => 'Pelatihan Fun Plastics Upcycled Armatur Lampu Botol Plastik', 'kategori' => 'Pelatihan & Sosialisasi', 'kode_prodi' => 'DI'],
            ['kode_kegiatan' => 'DP-01', 'nama_kegiatan' => 'Modul DP-1: Perancangan Wadah Pilah 4 Warna & Alat Mekanis', 'kategori' => 'Perancangan', 'kode_prodi' => 'DP'],
            ['kode_kegiatan' => 'DP-02', 'nama_kegiatan' => 'Modul DP-2: Eksperimen Termal & Olah Plastik Low-Value', 'kategori' => 'Fabrikasi & Implementasi', 'kode_prodi' => 'DP'],
            ['kode_kegiatan' => 'DP-03', 'nama_kegiatan' => 'Modul DP-3: Pengembangan Produk Commercial & HPP Sales', 'kategori' => 'Pengembangan Sistem', 'kode_prodi' => 'DP'],
            ['kode_kegiatan' => 'DP-04', 'nama_kegiatan' => 'Modul DP-4: Desain Kemasan Daur Ulang, Katalog & Pameran', 'kategori' => 'Pelatihan & Sosialisasi', 'kode_prodi' => 'DP'],
            ['kode_kegiatan' => 'TG-01', 'nama_kegiatan' => 'Persiapan & Desain Survei Geospasial Sampah', 'kategori' => 'Pemetaan & Riset', 'kode_prodi' => 'TG'],
            ['kode_kegiatan' => 'TG-02', 'nama_kegiatan' => 'Pemetaan Terestris/GNSS & Drone Kel. Gempol Sari', 'kategori' => 'Pemetaan & Riset', 'kode_prodi' => 'TG'],
            ['kode_kegiatan' => 'TG-03', 'nama_kegiatan' => 'Pemetaan Batas & Fasilitas Kawasan TPA Sarimukti', 'kategori' => 'Pemetaan & Riset', 'kode_prodi' => 'TG'],
            ['kode_kegiatan' => 'TG-04', 'nama_kegiatan' => 'Pengolahan Data Vector/Raster SIG & Spatial Overlay', 'kategori' => 'Pengolahan Data', 'kode_prodi' => 'TG'],
            ['kode_kegiatan' => 'TG-05', 'nama_kegiatan' => 'Pengembangan WebGIS Dashboard Interaktif Sebaran Sampah', 'kategori' => 'Pengembangan Sistem', 'kode_prodi' => 'TG'],
            ['kode_kegiatan' => 'TG-06', 'nama_kegiatan' => 'Validasi Topologi Data Geospasial & Pelaporan Akhir', 'kategori' => 'Evaluasi & Pengujian', 'kode_prodi' => 'TG'],
            ['kode_kegiatan' => 'TL-01', 'nama_kegiatan' => 'Penyusunan Konsep Reduksi Sampah Hulu 3R & Komposting', 'kategori' => 'Perencanaan & Konsep', 'kode_prodi' => 'TL'],
            ['kode_kegiatan' => 'TL-02', 'nama_kegiatan' => 'Audit Timbulan Sampah 8 Hari SNI 19-3964-1994 & POS', 'kategori' => 'Pemetaan & Riset', 'kode_prodi' => 'TL'],
            ['kode_kegiatan' => 'TL-03', 'nama_kegiatan' => 'Formula Indikator Reward/Punishment SIM Itenas Resik', 'kategori' => 'Perencanaan & Konsep', 'kode_prodi' => 'TL'],
            ['kode_kegiatan' => 'TL-04', 'nama_kegiatan' => 'Evaluasi Dampak TPA Sarimukti & Desain Instalasi Lindi IPL', 'kategori' => 'Perancangan', 'kode_prodi' => 'TL'],
            ['kode_kegiatan' => 'TL-05', 'nama_kegiatan' => 'Pembentukan Kelembagaan KSM & Modul Workshop Warga', 'kategori' => 'Pelatihan & Sosialisasi', 'kode_prodi' => 'TL'],
            ['kode_kegiatan' => 'TS-01', 'nama_kegiatan' => 'Program A: Perencanaan DED TPS 3R & Fasilitas Komposting', 'kategori' => 'Perencanaan & Konsep', 'kode_prodi' => 'TS'],
            ['kode_kegiatan' => 'TS-02', 'nama_kegiatan' => 'Program B: Uji Kelayakan & Kuat Tekan Paving Block Daur Ulang', 'kategori' => 'Evaluasi & Pengujian', 'kode_prodi' => 'TS'],
            ['kode_kegiatan' => 'TS-03', 'nama_kegiatan' => 'Program C: Pemetaan Drainase & Perancangan Hidrolika', 'kategori' => 'Pemetaan & Riset', 'kode_prodi' => 'TS'],
            ['kode_kegiatan' => 'TS-04', 'nama_kegiatan' => 'Program E: Optimasi Rute Pengangkutan & Calculation RAB', 'kategori' => 'Kegiatan Lapangan', 'kode_prodi' => 'TS'],
            ['kode_kegiatan' => 'PWK-01', 'nama_kegiatan' => 'Program PWK: Perencanaan Partisipatif & Sektor Informal', 'kategori' => 'Perencanaan & Konsep', 'kode_prodi' => 'PWK'],
            ['kode_kegiatan' => 'SI-01', 'nama_kegiatan' => 'Tahap 1: Analisis Kebutuhan & FGD Warga Iuran Sampah', 'kategori' => 'Kegiatan Lapangan', 'kode_prodi' => 'SI'],
            ['kode_kegiatan' => 'SI-02', 'nama_kegiatan' => 'Tahap 2: Perancangan Arsitektur Database & UI/UX Figma', 'kategori' => 'Perancangan', 'kode_prodi' => 'SI'],
            ['kode_kegiatan' => 'SI-03', 'nama_kegiatan' => 'Tahap 3: Pengkodean Fullstack Web & Mobile SIM Itenas Resik', 'kategori' => 'Pengembangan Sistem', 'kode_prodi' => 'SI'],
            ['kode_kegiatan' => 'SI-04', 'nama_kegiatan' => 'Tahap 4: User Acceptance Test (UAT) Lapangan Bersama RT/RW', 'kategori' => 'Evaluasi & Pengujian', 'kode_prodi' => 'SI'],
            ['kode_kegiatan' => 'SI-05', 'nama_kegiatan' => 'Tahap 5: Deployment Server, Training User & User Manual', 'kategori' => 'Fabrikasi & Implementasi', 'kode_prodi' => 'SI'],
            ['kode_kegiatan' => 'IF-WP1', 'nama_kegiatan' => 'WP1: Analisis Kebutuhan Jaringan IoT & Arsitektur Komputasi', 'kategori' => 'Pengembangan Sistem', 'kode_prodi' => 'IF'],
            ['kode_kegiatan' => 'IF-WP2', 'nama_kegiatan' => 'WP2: Integrasi Hardware Sensor ESP32 & Protokol MQTT', 'kategori' => 'Pengembangan Sistem', 'kode_prodi' => 'IF'],
            ['kode_kegiatan' => 'IF-WP3', 'nama_kegiatan' => 'WP3: Pengembangan Backend REST API & Database Records', 'kategori' => 'Pengembangan Sistem', 'kode_prodi' => 'IF'],
            ['kode_kegiatan' => 'IF-WP4', 'nama_kegiatan' => 'WP4: Pembangunan Dashboard Analytics Real-Time & Alert', 'kategori' => 'Pengembangan Sistem', 'kode_prodi' => 'IF'],
            ['kode_kegiatan' => 'IF-WP5', 'nama_kegiatan' => 'WP5: Pengujian Integrasi Sistem Jaringan & Stress Test', 'kategori' => 'Evaluasi & Pengujian', 'kode_prodi' => 'IF'],
            ['kode_kegiatan' => 'IF-WP6', 'nama_kegiatan' => 'WP6: Pilot Project Lapangan & Pelatihan Operator Desa', 'kategori' => 'Pelatihan & Sosialisasi', 'kode_prodi' => 'IF'],
            ['kode_kegiatan' => 'EL-01', 'nama_kegiatan' => 'Observasi & Perancangan Skematik Rangkaian Instrumentasi', 'kategori' => 'Pemetaan & Riset', 'kode_prodi' => 'EL'],
            ['kode_kegiatan' => 'EL-02', 'nama_kegiatan' => 'Perakitan Sensor Ultrasonik, Load Cell & Rangkaian Daya', 'kategori' => 'Fabrikasi & Implementasi', 'kode_prodi' => 'EL'],
            ['kode_kegiatan' => 'EL-03', 'nama_kegiatan' => 'Pemrograman Logika Otomasi Mikrokontroler C/C++', 'kategori' => 'Pengembangan Sistem', 'kode_prodi' => 'EL'],
            ['kode_kegiatan' => 'EL-04', 'nama_kegiatan' => 'Kalibrasi Presisi Sensor & Integration Test Panel', 'kategori' => 'Evaluasi & Pengujian', 'kode_prodi' => 'EL'],
            ['kode_kegiatan' => 'EL-05', 'nama_kegiatan' => 'Penyusunan Manual Operasional Panel & Training Operator', 'kategori' => 'Perencanaan & Konsep', 'kode_prodi' => 'EL'],
            ['kode_kegiatan' => 'MS-01', 'nama_kegiatan' => 'Evaluasi Teknis Kinerja Alat & Komposter Existing Mitra', 'kategori' => 'Evaluasi & Pengujian', 'kode_prodi' => 'MS'],
            ['kode_kegiatan' => 'MS-02', 'nama_kegiatan' => 'Perancangan Gambar CAD 3D & Perhitungan Tegangan Struktur', 'kategori' => 'Perancangan', 'kode_prodi' => 'MS'],
            ['kode_kegiatan' => 'MS-03', 'nama_kegiatan' => 'Fabrikasi Mesin Pemadat Pedal Botol Plastik / Pencacah', 'kategori' => 'Fabrikasi & Implementasi', 'kode_prodi' => 'MS'],
            ['kode_kegiatan' => 'MS-04', 'nama_kegiatan' => 'Uji Operasional Beban, Panduan SOP & Pelatihan K3', 'kategori' => 'Pelatihan & Sosialisasi', 'kode_prodi' => 'MS'],
            ['kode_kegiatan' => 'TI-01', 'nama_kegiatan' => 'Skema 1: Pemetaan Process Flow Chart & Eliminasi Waste', 'kategori' => 'Pemetaan & Riset', 'kode_prodi' => 'TI'],
            ['kode_kegiatan' => 'TI-02', 'nama_kegiatan' => 'Skema 2: Penyusunan Dokumen SOP Operasional & Matriks K3', 'kategori' => 'Perencanaan & Konsep', 'kode_prodi' => 'TI'],
            ['kode_kegiatan' => 'TI-03', 'nama_kegiatan' => 'Skema 3: Optimasi Layout TPS 3R Berbasis ARC & ARD', 'kategori' => 'Perancangan', 'kode_prodi' => 'TI'],
            ['kode_kegiatan' => 'TI-04', 'nama_kegiatan' => 'Skema 4: Perancangan Matriks KPI & Dashboard Kinerja', 'kategori' => 'Perancangan', 'kode_prodi' => 'TI'],
            ['kode_kegiatan' => 'TK-01', 'nama_kegiatan' => 'Baseline Karakterisasi Sampah Organik & Uji C/N Ratio', 'kategori' => 'Pemetaan & Riset', 'kode_prodi' => 'TK'],
            ['kode_kegiatan' => 'TK-02', 'nama_kegiatan' => 'Perancangan & Fabrikasi Reaktor Biodigester OWREN 200L-2m³', 'kategori' => 'Perancangan', 'kode_prodi' => 'TK'],
            ['kode_kegiatan' => 'TK-03', 'nama_kegiatan' => 'Pemisahan Slurry Sludge Padat BSF/Kompos & Liquid POC', 'kategori' => 'Fabrikasi & Implementasi', 'kode_prodi' => 'TK'],
            ['kode_kegiatan' => 'TK-04', 'nama_kegiatan' => 'Monitoring Gas Metana (CH4) & Validasi Neraca Massa', 'kategori' => 'Evaluasi & Pengujian', 'kode_prodi' => 'TK'],
            ['kode_kegiatan' => 'TK-05', 'nama_kegiatan' => 'Pelatihan Operasional Reaktor Warga & Penyusunan SOP', 'kategori' => 'Pelatihan & Sosialisasi', 'kode_prodi' => 'TK'],
        ];

        DB::table('master_kegiatans')->insert($kegiatans);
    }
}
