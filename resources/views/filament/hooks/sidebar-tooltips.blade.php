<script>
    document.addEventListener('DOMContentLoaded', function() {
        initCustomTooltips();
    });
    
    // Untuk navigasi Livewire
    document.addEventListener('livewire:navigated', function() {
        initCustomTooltips();
    });
    
    function initCustomTooltips() {
        const tooltips = {
            'Epurcasing': 'Form untuk keperluan elektronik purchasing',
            'Non Tender': 'Form pengadaan barang/jasa tanpa proses tender',
            'Pencatatan Non Tender': 'Form untuk mencatat transaksi non tender',
            'Pencatatan Pengadaan Darurat': 'Form pencatatan untuk kondisi darurat atau mendesak',
            'Pencatatan Swakelola': 'Form pencatatan untuk pengerjaan swakelola',
            'Tender': 'Form dan proses tender pengadaan',
        };
        
        document.querySelectorAll('.fi-sidebar-item-button').forEach(button => {
            const labelElement = button.querySelector('.fi-sidebar-item-label');
            if (labelElement) {
                const menuName = labelElement.textContent.trim();
                
                if (tooltips[menuName]) {
                    // Set Alpine.js data
                    if (!button.hasAttribute('x-data')) {
                        button.setAttribute('x-data', '{ tooltipShow: false }');
                    }
                    button.setAttribute('@mouseenter', 'tooltipShow = true');
                    button.setAttribute('@mouseleave', 'tooltipShow = false');
                    
                    // Buat tooltip element jika belum ada
                    if (!button.querySelector('.custom-tooltip')) {
                        const tooltip = document.createElement('div');
                        tooltip.className = 'custom-tooltip';
                        tooltip.setAttribute('x-show', 'tooltipShow');
                        tooltip.setAttribute('x-transition', '');
                        tooltip.setAttribute('x-cloak', '');
                        tooltip.textContent = tooltips[menuName];
                        
                        button.style.position = 'relative';
                        button.appendChild(tooltip);
                    }
                }
            }
        });
    }
</script>

<style>
    [x-cloak] {
        display: none !important;
    }
    
    .custom-tooltip {
        position: absolute;
        left: calc(100% + 15px);
        top: 50%;
        transform: translateY(-50%);
        background-color: rgb(31, 41, 55);
        color: white;
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 13px;
        white-space: nowrap;
        z-index: 9999;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        pointer-events: none;
    }
    
    .custom-tooltip::before {
        content: '';
        position: absolute;
        right: 100%;
        top: 50%;
        transform: translateY(-50%);
        border: 8px solid transparent;
        border-right-color: rgb(31, 41, 55);
    }
    
    /* Dark mode support */
    .dark .custom-tooltip {
        background-color: rgb(55, 65, 81);
    }
    
    .dark .custom-tooltip::before {
        border-right-color: rgb(55, 65, 81);
    }
</style>