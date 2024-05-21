<?php

namespace r3pt1s\kahoot\game\template;

use r3pt1s\kahoot\game\question\GameQuestion;
use r3pt1s\kahoot\game\question\GameQuestionType;
use r3pt1s\kahoot\game\question\type\QuizQuestion;
use r3pt1s\kahoot\game\question\type\SliderQuestion;
use r3pt1s\kahoot\game\question\type\TrueOrFalseQuestion;

class Template implements \JsonSerializable {

    public const DEFAULT_MAX_POINTS = 1000;

    /** @var array<GameQuestion> $questions */
    public function __construct(
        private readonly string $id,
        private readonly string $creator,
        private string $name,
        private string $description,
        private array $questions,
        private int $max_points = self::DEFAULT_MAX_POINTS
    ) {}

    public function getId(): string {
        return $this->id;
    }

    public function getCreator(): string {
        return $this->creator;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function setDescription(string $description): void {
        $this->description = $description;
    }

    public function getQuestion(int $index): ?GameQuestion {
        return $this->questions[$index] ?? null;
    }

    public function getQuestions(): array {
        return $this->questions;
    }

    public function addQuestion(GameQuestion $question): void {
        $this->questions[] = $question;
    }

    public function removeQuestion(GameQuestion $question): void {
        if (in_array($question, $this->questions)) unset($this->questions[array_search($question, $this->questions)]);
    }

    public function setQuestions(array $questions): void {
        $this->questions = $questions;
    }

    public function getMaxPoints(): int {
        return $this->max_points;
    }

    public function setMaxPoints(int $maxPoints): void {
        $this->max_points = $maxPoints;
    }

    public function toArray(): array {
        return [
            "id" => $this->id,
            "creator" => $this->creator,
            "name" => $this->name,
            "description" => $this->description,
            "questions" => $this->questions,
            "max_points" => $this->max_points
        ];
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }

    public static function fromArray(array $data): ?Template {
        if (isset($data["id"], $data["creator"], $data["name"], $data["description"], $data["questions"], $data["max_points"])) {
            if (is_array($data["questions"])) {
                $data["questions"] = array_map(fn(array $qData) => match (GameQuestionType::from(array_shift($qData))) {
                    GameQuestionType::QUIZ => new QuizQuestion(...$qData),
                    GameQuestionType::TRUE_OR_FALSE => new TrueOrFalseQuestion(...$qData),
                    GameQuestionType::SLIDER => new SliderQuestion(...$qData)
                }, $data["questions"]);

                return new Template(...$data);
            }
        }
        return null;
    }
}