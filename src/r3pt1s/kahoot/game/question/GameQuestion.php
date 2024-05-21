<?php

namespace r3pt1s\kahoot\game\question;

use pocketmine\form\Form;
use r3pt1s\kahoot\game\KahootGame;

interface GameQuestion {

    public function buildForm(KahootGame $game, bool $isHost): Form;

    public function isAnswerCorrect(mixed $answer): bool;

    public function setQuestion(string $question): void;

    public function getQuestion(): string;

    public function setTimeLimit(int $timeLimit): void;

    public function getTimeLimit(): int;

    public function isDoublePoints(): bool;

    public function setDoublePoints(bool $doublePoints): void;

    public function getQuestionType(): GameQuestionType;
}