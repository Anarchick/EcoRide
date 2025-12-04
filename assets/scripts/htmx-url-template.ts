/**
 * Universal HTMX Dynamic URL Template Handler
 *
 * Enables dynamic URL generation for HTMX requests using templates.
 * Works with any element that has the following attributes:
 * - data-hx-url-template: URL template with {value} placeholder
 * - data-hx-target: Target element for the response
 * - data-hx-swap: (optional) Swap strategy, defaults to 'innerHTML'
 * - data-hx-trigger-event: (optional) Event to listen to, defaults to 'change'
 *
 * Example:
 * <select data-hx-url-template="/api/items/{value}"
 *         data-hx-target="#results"
 *         data-hx-swap="innerHTML"
 *         data-hx-trigger-event="change">
 * </select>
 */

declare const htmx: {
    ajax: (method: string, url: string, options: { target: string; swap: string }) => void;
};

document.addEventListener('DOMContentLoaded', (): void => {
    const elements = document.querySelectorAll<HTMLElement>('[data-hx-url-template]');
    
    elements.forEach((element: HTMLElement): void => {
        const urlTemplate = element.getAttribute('data-hx-url-template');
        const target = element.getAttribute('data-hx-target');
        const swap = element.getAttribute('data-hx-swap') || 'innerHTML';
        const triggerEvent = element.getAttribute('data-hx-trigger-event') || 'change';
        
        if (!urlTemplate || !target) {
            console.warn('Element with data-hx-url-template must also have data-hx-target', element);
            return;
        }
        
        element.addEventListener(triggerEvent, (event: Event): void => {
            const value = (event.target as HTMLInputElement | HTMLSelectElement).value;
            
            if (!value) {
                return;
            }
            
            // Replace {value} placeholder with actual value
            const url = urlTemplate.replace('{value}', value);
            
            // Trigger HTMX request
            htmx.ajax('GET', url, {
                target: target,
                swap: swap
            });
        });
    });
});
