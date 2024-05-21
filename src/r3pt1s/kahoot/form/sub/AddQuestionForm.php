<?php

namespace r3pt1s\kahoot\form\sub;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Label;
use dktapps\pmforms\element\Slider;
use dktapps\pmforms\element\Toggle;
use pocketmine\player\Player;
use r3pt1s\kahoot\game\question\type\QuizQuestion;
use r3pt1s\kahoot\game\question\type\SliderQuestion;
use r3pt1s\kahoot\game\question\type\TrueOrFalseQuestion;
use r3pt1s\kahoot\game\template\Template;
use r3pt1s\kahoot\game\template\TemplateManager;
use r3pt1s\kahoot\Kahoot;

class AddQuestionForm extends CustomForm {

    public function __construct(private readonly Template $template) {
        parent::__construct(
            "§8» §l§aAdd Question",
            [
                new Input("question", "§7What is the question?", "How far away is the Moon from the Earth?"),
                new Toggle("doublePoints", "§7Should this question give double points?", false),
                new Slider("timeLimit", "§7Time limit in seconds for this question", 1, 30, 1, 30),
                new Dropdown("type", "§7What type of question is this?", [
                    "Quiz",
                    "True or False",
                    "Slider"
                ])
            ],
            function (Player $player, CustomFormResponse $response): void {
                $question = trim($response->getString("question"));
                $doublePoints = $response->getBool("doublePoints");
                $timeLimit = $response->getFloat("timeLimit");

                if ($question == "") {
                    $player->sendMessage(Kahoot::PREFIX . "§cPlease provide a valid question!");
                    return;
                }

                if (strlen($question) > 64) {
                    $player->sendMessage(Kahoot::PREFIX . "§cThe question is too long! §8(§cmax. 64 characters§8)");
                    return;
                }

                $player->sendForm(match ($response->getInt("type")) {
                    1 => $this->trueOrFalseTypeForm($question, $doublePoints, $timeLimit),
                    2 => $this->sliderTypeForm($question, $doublePoints, $timeLimit),
                    default => $this->quizTypeForm($question, $doublePoints, $timeLimit)
                });
            }
        );
    }

    private function quizTypeForm(string $question, bool $doublePoints, int $timeLimit): CustomForm {
        return new CustomForm(
            "§8» §l§eType§r§8: §eQuiz",
            [
                new Label("question", "§e" . $question),
                new Label("content", "\n§7You can provide up to §e4 answers §7for each quiz-type question. You can also make more than 1 answer correct."),
                new Label("contentTwo", "§8(§c*§8) §7= §cRequired"),
                new Input("answerOne", "§7Please provide the first answer here §8(§c*§8)", "384,400 kilometers"),
                new Toggle("answerOneCorrect", "§7Is the first answer correct?"),
                new Input("answerTwo", "§7Please provide the second answer here §8(§c*§8)", "384,400 meters"),
                new Toggle("answerTwoCorrect", "§7Is the second answer correct?"),
                new Input("answerThree", "§7Please provide the third answer here", "384,400 centimeters"),
                new Toggle("answerThreeCorrect", "§7Is the third answer correct?"),
                new Input("answerFour", "§7Please provide the fourth answer here", "384,400 light years"),
                new Toggle("answerFourCorrect", "§7Is the fourth answer correct?")
            ],
            function (Player $player, CustomFormResponse $response) use($question, $doublePoints, $timeLimit): void {
                [$answerOne, $answerTwo, $answerThree, $answerFour] = [
                    trim($response->getString("answerOne")),
                    trim($response->getString("answerTwo")),
                    trim($response->getString("answerThree")),
                    trim($response->getString("answerFour"))
                ];

                [$answerOneCorrect, $answerTwoCorrect, $answerThreeCorrect, $answerFourCorrect] = [
                    $response->getBool("answerOneCorrect"),
                    $response->getBool("answerTwoCorrect"),
                    $response->getBool("answerThreeCorrect"),
                    $response->getBool("answerFourCorrect")
                ];

                if ($answerOne == "" && $answerTwo == "") {
                    $player->sendMessage(Kahoot::PREFIX . "§cPlease provide answer one and answer two!");
                    $player->sendForm($this->quizTypeForm($question, $doublePoints, $timeLimit));
                    return;
                }

                $providedAnswers = [$answerOne, $answerTwo];
                $providedCorrectAnswers = [];
                if ($answerThree !== "") $providedAnswers[] = $answerThree;
                if ($answerFour !== "") $providedAnswers[] = $answerFour;

                if ($answerOneCorrect) $providedCorrectAnswers[] = $answerOne;
                if ($answerTwoCorrect) $providedCorrectAnswers[] = $answerTwo;
                if ($answerThreeCorrect) $providedCorrectAnswers[] = $answerThree;
                if ($answerFourCorrect) $providedCorrectAnswers[] = $answerFour;

                $isOkay = true;

                foreach ($providedCorrectAnswers as $answer) {
                    if (!in_array($answer, $providedAnswers)) {
                        $isOkay = false;
                        break;
                    }
                }

                if ($isOkay) {
                    $player->sendMessage(Kahoot::PREFIX . "§aQuestion has been added.");
                    $this->template->addQuestion(new QuizQuestion(
                        $question, $providedAnswers, $providedCorrectAnswers, $timeLimit, $doublePoints
                    ));
                    TemplateManager::getInstance()->saveTemplate($this->template);
                } else {
                    $player->sendMessage(Kahoot::PREFIX . "§cYou selected one answer to be correct but it is empty!");
                    $player->sendForm($this->quizTypeForm($question, $doublePoints, $timeLimit));
                }
            }
        );
    }

    private function sliderTypeForm(string $question, bool $doublePoints, int $timeLimit): CustomForm {
        return new CustomForm(
            "§8» §l§eType§r§8: §eSlider",
            [
                new Input("min", "§7Min of the slider", "1", "1"),
                new Input("max", "§7Max of the slider", "3", "3"),
                new Input("default", "§7Default of the slider", "2", "2"),
                new Input("step", "§7Step of the slider §8(§cDistances§8)", "1", "1"),
                new Input("answer", "§7Answer of the final slider", "3", "3")
            ],
            function (Player $player, CustomFormResponse $response) use($question, $doublePoints, $timeLimit): void {
                [$min, $max, $default, $step, $answer] = [
                    $response->getString("min"),
                    $response->getString("max"),
                    $response->getString("default"),
                    $response->getString("step"),
                    $response->getString("answer")
                ];

                if (is_numeric($min) && is_numeric($max) && is_numeric($default) && is_numeric($step) && is_numeric($answer)) {
                    $player->sendMessage(Kahoot::PREFIX . "§aQuestion has been added.");
                    $this->template->addQuestion(new SliderQuestion(
                        $question, floatval($min), floatval($max), floatval($default), floatval($step), floatval($answer), $timeLimit, $doublePoints
                    ));
                    TemplateManager::getInstance()->saveTemplate($this->template);
                } else $player->sendMessage(Kahoot::PREFIX . "§cOne value is not a number!");
            }
        );
    }

    private function trueOrFalseTypeForm(string $question, bool $doublePoints, int $timeLimit): CustomForm {
        return new CustomForm(
            "§8» §l§eType§r§8: §eToF",
            [
                new Dropdown("correct", "§7Is the question correct or not?", [
                    "Yes", "No"
                ])
            ],
            function (Player $player, CustomFormResponse $response) use($question, $doublePoints, $timeLimit): void {
                $player->sendMessage(Kahoot::PREFIX . "§aQuestion has been added.");
                $this->template->addQuestion(new TrueOrFalseQuestion(
                    $question, $response->getInt("correct") == 0, $timeLimit, $doublePoints
                ));
                TemplateManager::getInstance()->saveTemplate($this->template);
            }
        );
    }
}