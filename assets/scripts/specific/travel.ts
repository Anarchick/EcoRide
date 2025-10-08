const DELAY_MS: number = 100;

// On page load, if all required inputs are pre-filled, trigger HTMX search
document.addEventListener('DOMContentLoaded', function (): void {
    // Wait for HTMX initialization and inputs to be set from Controller
    setTimeout((): void => {
        const form: HTMLFormElement | null = document.getElementById('travel-search-bar') as HTMLFormElement; // templates/components/TravelSearchBar.html.twig

        // @ts-expect-error htmx is loaded globally
        if (form && window.htmx) {
            const departure: HTMLInputElement | null = form.querySelector('input[name="travel_search[departure]"]');
            const arrival: HTMLInputElement | null = form.querySelector('input[name="travel_search[arrival]"]');
            const date: HTMLInputElement | null = form.querySelector('input[name="travel_search[date]"]');
            const passengers: HTMLInputElement | null = form.querySelector('input[name="travel_search[minPassengers]"]');
            
            const allRequiredFilled: boolean = Boolean(
                departure?.value.trim() && 
                arrival?.value.trim() && 
                date?.value.trim() && 
                passengers?.value.trim()
            );
            
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
                const htmxUrl: string | null = form.getAttribute('hx-get');
                const htmxTarget: string | null = form.getAttribute('hx-target');
                
                if (htmxUrl && htmxTarget) { // Always true
                    
                    const formData: FormData = new FormData(form);
                    const searchParams: URLSearchParams = new URLSearchParams();

                    formData.forEach((value: FormDataEntryValue, key: string): void => {
                        searchParams.append(key, value.toString());
                    });
                    
                    const fullUrl: string = `${htmxUrl}?${searchParams.toString()}`;
                    
                    // manual HTMX request
                    // @ts-expect-error htmx is loaded globally
                    window.htmx.ajax('GET', fullUrl, {
                        target: htmxTarget,
                        swap: 'innerHTML'
                    });
                    
                    const pushUrl: string | null = form.getAttribute('hx-push-url');
                    if (pushUrl === 'true') {
                        window.history.pushState({}, '', fullUrl);
                    }
                }
            }
        }
    }, DELAY_MS);
});