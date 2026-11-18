import io
import json
import sys
from datetime import datetime
from xml.sax.saxutils import escape

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.platypus import (
    Paragraph,
    SimpleDocTemplate,
    Spacer,
    Table,
    TableStyle,
)


STATUS_LABELS = {
    "disetujui": "Disetujui",
    "menunggu": "Menunggu",
    "ditolak": "Ditolak",
    "draft": "Draft",
}

STATUS_COLORS = {
    "disetujui": colors.HexColor("#DCFCE7"),
    "menunggu": colors.HexColor("#DBEAFE"),
    "ditolak": colors.HexColor("#FEE2E2"),
    "draft": colors.HexColor("#FEF3C7"),
}


def safe_text(value: object, fallback: str = "-") -> str:
    text = str(value).strip() if value is not None else ""
    return escape(text if text else fallback).replace("\n", "<br/>")


def format_date(value: object) -> str:
    if not value:
        return "-"

    text = str(value).strip()

    try:
        return datetime.strptime(text[:10], "%Y-%m-%d").strftime("%d-%m-%Y")
    except ValueError:
        return text


def format_hours(value: object) -> str:
    try:
        hours = float(value or 0)
    except (TypeError, ValueError):
        hours = 0.0

    return f"{hours:,.1f}".replace(",", "_").replace(".", ",").replace("_", ".")


def format_time(activity: dict[str, object]) -> str:
    start = str(activity.get("jam_mulai") or "").strip()
    end = str(activity.get("jam_selesai") or "").strip()

    if start or end:
        return f"{start or '-'} - {end or '-'}"

    return str(activity.get("jam_kehadiran") or "-")


def generate_student_activity_pdf(data: dict[str, object]) -> bytes:
    student = data.get("mahasiswa") or {}
    activities = data.get("kegiatan") or []

    if not isinstance(student, dict) or not isinstance(activities, list):
        raise ValueError("Format data laporan mahasiswa tidak valid.")

    normalized_activities = []
    status_counts = {
        "disetujui": 0,
        "menunggu": 0,
        "ditolak": 0,
        "draft": 0,
    }

    total_hours = 0.0
    approved_hours = 0.0

    for activity in activities:
        if not isinstance(activity, dict):
            continue

        status = str(activity.get("status") or "draft").lower()

        try:
            duration = max(float(activity.get("durasi_jam") or 0), 0)
        except (TypeError, ValueError):
            duration = 0.0

        status_counts[status] = status_counts.get(status, 0) + 1
        total_hours += duration

        if status == "disetujui":
            approved_hours += duration

        normalized_activities.append({
            **activity,
            "status": status,
            "durasi_jam": duration,
        })

    buffer = io.BytesIO()

    document = SimpleDocTemplate(
        buffer,
        pagesize=A4,
        rightMargin=14 * mm,
        leftMargin=14 * mm,
        topMargin=15 * mm,
        bottomMargin=18 * mm,
        title="Laporan Kegiatan KKN Mahasiswa",
        author="KKN Tematik RESIK",
    )

    styles = getSampleStyleSheet()

    title_style = ParagraphStyle(
        "ReportTitle",
        parent=styles["Title"],
        fontName="Helvetica-Bold",
        fontSize=15,
        leading=19,
        alignment=TA_CENTER,
        textColor=colors.HexColor("#172554"),
        spaceAfter=4,
    )

    subtitle_style = ParagraphStyle(
        "ReportSubtitle",
        parent=styles["Normal"],
        fontName="Helvetica",
        fontSize=8,
        leading=11,
        alignment=TA_CENTER,
        textColor=colors.HexColor("#64748B"),
    )

    section_style = ParagraphStyle(
        "SectionTitle",
        parent=styles["Heading2"],
        fontName="Helvetica-Bold",
        fontSize=10,
        leading=13,
        textColor=colors.HexColor("#1E293B"),
        spaceBefore=9,
        spaceAfter=6,
    )

    body_style = ParagraphStyle(
        "ReportBody",
        parent=styles["BodyText"],
        fontName="Helvetica",
        fontSize=8,
        leading=11,
        textColor=colors.HexColor("#334155"),
    )

    small_style = ParagraphStyle(
        "ReportSmall",
        parent=body_style,
        fontSize=7,
        leading=9,
        textColor=colors.HexColor("#64748B"),
    )

    table_header_style = ParagraphStyle(
        "TableHeader",
        parent=body_style,
        fontName="Helvetica-Bold",
        fontSize=7,
        leading=9,
        textColor=colors.white,
    )

    table_body_style = ParagraphStyle(
        "TableBody",
        parent=body_style,
        fontSize=7,
        leading=9,
    )

    story = [
        Paragraph("LAPORAN KEGIATAN KKN MAHASISWA", title_style),
        Paragraph("KKN Tematik RESIK", subtitle_style),
        Spacer(1, 7 * mm),
    ]

    generated_date = datetime.now().strftime("%d-%m-%Y %H:%M")

    student_info = [
        [
            Paragraph("<b>Nama</b>", body_style),
            Paragraph(safe_text(student.get("nama")), body_style),
            Paragraph("<b>NIM</b>", body_style),
            Paragraph(safe_text(student.get("nim")), body_style),
        ],
        [
            Paragraph("<b>Program Studi</b>", body_style),
            Paragraph(safe_text(student.get("prodi")), body_style),
            Paragraph("<b>Tanggal Cetak</b>", body_style),
            Paragraph(generated_date, body_style),
        ],
    ]

    info_table = Table(
        student_info,
        colWidths=[23 * mm, 68 * mm, 18 * mm, 73 * mm],
    )

    info_table.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (0, -1), colors.HexColor("#F1F5F9")),
        ("BACKGROUND", (2, 0), (2, -1), colors.HexColor("#F1F5F9")),
        ("GRID", (0, 0), (-1, -1), 0.4, colors.HexColor("#CBD5E1")),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("LEFTPADDING", (0, 0), (-1, -1), 6),
        ("RIGHTPADDING", (0, 0), (-1, -1), 6),
        ("TOPPADDING", (0, 0), (-1, -1), 7),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 7),
    ]))

    story.extend([
        info_table,
        Paragraph("Ringkasan Kegiatan", section_style),
    ])

    summary_data = [
        [
            Paragraph("<b>Total Kegiatan</b>", body_style),
            Paragraph("<b>Disetujui</b>", body_style),
            Paragraph("<b>Menunggu</b>", body_style),
            Paragraph("<b>Ditolak</b>", body_style),
            Paragraph("<b>Draft</b>", body_style),
        ],
        [
            str(len(normalized_activities)),
            str(status_counts.get("disetujui", 0)),
            str(status_counts.get("menunggu", 0)),
            str(status_counts.get("ditolak", 0)),
            str(status_counts.get("draft", 0)),
        ],
    ]

    summary_table = Table(
        summary_data,
        colWidths=[36.4 * mm] * 5,
    )

    summary_table.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#E2E8F0")),
        ("TEXTCOLOR", (0, 0), (-1, -1), colors.HexColor("#1E293B")),
        ("GRID", (0, 0), (-1, -1), 0.4, colors.HexColor("#CBD5E1")),
        ("ALIGN", (0, 0), (-1, -1), "CENTER"),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("TOPPADDING", (0, 0), (-1, -1), 7),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 7),
    ]))

    story.extend([
        summary_table,
        Spacer(1, 3 * mm),
    ])

    hours_data = [
        [
            Paragraph("<b>Total Jam Tercatat</b>", body_style),
            Paragraph("<b>Total Jam Disetujui</b>", body_style),
        ],
        [
            Paragraph(f"{format_hours(total_hours)} jam", body_style),
            Paragraph(f"{format_hours(approved_hours)} jam", body_style),
        ],
    ]

    hours_table = Table(hours_data, colWidths=[91 * mm, 91 * mm])

    hours_table.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#F1F5F9")),
        ("GRID", (0, 0), (-1, -1), 0.4, colors.HexColor("#CBD5E1")),
        ("ALIGN", (0, 0), (-1, -1), "CENTER"),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("TOPPADDING", (0, 0), (-1, -1), 7),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 7),
    ]))

    story.extend([
        hours_table,
        Paragraph("Daftar Seluruh Kegiatan", section_style),
    ])

    table_data = [[
        Paragraph("No.", table_header_style),
        Paragraph("Tanggal", table_header_style),
        Paragraph("Kegiatan", table_header_style),
        Paragraph("Waktu", table_header_style),
        Paragraph("Jam", table_header_style),
        Paragraph("Status", table_header_style),
    ]]

    for index, activity in enumerate(normalized_activities, start=1):
        activity_name = safe_text(
            activity.get("nama_kegiatan"),
            "Kegiatan belum dipilih",
        )

        description = safe_text(activity.get("deskripsi_kegiatan"), "")
        location = safe_text(activity.get("lokasi"), "")

        details = []

        if description != "-":
            details.append(description)

        if location and location != "-":
            details.append(f"Lokasi: {location}")

        activity_content = f"<b>{activity_name}</b>"

        if details:
            activity_content += (
                "<br/><font size='6' color='#64748B'>"
                + "<br/>".join(details)
                + "</font>"
            )

        status = activity["status"]
        status_label = STATUS_LABELS.get(status, status.title())

        table_data.append([
            Paragraph(str(index), table_body_style),
            Paragraph(format_date(activity.get("tanggal")), table_body_style),
            Paragraph(activity_content, table_body_style),
            Paragraph(safe_text(format_time(activity)), table_body_style),
            Paragraph(format_hours(activity.get("durasi_jam")), table_body_style),
            Paragraph(safe_text(status_label), table_body_style),
        ])

    if len(table_data) == 1:
        story.append(
            Paragraph("Belum ada catatan kegiatan.", body_style)
        )
    else:
        activity_table = Table(
            table_data,
            colWidths=[
                9 * mm,
                24 * mm,
                64 * mm,
                34 * mm,
                17 * mm,
                34 * mm,
            ],
            repeatRows=1,
            hAlign="LEFT",
        )

        table_styles = [
            ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#1E3A8A")),
            ("GRID", (0, 0), (-1, -1), 0.4, colors.HexColor("#CBD5E1")),
            ("VALIGN", (0, 0), (-1, -1), "TOP"),
            ("ALIGN", (0, 0), (0, -1), "CENTER"),
            ("ALIGN", (4, 1), (4, -1), "CENTER"),
            ("LEFTPADDING", (0, 0), (-1, -1), 5),
            ("RIGHTPADDING", (0, 0), (-1, -1), 5),
            ("TOPPADDING", (0, 0), (-1, -1), 6),
            ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
        ]

        for row_index, activity in enumerate(normalized_activities, start=1):
            status = activity["status"]
            background = STATUS_COLORS.get(
                status,
                colors.white,
            )

            table_styles.append((
                "BACKGROUND",
                (5, row_index),
                (5, row_index),
                background,
            ))

        activity_table.setStyle(TableStyle(table_styles))
        story.append(activity_table)

    story.extend([
        Spacer(1, 5 * mm),
        Paragraph(
            "<b>Keterangan:</b> Total jam disetujui hanya menghitung "
            "kegiatan dengan status Disetujui. Kegiatan berstatus Draft, "
            "Menunggu, atau Ditolak tetap ditampilkan sebagai riwayat "
            "dan tidak dimasukkan ke total jam yang disetujui.",
            small_style,
        ),
    ])

    def draw_footer(canvas, doc):
        canvas.saveState()
        canvas.setStrokeColor(colors.HexColor("#CBD5E1"))
        canvas.setLineWidth(0.5)
        canvas.line(
            doc.leftMargin,
            12 * mm,
            A4[0] - doc.rightMargin,
            12 * mm,
        )
        canvas.setFont("Helvetica", 7)
        canvas.setFillColor(colors.HexColor("#64748B"))
        canvas.drawString(
            doc.leftMargin,
            8 * mm,
            "KKN Tematik RESIK - Laporan Kegiatan Mahasiswa",
        )
        canvas.drawRightString(
            A4[0] - doc.rightMargin,
            8 * mm,
            f"Halaman {doc.page}",
        )
        canvas.restoreState()

    document.build(
        story,
        onFirstPage=draw_footer,
        onLaterPages=draw_footer,
    )

    return buffer.getvalue()


def main() -> None:
    data = json.load(sys.stdin)
    pdf_content = generate_student_activity_pdf(data)
    sys.stdout.buffer.write(pdf_content)


if __name__ == "__main__":
    main()