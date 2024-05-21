<?php

namespace r3pt1s\kahoot\game\question\type;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use dktapps\pmforms\ModalForm;
use JsonSerializable;
use pocketmine\form\Form;
use pocketmine\player\Player;
use r3pt1s\kahoot\game\KahootGame;
use r3pt1s\kahoot\game\question\GameQuestion;
use r3pt1s\kahoot\game\question\GameQuestionType;
use r3pt1s\kahoot\Kahoot;

class TrueOrFalseQuestion implements GameQuestion, JsonSerializable {

    public function __construct(
        private string $question,
        private bool $correctAnswer,
        private int $timeLimit,
        private bool $doublePoints
    ) {}

    public function buildForm(KahootGame $game, bool $isHost): Form {
        $text = "§e" . $this->question;
        if ($isHost && !$game->getSettings()->isHostCanPlay()) {
            $text .= "\n§7Correct answer: §e" . ($this->correctAnswer ? "True" : "False");
            $text .= "\n§cThere is a time limit of: §e" . $this->timeLimit . " seconds";
            $text .= "\n\n§cYou can't play because you are the host.";
        } else {
            $text .= "\n§cYou have a time limit of: §e" . $this->timeLimit . " seconds";
            $text .= "\n\n§cIf you close this form, your answer to the question would be §e§lNONE§r§c.";
        }

        if ($isHost && !$game->getSettings()->isHostCanPlay()) {
            return new MenuForm(
                "§8» §l§cQuestion",
                $text,
                [
                    new MenuOption("§6Make all answers valid"),
                    new MenuOption("§6Enable double points")
                ],
                function (Player $player, int $data) use($game): void {
                    if ($game->isQuestioning()) {
                        if ($data == 0) $game->setForceEveryAnswerCorrect(true);
                        else $game->setForceDoublePoints(true);
                        $player->sendMessage(Kahoot::PREFIX . "§aSaved your changes, only valid for the current question.");
                    } else {
                        $player->sendMessage(Kahoot::PREFIX . "§cYou can't do that right now!");
                    }
                }
            );
        } else return new ModalForm(
            "§8» §l§cQuestion",
            $text,
            function (Player $player, bool $data) use($game): void {
                $game->getGameHandler()->handleAnswer($player, $data);
            },
            "§aTrue",
            "§cFalse"
        );
    }

    public function getQuestion(): string {
        return $this->question;
    }

    public function setQuestion(string $question): void {
        $this->question = $question;
    }

    public function isAnswerCorrect(mixed $answer): bool {
        return $answer == $this->correctAnswer;
    }

    public function getCorrectAnswer(): bool {
        return $this->correctAnswer;
    }

    public function setCorrectAnswer(bool $correctAnswer): void {
        $this->correctAnswer = $correctAnswer;
    }

    public function getTimeLimit(): int {
        return $this->timeLimit;
    }

    public function setTimeLimit(int $timeLimit): void {
        $this->timeLimit = $timeLimit;
    }

    public function isDoublePoints(): bool {
        return $this->doublePoints;
    }

    public function setDoublePoints(bool $doublePoints): void {
        $this->doublePoints = $doublePoints;
    }

    public function getQuestionType(): GameQuestionType {
        return GameQuestionType::TRUE_OR_FALSE;
    }

    public function toArray(): array {
        return [
            "question_type" => $this->getQuestionType()->value,
            "question" => $this->question,
            "correctAnswer" => $this->correctAnswer,
            "timeLimit" => $this->timeLimit,
            "doublePoints" => $this->doublePoints
        ];
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }

    public static function fromArray(array $data): ?TrueOrFalseQuestion {
        if (isset($data["question"], $data["correctAnswer"], $data["timeLimit"], $data["doublePoints"])) {
            return new TrueOrFalseQuestion(...$data);
        }
        return null;
    }
}