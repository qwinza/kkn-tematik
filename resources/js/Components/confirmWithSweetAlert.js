import Swal from 'sweetalert2';

export default async function confirmWithSweetAlert({
    title,
    text,
    confirmButtonText = 'Ya, lanjutkan',
    icon = 'warning',
}) {
    const result = await Swal.fire({
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText: 'Batal',
        confirmButtonColor: icon === 'question' ? '#064e3b' : '#b91c1c',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });

    return result.isConfirmed;
}
