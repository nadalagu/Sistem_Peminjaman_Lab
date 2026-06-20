// ============================================================
//  assets/js/main.js
//  JavaScript umum aplikasi
// ============================================================

$(document).ready(function () {

    // Inisialisasi DataTable pada semua tabel yang pakai class .dt-table
    if ($.fn.DataTable) {
        $('.dt-table').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
            },
            pageLength: 10,
            responsive: true
        });
    }

    // Konfirmasi hapus dengan SweetAlert2
    $(document).on('click', '.btn-hapus', function (e) {
        e.preventDefault();
        const url = $(this).attr('href');
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: 'Data yang dihapus tidak bisa dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });

    // Tampilkan SweetAlert dari session (flash message)
    if (typeof flashMessage !== 'undefined') {
        Swal.fire({
            icon: flashMessage.type,
            title: flashMessage.title,
            text: flashMessage.text,
            timer: 2500,
            showConfirmButton: false
        });
    }

});

// Fungsi flash message (dipanggil dari PHP via inline script)
function showAlert(type, title, text) {
    Swal.fire({ icon: type, title: title, text: text,
        timer: 2500, showConfirmButton: false });
}