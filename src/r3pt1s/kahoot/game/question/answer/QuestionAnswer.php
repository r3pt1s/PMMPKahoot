<?php

namespace r3pt1s\kahoot\game\question\answer;

use pocketmine\player\Player;
use r3pt1s\kahoot\game\KahootGame;
use r3pt1s\kahoot\game\KahootGameManager;
use r3pt1s\kahoot\game\question\GameQuestion;
use r3pt1s\kahoot\game\util\Utils;

class QuestionAnswer {

    public function __construct(
        private readonly Player $player,
        private readonly GameQuestion $question,
        private readonly mixed $answer,
        private readonly float $time
    ) {}

    public function calculateAndAddPoints(): int {
        $finalTime = $this->time - $this->getGame()->getQuestionStart();
        $calculatedPoints = Utils::calculatePoints($finalTime, $this->question->getTimeLimit(), $this->getGame()->getTemplate()->getMaxPoints());
        if ($this->question->isDoublePoints() || ($this->getGame()?->isForceDoublePoints() ?? false)) $calculatedPoints *= 2;
        return $this->getGame()->getParticipant($this->player)->addPoints($calculatedPoints);
    }

    public function isCorrect(): bool {
        if ($this->getGame()?->isForceEveryAnswerCorrect() ?? false) return true;
        return $this->question->isAnswerCorrect($this->answer);
    }

    public function getPlayer(): Player {
        return $this->player;
    }

    public function getQuestion(): GameQuestion {
        return $this->question;
    }

    public function getAnswer(): mixed {
        return $this->answer;
    }

    public function getTime(): float {
        return $this->time;
    }

    public function getGame(): KahootGame {
        return KahootGameManager::getInstance()->getGameOfPlayer($this->player);
    }
}