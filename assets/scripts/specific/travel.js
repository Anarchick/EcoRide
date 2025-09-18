"use strict";
const DELAY_MS = 100;
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(() => {
        const form = document.getElementById('travel-search-bar');
        if (form && window.htmx) {
            const departure = form.querySelector('input[name="travel_search[departure]"]');
            const arrival = form.querySelector('input[name="travel_search[arrival]"]');
            const date = form.querySelector('input[name="travel_search[date]"]');
            const passengers = form.querySelector('input[name="travel_search[passengersMin]"]');
            const allRequiredFilled = Boolean(departure?.value.trim() &&
                arrival?.value.trim() &&
                date?.value.trim() &&
                passengers?.value.trim());
            if (allRequiredFilled) {
                console.log('Manual HTMX search with pre-filled data');
                const htmxUrl = form.getAttribute('hx-get');
                const htmxTarget = form.getAttribute('hx-target');
                if (htmxUrl && htmxTarget) {
                    const formData = new FormData(form);
                    const searchParams = new URLSearchParams();
                    formData.forEach((value, key) => {
                        searchParams.append(key, value.toString());
                    });
                    const fullUrl = `${htmxUrl}?${searchParams.toString()}`;
                    window.htmx.ajax('GET', fullUrl, {
                        target: htmxTarget,
                        swap: 'innerHTML'
                    });
                    const pushUrl = form.getAttribute('hx-push-url');
                    if (pushUrl === 'true') {
                        window.history.pushState({}, '', fullUrl);
                    }
                }
            }
        }
    }, DELAY_MS);
});
