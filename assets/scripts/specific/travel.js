"use strict";
const DELAY_MS = 100;
// On page load, if all required inputs are pre-filled, trigger HTMX search
document.addEventListener('DOMContentLoaded', function () {
    // Wait for HTMX initialization and inputs to be set from Controller
    setTimeout(() => {
        const form = document.getElementById('travel-search-bar'); // templates/components/TravelSearchBar.html.twig
        // @ts-expect-error htmx is loaded globally
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
                console.log('Manual HTMX search with pre-filled data'); // Used for testing
                // // @ts-expect-error htmx is loaded globally
                // if (window.htmx.trigger) {
                //     If this works, I can remove the manual ajax call below
                //     https://github.com/bigskysoftware/htmx/issues/505
                //     https://github.com/bigskysoftware/htmx/blob/master/www/content/attributes/hx-trigger.md
                //     https://github.com/bigskysoftware/htmx/blob/master/www/content/api.md#method---htmxtrigger-trigger
                //     // @ts-expect-error htmx is loaded globally
                //     window.htmx.trigger('#travel-search-bar', 'pageLoad', {from: 'input'});
                //     return;
                // }
                // htmx.trigger() not working here, so doing manual ajax call
                const htmxUrl = form.getAttribute('hx-get');
                const htmxTarget = form.getAttribute('hx-target');
                if (htmxUrl && htmxTarget) { // Always true
                    const formData = new FormData(form);
                    const searchParams = new URLSearchParams();
                    formData.forEach((value, key) => {
                        searchParams.append(key, value.toString());
                    });
                    const fullUrl = `${htmxUrl}?${searchParams.toString()}`;
                    // manual HTMX request
                    // @ts-expect-error htmx is loaded globally
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
//# sourceMappingURL=travel.js.map