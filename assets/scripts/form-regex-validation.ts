/**
 * Universal real-time regex validation for form inputs
 * 
 * Automatically validates any input with a 'pattern' attribute.
 * Optional attributes:
 * - pattern: HTML5 regex pattern (required)
 * - data-validation-message: Custom error message (optional)
 * - data-transform: Transformation to apply ('uppercase', 'lowercase', 'capitalize') (optional)
 * 
 * Example:
 * <input type="text" 
 *        name="plate" 
 *        pattern="[A-Z]{2}-[0-9]{3}-[A-Z]{2}"
 *        data-validation-message="Format invalide. Attendu : XX-123-XX"
 *        data-transform="uppercase">
 */
document.addEventListener('DOMContentLoaded', (): void => {
    const inputs = document.querySelectorAll<HTMLInputElement>('input[pattern]');
    
    inputs.forEach((input: HTMLInputElement): void => {
        const pattern = input.getAttribute('pattern');
        if (!pattern) return;

        const regex = new RegExp(`^${pattern}$`);
        const customMessage = input.getAttribute('data-validation-message') || 'Format invalide';
        const transform = input.getAttribute('data-transform');

        input.addEventListener('input', (event: Event): void => {
            const target = event.target as HTMLInputElement;
            let value = target.value;

            // Apply transformation if specified
            if (transform === 'uppercase') {
                value = value.toUpperCase();
                target.value = value;
            } else if (transform === 'lowercase') {
                value = value.toLowerCase();
                target.value = value;
            } else if (transform === 'capitalize') {
                value = value.charAt(0).toUpperCase() + value.slice(1).toLowerCase();
                target.value = value;
            }

            // Validate format
            if (value && !regex.test(value)) {
                target.setCustomValidity(customMessage);
                target.classList.add('is-invalid');
                target.classList.remove('is-valid');
            } else {
                target.setCustomValidity('');
                target.classList.remove('is-invalid');
                if (value) {
                    target.classList.add('is-valid');
                }
            }
        });
    });
});