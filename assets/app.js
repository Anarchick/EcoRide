import './bootstrap.js';
import'./layout_guide.js';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
import './styles/app.css';
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