<?php

namespace r3pt1s\kahoot\game\question\type;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Label;
use dktapps\pmforms\element\Slider;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use JsonSerializable;
use pocketmine\form\Form;
use pocketmine\player\Player;
use r3pt1s\kahoot\game\KahootGame;
use r3pt1s\kahoot\game\question\GameQuestion;
use r3pt1s\kahoot\game\question\GameQuestionType;
use r3pt1s\kahoot\Kahoot;

class SliderQuestion implements GameQuestion, JsonSerializable {

    public function __construct(
        private string $question,
        private float $min,
        private float $max,
        private float $default,
        private float $step,
        private float $answer,
        private int $timeLimit,
        private bool $doublePoints
    ) {}

    public function buildForm(KahootGame $game, bool $isHost): Form {
        $text = "§e" . $this->question;
        if ($isHost && !$game->getSettings()->isHostCanPlay()) {
            $text .= "\n§7Correct answer: §e" . $this->answer;
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
        } else return new CustomForm(
            "§8» §l§cQuestion",
            [
                new Label("label", $text),
                new Slider("slider", "§7Your answer:", $this->min, $this->max, $this->step, $this->default)
            ],
            function (Player $player, CustomFormResponse $data) use($game): void {
                $game->getGameHandler()->handleAnswer($player, $data->getFloat("slider"));
            }
        );
    }

    public function getQuestion(): string {
        return $this->question;
    }

    public function setQuestion(string $question): void {
        $this->question = $question;
    }

    public function getMin(): float {
        return $this->min;
    }

    public function setMin(float $min): void {
        $this->min = $min;
    }

    public function getMax(): float {
        return $this->max;
    }

    public function setMax(float $max): void {
        $this->max = $max;
    }

    public function getDefault(): float {
        return $this->default;
    }

    public function setDefault(float $default): void {
        $this->default = $default;
    }

    public function getStep(): float {
        return $this->step;
    }

    public function setStep(float $step): void {
        $this->step = $step;
    }

    public function isAnswerCorrect(mixed $answer): bool {
        return $answer == $this->answer;
    }

    public function getAnswer(): float {
        return $this->answer;
    }

    public function setAnswer(float $answer): void {
        $this->answer = $answer;
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
        return GameQuestionType::SLIDER;
    }

    public function toArray(): array {
        return [
            "question_type" => $this->getQuestionType()->value,
            "question" => $this->question,
            "min" => $this->min,
            "max" => $this->max,
            "default" => $this->default,
            "step" => $this->step,
            "answer" => $this->answer,
            "timeLimit" => $this->timeLimit,
            "doublePoints" => $this->doublePoints
        ];
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }

    public static function fromArray(array $data): ?SliderQuestion {
        if (isset($data["question"], $data["min"], $data["max"], $data["default"], $data["step"], $data["answer"], $data["timeLimit"], $data["doublePoints"])) {
            return new SliderQuestion(...$data);
        }
        return null;
    }
}