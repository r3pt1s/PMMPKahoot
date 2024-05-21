<?php

namespace r3pt1s\kahoot\game\util;

class Utils {

    private static int $gameIdCounter = 0;

    public static function generateInvCode(int $length = 5): string {
        $characters = "1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $string = "";
        for ($i = 0; $i < $length; $i++) $string .= $characters[mt_rand(0, (strlen($characters) - 1))];
        return $string;
    }

    public static function nextGameId(): int {
        return self::$gameIdCounter++;
    }

    public static function calculatePoints(float $answerTime, int $timeLimit, int $maxPossiblePoints): int {
        if ($answerTime < 0.5) return $maxPossiblePoints;
        return round((1 - (($answerTime / $timeLimit) / 2)) * $maxPossiblePoints);
    }
}