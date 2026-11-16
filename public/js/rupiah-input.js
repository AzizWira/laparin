function formatRupiahInput(input) {
    const current = input.value;
    const cursor = input.selectionStart ?? current.length;
    const rawDigits = current.replace(/[^0-9]/g, '');
    const digits = rawDigits.replace(/^0+(?=[0-9])/, '');
    const formatted = digits.replace(/\B(?=(?:[0-9]{3})+(?![0-9]))/g, '.');

    if (formatted === current) {
        return;
    }

    const removedZeros = rawDigits.length - digits.length;
    const digitsBeforeCursor = Math.max(0, current.slice(0, cursor).replace(/[^0-9]/g, '').length - removedZeros);
    input.value = formatted;

    if (cursor < current.length) {
        let position = 0;
        let digitsSeen = 0;
        while (position < formatted.length && digitsSeen < digitsBeforeCursor) {
            if (formatted[position] !== '.') {
                digitsSeen++;
            }
            position++;
        }
        input.setSelectionRange(position, position);
    }
}

for (const input of document.querySelectorAll('input[data-rupiah]')) {
    formatRupiahInput(input);
    input.addEventListener('input', () => formatRupiahInput(input));
}
