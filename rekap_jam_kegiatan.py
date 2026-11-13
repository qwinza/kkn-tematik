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


def main() -> None:
    daftar_kegiatan = json.load(sys.stdin)
    print(json.dumps(rekap_jam_kegiatan(daftar_kegiatan), ensure_ascii=False))


if __name__ == "__main__":
    main()
