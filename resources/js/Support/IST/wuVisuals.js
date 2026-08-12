import masterAUrl from '../../../../database/data/ist-final-staging/media/wu/examples/wu-example-001-option-a.svg?url';
import masterBUrl from '../../../../database/data/ist-final-staging/media/wu/examples/wu-example-001-option-b.svg?url';
import masterCUrl from '../../../../database/data/ist-final-staging/media/wu/examples/wu-example-001-option-c.svg?url';
import masterDUrl from '../../../../database/data/ist-final-staging/media/wu/examples/wu-example-001-option-d.svg?url';
import masterEUrl from '../../../../database/data/ist-final-staging/media/wu/examples/wu-example-001-option-e.svg?url';
import exampleTargetUrl from '../../../../database/data/ist-final-staging/media/wu/examples/wu-example-001-reference.svg?url';
import target001Url from '../../../../database/data/ist-final-staging/media/wu/questions/wu-q001-reference.svg?url';
import target002Url from '../../../../database/data/ist-final-staging/media/wu/questions/wu-q002-reference.svg?url';
import target003Url from '../../../../database/data/ist-final-staging/media/wu/questions/wu-q003-reference.svg?url';
import target004Url from '../../../../database/data/ist-final-staging/media/wu/questions/wu-q004-reference.svg?url';
import target005Url from '../../../../database/data/ist-final-staging/media/wu/questions/wu-q005-reference.svg?url';
import target006Url from '../../../../database/data/ist-final-staging/media/wu/questions/wu-q006-reference.svg?url';
import target007Url from '../../../../database/data/ist-final-staging/media/wu/questions/wu-q007-reference.svg?url';
import target008Url from '../../../../database/data/ist-final-staging/media/wu/questions/wu-q008-reference.svg?url';
import target009Url from '../../../../database/data/ist-final-staging/media/wu/questions/wu-q009-reference.svg?url';
import target010Url from '../../../../database/data/ist-final-staging/media/wu/questions/wu-q010-reference.svg?url';
import target011Url from '../../../../database/data/ist-final-staging/media/wu/questions/wu-q011-reference.svg?url';
import target012Url from '../../../../database/data/ist-final-staging/media/wu/questions/wu-q012-reference.svg?url';

export const WU_INSTRUCTION_CONTENT = [
    'Perhatikan lima kubus acuan A–E.',
    'Pada setiap soal hanya ditampilkan satu kubus target di area utama.',
    'Bandingkan tiga simbol pada sisi atas, depan, dan kanan yang terlihat.',
    'Pilih tepat satu kubus acuan A, B, C, D, atau E yang identik setelah rotasi legal.',
    'Kubus boleh diputar, tetapi tidak boleh dicerminkan.',
].join('\n');

export const WU_EXAMPLE_PROMPT =
    'Perhatikan satu kubus target contoh, kemudian pilih kubus acuan A–E yang identik setelah rotasi legal.';

export const WU_QUESTION_PROMPT =
    'Perhatikan satu kubus target. Pilih kubus acuan A–E yang identik setelah rotasi legal.';

const masterUrls = {
    A: masterAUrl,
    B: masterBUrl,
    C: masterCUrl,
    D: masterDUrl,
    E: masterEUrl,
};

const targetUrls = {
    1: target001Url,
    2: target002Url,
    3: target003Url,
    4: target004Url,
    5: target005Url,
    6: target006Url,
    7: target007Url,
    8: target008Url,
    9: target009Url,
    10: target010Url,
    11: target011Url,
    12: target012Url,
};

function image(url, alt) {
    return { url, alt };
}

export function wuMasterImage(optionKey) {
    const key = String(optionKey ?? '').toUpperCase();

    return image(masterUrls[key] ?? null, `Kubus acuan ${key || '?'}`);
}

export function wuExampleTargetImage() {
    return image(exampleTargetUrl, 'Satu kubus target contoh WU');
}

export function wuQuestionTargetImage(displayOrder) {
    const order = Number(displayOrder);

    return image(targetUrls[order] ?? null, `Satu kubus target soal WU nomor ${order || '?'}`);
}
