<?php
declare(strict_types=1);

final class EloCalculator {
    public static function expected(float $ra, float $rb): float {
        return 1.0 / (1.0 + pow(10.0, ($rb - $ra) / 400.0));
    }

    public static function rate(int $userElo, int $taskElo, bool $isSolved, int $kUser = 24, int $kTask = 8): array {
        $expectedUser = self::expected($userElo, $taskElo);
        $scoreUser = $isSolved ? 1.0 : 0.0;
        $newUser = (int) round($userElo + $kUser * ($scoreUser - $expectedUser));

        $expectedTask = self::expected($taskElo, $userElo);
        $scoreTask = $isSolved ? 0.0 : 1.0;
        $newTask = (int) round($taskElo + $kTask * ($scoreTask - $expectedTask));

        return [
            'user_before' => $userElo,
            'user_after' => max(100, $newUser),
            'task_before' => $taskElo,
            'task_after' => max(500, $newTask),
            'delta_user' => $newUser - $userElo,
        ];
    }
}
