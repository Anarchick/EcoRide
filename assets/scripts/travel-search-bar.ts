// Update the price and duration text when the sliders are moved
document.addEventListener('DOMContentLoaded', function (): void {
    const priceText: HTMLParagraphElement | null = document.getElementById('filter-price-text') as HTMLParagraphElement;
    const priceInput: HTMLInputElement | null = document.getElementById('filter-price-input') as HTMLInputElement;
    const durationText: HTMLParagraphElement | null = document.getElementById('filter-duration-text') as HTMLParagraphElement;
    const durationInput: HTMLInputElement | null = document.getElementById('filter-duration-input') as HTMLInputElement;

    if (priceText && priceInput) {
        priceInput.addEventListener('input', function (): void {
            const priceValue: number = parseInt(priceInput.value) * 10;
            priceText.textContent = priceValue.toString();
        });
    }

    if (durationText && durationInput) {
        durationInput.addEventListener('input', function (): void {
            const durationValue: number = parseInt(durationInput.value);
            durationText.textContent = durationValue.toString() + 'h';
        });
    }
});