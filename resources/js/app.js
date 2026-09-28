import './bootstrap';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';


// Alpine lo carga y arranca Livewire 3 (@livewireScripts). No importarlo acá:
// con dos copias de Alpine se pisan y deja de existir $wire en los componentes.
import Swiper from 'swiper/bundle';
import Swal from 'sweetalert2';

window.Swal = Swal;
document.addEventListener('DOMContentLoaded', () => {
    new Swiper('.swiper', {
        loop: true,
        pagination: {
            el: '.swiper-pagination',
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
    });
});