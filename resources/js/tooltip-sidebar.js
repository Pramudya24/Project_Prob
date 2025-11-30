document.addEventListener('DOMContentLoaded', function() {
    initSidebarTooltips();
});

// Untuk Filament yang menggunakan Livewire
document.addEventListener('livewire:navigated', function() {
    initSidebarTooltips();
});

function initSidebarTooltips() {
    // Data tooltip untuk setiap menu
    const tooltips = {
        'Epurcasing': 'Form untuk keperluan elektronik purchasing',
        'Non Tender': 'Form pengadaan barang/jasa tanpa proses tender',
        'Pencatatan Non Tender': 'Form untuk mencatat transaksi non tender',
        'Pencatatan Pengadaan Darurat': 'Form pencatatan untuk kondisi darurat',
        'Pencatatan Swakelola': 'Form pencatatan untuk pengerjaan swakelola',
        'Tender': 'Form dan proses tender pengadaan',
    };

    // Loop semua menu sidebar
    document.querySelectorAll('.fi-sidebar-item-button').forEach(button => {
        const label = button.querySelector('.fi-sidebar-item-label');
        if (label) {
            const menuName = label.textContent.trim();
            if (tooltips[menuName]) {
                // Tambahkan tooltip
                button.setAttribute('data-tippy-content', tooltips[menuName]);
            }
        }
    });

    // Initialize Tippy
    if (typeof tippy !== 'undefined') {
        tippy('[data-tippy-content]', {
            placement: 'right',
            arrow: true,
            theme: 'sivera',
            delay: [300, 0],
        });
    }
}