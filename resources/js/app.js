import './bootstrap';

import Alpine from 'alpinejs';
import 'flowbite';

window.Alpine = Alpine;

// Máscara monetária global reutilizável (R$ X.XXX,XX)
// Uso: x-on:input="raw = formatCurrency($event)"
// Retorna o valor numérico float para armazenar em campo hidden
window.formatCurrency = function(event) {
    let digits = event.target.value.replace(/\D/g, '');
    let number = (parseInt(digits || '0') / 100).toFixed(2);
    event.target.value = 'R$ ' + number
        .replace('.', ',')
        .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return parseFloat(number);
};

Alpine.start();
