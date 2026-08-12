const exampleOptions = import.meta.glob(
    '../../../../database/data/ist-final-staging/media/fa/examples/*-option-*.svg',
    { eager: true, query: '?url', import: 'default' },
);

const scoredOptions = import.meta.glob(
    '../../../../database/data/ist-final-staging/media/fa/options/*.svg',
    { eager: true, query: '?url', import: 'default' },
);

function optionImage(url, optionKey) {
    const key = String(optionKey ?? '').toUpperCase();

    return {
        url: url ?? null,
        alt: `Pilihan bentuk FA ${key || '?'}`,
    };
}

export function faExampleOptionImage(optionKey) {
    const key = String(optionKey ?? '').toLowerCase();
    const path = `../../../../database/data/ist-final-staging/media/fa/examples/fa-example-001-option-${key}.svg`;

    return optionImage(exampleOptions[path], optionKey);
}

export function faQuestionOptionImage(displayOrder, optionKey) {
    const order = String(Number(displayOrder) || 0).padStart(3, '0');
    const key = String(optionKey ?? '').toLowerCase();
    const path = `../../../../database/data/ist-final-staging/media/fa/options/fa-q${order}-option-${key}.svg`;

    return optionImage(scoredOptions[path], optionKey);
}
