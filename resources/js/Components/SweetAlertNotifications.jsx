import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import Swal from 'sweetalert2';

export default function SweetAlertNotifications() {
    const { props } = usePage();
    const flash = props.flash || {};
    const errors = props.errors || {};
    const firstError = Object.values(errors).flat()[0];
    const lastNotice = useRef(null);

    useEffect(() => {
        const notice = flash.error
            ? { icon: 'error', title: 'Gagal', text: flash.error }
            : flash.success
                ? { icon: 'success', title: 'Berhasil', text: flash.success }
                : firstError
                    ? { icon: 'error', title: 'Periksa kembali data Anda', text: firstError }
                    : flash.status
                        ? { icon: 'success', title: 'Berhasil', text: flash.status }
                        : null;

        if (!notice) {
            lastNotice.current = null;
            return;
        }

        const noticeKey = `${notice.icon}:${notice.title}:${notice.text}`;
        if (lastNotice.current === noticeKey) {
            return;
        }

        lastNotice.current = noticeKey;
        Swal.fire({
            ...notice,
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#064e3b',
        });
    }, [flash.error, flash.success, flash.status, firstError]);

    return null;
}
