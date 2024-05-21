<?php

namespace r3pt1s\kahoot\game\question\type;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use JsonSerializable;
use pocketmine\form\Form;
use pocketmine\player\Player;
use r3pt1s\kahoot\game\KahootGame;
use r3pt1s\kahoot\game\question\GameQuestion;
use r3pt1s\kahoot\game\question\GameQuestionType;
use r3pt1s\kahoot\Kahoot;

class QuizQuestion implements GameQuestion, JsonSerializable {

    public function __construct(
        private string $question,
        private array $answers,
        private array $correctAnswers,
        private int $timeLimit,
        private bool $doublePoints
    ) {
        $this->answers = array_slice($this->answers, 0, 4);
    }

    public function buildForm(KahootGame $game, bool $isHost): Form {
        $possibleAnswers = count($this->correctAnswers);
        $text = "§e" . $this->question;
        $text .= "\n\n§cTHERE " . ($possibleAnswers == 1 ? "IS §e1 §cPOSSIBLE ANSWER!" : "ARE §e" . $possibleAnswers . " §cPOSSIBLE ANSWERS!");
        if ($isHost && !$game->getSettings()->isHostCanPlay()) {
            $text .= "\n§cThere is a time limit of: §e" . $this->timeLimit . " seconds";
            $text .= "\n\n§cYou can't play because you are the host.";
        } else {
            $text .= "\n§cYou have a time limit of: §e" . $this->timeLimit . " seconds";
            $text .= "\n\n§cIf you close this, your answer to the question would be §e§lNONE§r§c.";
        }

        $options = [];
        foreach ($this->answers as $i => $answer) {
            $options[] = new MenuOption(match ($i) {
                1 => "§c" . $answer . ($isHost && !$game->getSettings()->isHostCanPlay() ? ($this->isAnswerCorrect($answer) ? " §8(§a**§8)" : "") : ""),
                2 => "§e" . $answer . ($isHost && !$game->getSettings()->isHostCanPlay()  ? ($this->isAnswerCorrect($answer) ? " §8(§a**§8)" : "") : ""),
                3 => "§9" . $answer . ($isHost && !$game->getSettings()->isHostCanPlay()  ? ($this->isAnswerCorrect($answer) ? " §8(§a**§8)" : "") : ""),
                default => "§a" . $answer . ($isHost && !$game->getSettings()->isHostCanPlay()  ? ($this->isAnswerCorrect($answer) ? " §8(§a**§8)" : "") : "")
            });
        }

        if ($isHost && !$game->getSettings()->isHostCanPlay()) {
            $options[] = new MenuOption("§6Make all answers valid");
            $options[] = new MenuOption("§6Enable double points");
        }

        return new MenuForm(
            "§8» §l§cQuestion",
            $text,
            $options,
            function (Player $player, int $data) use($game, $isHost): void {
                if ($isHost && !$game->getSettings()->isHostCanPlay()) {
                    if ($game->isQuestioning()) {
                        if ($data == 0) $game->setForceEveryAnswerCorrect(true);
                        else $game->setForceDoublePoints(true);
                        $player->sendMessage(Kahoot::PREFIX . "§aSaved your changes, only valid for the current question.");
                    } else {
                        $player->sendMessage(Kahoot::PREFIX . "§cYou can't do that right now!");
                    }
                } else {
                    $game->getGameHandler()->handleAnswer($player, $this->answers[$data] ?? $data);
                }
            }
        );
    }

    public function getQuestion(): string {
        return $this->question;
    }

    public function setQuestion(string $question): void {
        $this->question = $question;
    }

    public function getAnswerIndex(string $answer): false|int {
        return array_search($answer, $this->answers);
    }

    public function getAnswers(): array {
        return $this->answers;
    }

    public function setAnswers(array $answers): void {
        $this->answers = $answers;
    }

    public function isAnswerCorrect(mixed $answer): bool {
        return in_array($answer, $this->correctAnswers);
    }

    public function getCorrectAnswers(): array {
        return $this->correctAnswers;
    }

    public function setCorrectAnswers(array $correctAnswers): void {
        $this->correctAnswers = $correctAnswers;
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
        return GameQuestionType::QUIZ;
    }

    public function toArray(): array {
        return [
            "question_type" => $this->getQuestionType()->value,
            "question" => $this->question,
            "answers" => $this->answers,
            "correctAnswers" => $this->correctAnswers,
            "timeLimit" => $this->timeLimit,
            "doublePoints" => $this->doublePoints
        ];
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }

    public static function fromArray(array $data): ?QuizQuestion {
        if (isset($data["question"], $data["answers"], $data["correctAnswers"], $data["timeLimit"], $data["doublePoints"])) {
            return new QuizQuestion(...$data);
        }
        return null;
    }
}