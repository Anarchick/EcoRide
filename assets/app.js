import './bootstrap.js';
import'./layout_guide.js';
import 'bootstrap/dist/css/bootstrap.min.css';
import { Tooltip } from 'bootstrap';
import './styles/app.css';
import './scripts/travel-search-bar.js'
import './styles/home/home.css';
import htmx from 'htmx.org';

window.htmx = htmx;

// Make HTMX work with Symfony\ux-turbo
document.addEventListener("turbo:load", function() {
    if (window.htmx) {
        htmx.process(document.body);
    }
});

/*
document.addEventListener('htmx:afterSwap', (event) => {
    if (window.htmx) {
        htmx.process(document.body);
    }
});
*/

// Initialize Bootstrap tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new Tooltip(tooltipTriggerEl);
    });
});