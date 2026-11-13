import json
import sys


def rekap_jam_kegiatan(daftar_kegiatan: list[dict[str, object]]) -> list[dict[str, object]]:
    total_jam_per_nim: dict[str, float] = {}
    nama_per_nim: dict[str, str] = {}

    for kegiatan in daftar_kegiatan:
        nim = str(kegiatan.get("nim", "")).strip()
        if not nim:
            continue

        nama = str(kegiatan.get("nama", "")).strip()
        durasi_jam = float(kegiatan.get("durasi_jam", 0))

        total_jam_per_nim[nim] = total_jam_per_nim.get(nim, 0.0) + durasi_jam
        nama_per_nim[nim] = nama_per_nim.get(nim) or nama

    hasil_rekap: list[dict[str, object]] = []

    for nim, total_jam in total_jam_per_nim.items():
        hasil_rekap.append(
            {
                "nim": nim,
                "nama": nama_per_nim[nim],
                "total_jam": round(total_jam, 1),
            }
        )

    return sorted(hasil_rekap, key=lambda mahasiswa: str(mahasiswa["nama"]).casefold())


def analisis_progres_kkn(data: dict[str, list[dict[str, object]]]) -> dict[str, object]:
    mahasiswa_per_prodi: dict[str, dict[str, object]] = {}
    mahasiswa_aktif_per_prodi: dict[str, set[str]] = {}
    kegiatan_per_nama: dict[str, dict[str, float | int | str]] = {}
    total_jam_global = 0.0

    for mahasiswa in data.get("mahasiswa", []):
        kode_prodi = str(mahasiswa.get("kode_prodi") or "tanpa-prodi")
        nama_prodi = str(mahasiswa.get("prodi") or "Belum ditentukan")

        if kode_prodi not in mahasiswa_per_prodi:
            mahasiswa_per_prodi[kode_prodi] = {
                "kode_prodi": kode_prodi,
                "prodi": nama_prodi,
                "total_mahasiswa": 0,
                "mahasiswa_aktif": 0,
                "total_kegiatan": 0,
                "total_jam": 0.0,
            }
            mahasiswa_aktif_per_prodi[kode_prodi] = set()

        mahasiswa_per_prodi[kode_prodi]["total_mahasiswa"] += 1

    for kegiatan in data.get("kegiatan", []):
        kode_prodi = str(kegiatan.get("kode_prodi") or "tanpa-prodi")
        nama_prodi = str(kegiatan.get("prodi") or "Belum ditentukan")
        nim = str(kegiatan.get("nim") or "").strip()
        nama_kegiatan = str(kegiatan.get("nama_kegiatan") or "Kegiatan lainnya")
        durasi_jam = float(kegiatan.get("durasi_jam", 0))
        total_jam_global += durasi_jam

        if kode_prodi not in mahasiswa_per_prodi:
            mahasiswa_per_prodi[kode_prodi] = {
                "kode_prodi": kode_prodi,
                "prodi": nama_prodi,
                "total_mahasiswa": 0,
                "mahasiswa_aktif": 0,
                "total_kegiatan": 0,
                "total_jam": 0.0,
            }
            mahasiswa_aktif_per_prodi[kode_prodi] = set()

        ringkasan_prodi = mahasiswa_per_prodi[kode_prodi]
        ringkasan_prodi["total_kegiatan"] += 1
        ringkasan_prodi["total_jam"] += durasi_jam

        if nim:
            mahasiswa_aktif_per_prodi[kode_prodi].add(nim)

        if nama_kegiatan not in kegiatan_per_nama:
            kegiatan_per_nama[nama_kegiatan] = {
                "nama_kegiatan": nama_kegiatan,
                "total_kegiatan": 0,
                "total_jam": 0.0,
            }

        kegiatan_per_nama[nama_kegiatan]["total_kegiatan"] += 1
        kegiatan_per_nama[nama_kegiatan]["total_jam"] += durasi_jam

    for kode_prodi, ringkasan in mahasiswa_per_prodi.items():
        aktif = len(mahasiswa_aktif_per_prodi[kode_prodi])
        total = int(ringkasan["total_mahasiswa"])
        ringkasan["mahasiswa_aktif"] = aktif
        ringkasan["persentase_aktif"] = round((aktif / total) * 100) if total else 0
        ringkasan["total_jam"] = round(float(ringkasan["total_jam"]), 1)

    prodi_list = sorted(mahasiswa_per_prodi.values(), key=lambda item: str(item["prodi"]).casefold())
    kegiatan_list = sorted(kegiatan_per_nama.values(), key=lambda item: str(item["nama_kegiatan"]).casefold())
    total_mahasiswa = sum(int(item["total_mahasiswa"]) for item in prodi_list)
    total_mahasiswa_aktif = sum(int(item["mahasiswa_aktif"]) for item in prodi_list)

    return {
        "summary": {
            "total_mahasiswa": total_mahasiswa,
            "mahasiswa_aktif": total_mahasiswa_aktif,
            "persentase_aktif": round((total_mahasiswa_aktif / total_mahasiswa) * 100) if total_mahasiswa else 0,
            "total_kegiatan": sum(int(item["total_kegiatan"]) for item in prodi_list),
            "total_jam": round(total_jam_global, 1),
        },
        "per_prodi": prodi_list,
        "per_kegiatan": kegiatan_list,
    }


def main() -> None:
    operation = sys.argv[1] if len(sys.argv) > 1 else "rekap-jam"
    data = json.load(sys.stdin)

    if operation == "rekap-jam":
        result = rekap_jam_kegiatan(data)
    elif operation == "analisis-progres":
        result = analisis_progres_kkn(data)
    else:
        raise ValueError("Operasi analisis tidak dikenal.")

    print(json.dumps(result, ensure_ascii=False))


if __name__ == "__main__":
    main()
