<?php

namespace App\Support\Ist;

use App\Models\Ist\IstSubtest;
use DomainException;

final class IstMeRuntimeContent
{
    public const MODEL = 'initial_letter_to_category';

    public const MEMORIZATION_SECONDS = 120;

    public const ANSWERING_SECONDS = 240;

    private const GROUP_KEYS = ['A', 'B', 'C', 'D', 'E'];

    /**
     * Build the participant-safe ME material that is copied into every new
     * question snapshot. Internal target words and answer metadata never enter
     * this structure.
     */
    public function fromMaster(IstSubtest $subtest): array
    {
        if (strtoupper((string) $subtest->code) !== 'ME') {
            throw new DomainException('IST ME runtime content requires the ME subtest.');
        }

        if ((int) $subtest->memorization_seconds !== self::MEMORIZATION_SECONDS
            || (int) $subtest->answering_seconds !== self::ANSWERING_SECONDS) {
            throw new DomainException('IST ME runtime timer contract is invalid.');
        }

        $instruction = trim((string) $subtest->instruction_content);

        if ($instruction === '' || preg_match('/\bpasangan\b/ui', $instruction) === 1) {
            throw new DomainException('IST ME runtime instruction uses an invalid mechanism.');
        }

        try {
            $decoded = json_decode(
                (string) $subtest->memorization_content,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (\Throwable) {
            throw new DomainException('IST ME runtime memorization content is invalid.');
        }

        if (! is_array($decoded)
            || array_keys($decoded) !== ['groups']
            || ! is_array($decoded['groups'])
            || count($decoded['groups']) !== 5) {
            throw new DomainException('IST ME runtime requires exactly five groups.');
        }

        $groups = [];
        $initials = [];

        foreach ($decoded['groups'] as $index => $group) {
            $expectedKey = self::GROUP_KEYS[$index];

            if (! is_array($group)
                || ! is_string($group['key'] ?? null)
                || ! is_string($group['name'] ?? null)
                || ! is_array($group['words'] ?? null)
                || count($group['words']) !== 5
                || strtoupper(trim($group['key'])) !== $expectedKey
                || ($group['display_order'] ?? null) !== $index + 1
                || trim($group['name']) === '') {
                throw new DomainException('IST ME runtime group definition is invalid.');
            }

            $words = [];

            foreach ($group['words'] as $word) {
                if (! is_string($word) || trim($word) === '') {
                    throw new DomainException('IST ME runtime group word is invalid.');
                }

                $word = trim($word);
                $initial = mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8');

                if (isset($initials[$initial])) {
                    throw new DomainException('IST ME runtime word initials must be unique.');
                }

                $initials[$initial] = true;
                $words[] = $word;
            }

            $groups[] = [
                'key' => $expectedKey,
                'name' => trim($group['name']),
                'words' => $words,
                'displayOrder' => $index + 1,
            ];
        }

        if (count($initials) !== 25) {
            throw new DomainException('IST ME runtime requires 25 unique initials.');
        }

        return [
            'model' => self::MODEL,
            'instructionContent' => $instruction,
            'groups' => $groups,
        ];
    }

    /**
     * Read an already-created participant-safe snapshot without falling back
     * to mutable master content or the legacy paired-associate format.
     */
    public function fromQuestionSnapshot(mixed $snapshot): ?array
    {
        if (! is_array($snapshot)
            || ! is_array($snapshot['me_runtime'] ?? null)) {
            return null;
        }

        $runtime = $snapshot['me_runtime'];

        if (($runtime['model'] ?? null) !== self::MODEL
            || ! is_string($runtime['instructionContent'] ?? null)
            || trim($runtime['instructionContent']) === ''
            || preg_match('/\bpasangan\b/ui', $runtime['instructionContent']) === 1
            || ! is_array($runtime['groups'] ?? null)
            || count($runtime['groups']) !== 5) {
            return null;
        }

        foreach ($runtime['groups'] as $index => $group) {
            if (! is_array($group)
                || ($group['key'] ?? null) !== self::GROUP_KEYS[$index]
                || ! is_string($group['name'] ?? null)
                || ! is_array($group['words'] ?? null)
                || count($group['words']) !== 5
                || ($group['displayOrder'] ?? null) !== $index + 1) {
                return null;
            }

            foreach ($group['words'] as $word) {
                if (! is_string($word) || trim($word) === '') {
                    return null;
                }
            }
        }

        return $runtime;
    }
}
