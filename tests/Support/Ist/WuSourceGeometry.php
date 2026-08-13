<?php

declare(strict_types=1);

namespace Tests\Support\Ist;

final class WuSourceGeometry
{
    private const FACE_NORMALS = [
        'U' => [0, 0, 1],
        'F' => [0, 1, 0],
        'R' => [1, 0, 0],
    ];

    private const CORNERS = [
        'U' => [
            'B' => [-1, -1, 1], 'R' => [1, -1, 1],
            'F' => [1, 1, 1], 'L' => [-1, 1, 1],
        ],
        'F' => [
            'TL' => [-1, 1, 1], 'TR' => [1, 1, 1],
            'BR' => [1, 1, -1], 'BL' => [-1, 1, -1],
        ],
        'R' => [
            'TL' => [1, 1, 1], 'TR' => [1, -1, 1],
            'BR' => [1, -1, -1], 'BL' => [1, 1, -1],
        ],
    ];

    public static function audit(): array
    {
        $masters = [
            'A' => self::observation(self::x('U'), self::diagonal('F', 'BL'), self::dot('R', 'BR')),
            'B' => self::observation(self::dot('U', 'L'), self::checker('F', 'TR', 'BL'), self::diagonal('R', 'BR')),
            'C' => self::observation(self::dot('U', 'L'), self::diagonal('F', 'BR'), self::x('R')),
            'D' => self::observation(self::x('U'), self::checker('F', 'TL', 'BR'), self::diagonal('R', 'BR')),
            'E' => self::observation(self::dot('U', 'R'), self::checker('F', 'TL', 'BR'), self::x('R')),
        ];

        $targets = [
            137 => self::observation(self::dot('U', 'B'), self::x('F'), self::diagonal('R', 'BR')),
            138 => self::observation(self::x('U'), self::dot('F', 'BR'), self::diagonal('R', 'TR')),
            139 => self::observation(self::checker('U', 'L', 'R'), self::diagonal('F', 'BL'), self::x('R')),
            140 => self::observation(self::x('U'), self::dot('F', 'TL'), self::checker('R', 'TR', 'BL')),
            141 => self::observation(self::checker('U', 'B', 'F'), self::dot('F', 'TL'), self::diagonal('R', 'TR')),
            142 => self::observation(self::diagonal('U', 'L'), self::x('F'), self::dot('R', 'TR')),
            143 => self::observation(self::diagonal('U', 'B'), self::x('F'), self::checker('R', 'TR', 'BL')),
            144 => self::observation(self::dot('U', 'F'), self::checker('F', 'TL', 'BR'), self::diagonal('R', 'BR')),
            145 => self::observation(self::checker('U', 'L', 'R'), self::x('F'), self::dot('R', 'BL')),
            146 => self::observation(self::diagonal('U', 'B'), self::dot('F', 'BL'), self::x('R')),
            147 => self::observation(self::checker('U', 'B', 'F'), self::diagonal('F', 'BL'), self::dot('R', 'TR')),
            148 => self::observation(self::dot('U', 'R'), self::diagonal('F', 'TL'), self::checker('R', 'TL', 'BR')),
        ];

        $proper = self::transforms(1);
        $reflections = self::transforms(-1);
        $result = [];

        foreach ($targets as $source => $target) {
            $properMatches = [];
            $reflectionMatches = [];

            foreach ($masters as $key => $master) {
                $properCount = self::matchCount($master, $target, $proper);
                $reflectionCount = self::matchCount($master, $target, $reflections);

                if ($properCount > 0) {
                    $properMatches[$key] = $properCount;
                }

                if ($reflectionCount > 0) {
                    $reflectionMatches[$key] = $reflectionCount;
                }
            }

            $result[$source] = [
                'proper_matches' => $properMatches,
                'reflection_matches' => $reflectionMatches,
                'proper_rotations_checked' => count($proper),
                'reflections_checked' => count($reflections),
            ];
        }

        return $result;
    }

    private static function observation(array $up, array $front, array $right): array
    {
        return ['U' => $up, 'F' => $front, 'R' => $right];
    }

    private static function x(string $face): array
    {
        return self::decoration($face, 'x');
    }

    private static function dot(string $face, string $corner): array
    {
        return self::decoration($face, 'dot', [$corner]);
    }

    private static function diagonal(string $face, string $darkCorner): array
    {
        return self::decoration($face, 'diagonal', [$darkCorner]);
    }

    private static function checker(string $face, string $first, string $second): array
    {
        return self::decoration($face, 'checker', [$first, $second]);
    }

    private static function decoration(string $face, string $type, array $markers = []): array
    {
        $vectors = array_map(
            static fn (string $marker): array => self::CORNERS[$face][$marker],
            $markers,
        );
        usort($vectors, static fn (array $a, array $b): int => $a <=> $b);

        return ['type' => $type, 'markers' => $vectors];
    }

    private static function transforms(int $expectedDeterminant): array
    {
        $permutations = [
            [0, 1, 2], [0, 2, 1], [1, 0, 2],
            [1, 2, 0], [2, 0, 1], [2, 1, 0],
        ];
        $matrices = [];

        foreach ($permutations as $permutation) {
            foreach ([-1, 1] as $x) {
                foreach ([-1, 1] as $y) {
                    foreach ([-1, 1] as $z) {
                        $signs = [$x, $y, $z];
                        $matrix = [[0, 0, 0], [0, 0, 0], [0, 0, 0]];

                        foreach ($permutation as $row => $column) {
                            $matrix[$row][$column] = $signs[$row];
                        }

                        if (self::determinant($matrix) === $expectedDeterminant) {
                            $matrices[] = $matrix;
                        }
                    }
                }
            }
        }

        return $matrices;
    }

    private static function matchCount(array $master, array $target, array $transforms): int
    {
        $knownFaces = [];
        $symbolFaces = [];

        foreach ($master as $face => $decoration) {
            $normal = self::FACE_NORMALS[$face];
            $normalKey = implode(',', $normal);
            $knownFaces[$normalKey] = $decoration;
            $symbolFaces[$decoration['type']] = $normal;
        }

        $matches = 0;

        foreach ($transforms as $transform) {
            $inverse = self::transpose($transform);
            $compatible = true;

            foreach ($target as $face => $decoration) {
                $baseNormal = self::multiply($inverse, self::FACE_NORMALS[$face]);
                $normalKey = implode(',', $baseNormal);
                $baseMarkers = array_map(
                    static fn (array $marker): array => self::multiply($inverse, $marker),
                    $decoration['markers'],
                );
                usort($baseMarkers, static fn (array $a, array $b): int => $a <=> $b);
                $baseDecoration = ['type' => $decoration['type'], 'markers' => $baseMarkers];

                if (isset($symbolFaces[$decoration['type']])
                    && $baseNormal !== $symbolFaces[$decoration['type']]) {
                    $compatible = false;
                    break;
                }

                if (isset($knownFaces[$normalKey]) && $baseDecoration !== $knownFaces[$normalKey]) {
                    $compatible = false;
                    break;
                }
            }

            if ($compatible) {
                $matches++;
            }
        }

        return $matches;
    }

    private static function determinant(array $matrix): int
    {
        return $matrix[0][0] * ($matrix[1][1] * $matrix[2][2] - $matrix[1][2] * $matrix[2][1])
            - $matrix[0][1] * ($matrix[1][0] * $matrix[2][2] - $matrix[1][2] * $matrix[2][0])
            + $matrix[0][2] * ($matrix[1][0] * $matrix[2][1] - $matrix[1][1] * $matrix[2][0]);
    }

    private static function transpose(array $matrix): array
    {
        return [
            [$matrix[0][0], $matrix[1][0], $matrix[2][0]],
            [$matrix[0][1], $matrix[1][1], $matrix[2][1]],
            [$matrix[0][2], $matrix[1][2], $matrix[2][2]],
        ];
    }

    private static function multiply(array $matrix, array $vector): array
    {
        return array_map(
            static fn (array $row): int => $row[0] * $vector[0] + $row[1] * $vector[1] + $row[2] * $vector[2],
            $matrix,
        );
    }
}

