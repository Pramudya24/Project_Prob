import './bootstrap';
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
import './tooltip-sidebar'; 

// Inisialisasi Tippy
document.addEventListener('DOMContentLoaded', function() {
    initTippy();
});

// Untuk Livewire
document.addEventListener('livewire:navigated', function() {
    initTippy();
});

// Jika menggunakan Livewire v3
Livewire.hook('morph.added', ({ el }) => {
    initTippy();
});

function initTippy() {
    tippy('[data-tippy-content]', {
        placement: 'right',
        arrow: true,
        animation: 'fade',
        theme: 'sivera',
        delay: [200, 0],
    });
}