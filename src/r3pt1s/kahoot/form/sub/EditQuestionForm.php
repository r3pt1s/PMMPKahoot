<?php

namespace r3pt1s\kahoot\form\sub;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Label;
use dktapps\pmforms\element\Slider;
use dktapps\pmforms\element\Toggle;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketmine\player\Player;
use r3pt1s\kahoot\game\question\GameQuestion;
use r3pt1s\kahoot\game\question\type\QuizQuestion;
use r3pt1s\kahoot\game\question\type\SliderQuestion;
use r3pt1s\kahoot\game\question\type\TrueOrFalseQuestion;
use r3pt1s\kahoot\game\template\Template;
use r3pt1s\kahoot\game\template\TemplateManager;
use r3pt1s\kahoot\Kahoot;

class EditQuestionForm extends MenuForm {

    public function __construct(private readonly Template $template) {
        parent::__construct(
            "§8» §l§eEdit Question",
            "§7Please select a question.",
            array_map(fn(GameQuestion $question) => new MenuOption("§e" . substr($question->getQuestion(), 0, 11) . "..."), $questions = $this->template->getQuestions()),
            function (Player $player, int $data) use($questions): void {
                $question = $questions[$data] ?? null;
                if ($question !== null) {
                    $player->sendForm(new MenuForm(
                        "§8» §l§eEdit Question",
                        "§e" . $question->getQuestion(),
                        [
                            new MenuOption("§eChange properties"),
                            new MenuOption("§eChange answer-related options")
                        ],
                        function (Player $player, int $data) use($question, $questions): void {
                            if ($data == 0) {
                                $player->sendForm($this->propertiesForm($question, array_search($question, $questions)));
                            } else if ($data == 1) {
                                $player->sendForm($this->answerRelatedOptionsForm($question, array_search($question, $questions)));
                            }
                        }
                    ));
                }
            }
        );
    }

    private function propertiesForm(GameQuestion $question, int $index): CustomForm {
        return new CustomForm(
            "§8» §l§eChange properties",
            [
                new Input("question", "§7What is the question?", "How far away is the Moon from the Earth?", $question->getQuestion()),
                new Toggle("doublePoints", "§7Should this question give double points?", $question->isDoublePoints()),
                new Slider("timeLimit", "§7Time limit in seconds for this question", 1, 30, 1, $question->getTimeLimit()),
            ],
            function (Player $player, CustomFormResponse $response) use($question, $index): void {
                $questionString = trim($response->getString("question"));
                $doublePoints = $response->getBool("doublePoints");
                $timeLimit = $response->getFloat("timeLimit");

                if ($questionString == "") {
                    $player->sendMessage(Kahoot::PREFIX . "§cPlease provide a valid question!");
                    return;
                }

                if (strlen($questionString) > 64) {
                    $player->sendMessage(Kahoot::PREFIX . "§cThe question is too long! §8(§cmax. 64 characters§8)");
                    return;
                }

                $question->setQuestion($questionString);
                $question->setDoublePoints($doublePoints);
                $question->setTimeLimit($timeLimit);
                TemplateManager::getInstance()->saveTemplate($this->template);
                $player->sendForm(new self($this->template));
            }
        );
    }

    private function answerRelatedOptionsForm(GameQuestion $question, int $index): CustomForm {
        if ($question instanceof QuizQuestion) {
            $answerOne = $question->getAnswers()[0] ?? "";
            $answerTwo = $question->getAnswers()[1] ?? "";
            $answerThree = $question->getAnswers()[2] ?? "";
            $answerFour = $question->getAnswers()[3] ?? "";
            return new CustomForm(
                "§8» §l§eChange answer-related options",
                [
                    new Label("question", "§e" . $question->getQuestion()),
                    new Label("content", "\n§7You can provide up to §e4 answers §7for each quiz-type question. You can also make more than 1 answer correct."),
                    new Label("contentTwo", "§8(§c*§8) §7= §cRequired"),
                    new Input("answerOne", "§7Please provide the first answer here §8(§c*§8)", "384,400 kilometers", $answerOne),
                    new Toggle("answerOneCorrect", "§7Is the first answer correct?", $question->isAnswerCorrect($answerOne)),
                    new Input("answerTwo", "§7Please provide the second answer here §8(§c*§8)", "384,400 meters", $answerTwo),
                    new Toggle("answerTwoCorrect", "§7Is the second answer correct?", $question->isAnswerCorrect($answerTwo)),
                    new Input("answerThree", "§7Please provide the third answer here", "384,400 centimeters", $answerThree),
                    new Toggle("answerThreeCorrect", "§7Is the third answer correct?", $question->isAnswerCorrect($answerThree)),
                    new Input("answerFour", "§7Please provide the fourth answer here", "384,400 light years", $answerFour),
                    new Toggle("answerFourCorrect", "§7Is the fourth answer correct?", $question->isAnswerCorrect($answerFour))
                ],
                function (Player $player, CustomFormResponse $response) use($question, $index): void {
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
                        $player->sendForm($this->answerRelatedOptionsForm($question, $index));
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
                        $question->setAnswers($providedAnswers);
                        $question->setCorrectAnswers($providedCorrectAnswers);
                        TemplateManager::getInstance()->saveTemplate($this->template);
                        $player->sendForm(new self($this->template));
                    } else {
                        $player->sendMessage(Kahoot::PREFIX . "§cYou selected one answer to be correct but it is empty!");
                        $player->sendForm($this->answerRelatedOptionsForm($question, $index));
                    }
                }
            );
        } else if ($question instanceof SliderQuestion) {
            return new CustomForm(
                "§8» §l§eChange answer-related options",
                [
                    new Input("min", "§7Min of the slider", "1", $question->getMin()),
                    new Input("max", "§7Max of the slider", "3", $question->getMax()),
                    new Input("default", "§7Default of the slider", "2", $question->getDefault()),
                    new Input("step", "§7Step of the slider §8(§cDistances§8)", "1", $question->getStep()),
                    new Input("answer", "§7Answer of the final slider", "3", $question->getAnswer())
                ],
                function (Player $player, CustomFormResponse $response) use($question, $index): void {
                    [$min, $max, $default, $step, $answer] = [
                        $response->getString("min"),
                        $response->getString("max"),
                        $response->getString("default"),
                        $response->getString("step"),
                        $response->getString("answer")
                    ];

                    if (is_numeric($min) && is_numeric($max) && is_numeric($default) && is_numeric($step) && is_numeric($answer)) {
                        $question->setMin($min);
                        $question->setMax($min);
                        $question->setDefault($min);
                        $question->setStep($min);
                        $question->setAnswer($min);
                        TemplateManager::getInstance()->saveTemplate($this->template);
                        $player->sendForm(new self($this->template));
                    } else $player->sendMessage(Kahoot::PREFIX . "§cOne value is not a number!");
                }
            );
        } else {
            /** @var TrueOrFalseQuestion $question */
            return new CustomForm(
                "§8» §l§eChange answer-related options",
                [
                    new Dropdown("correct", "§7Is the question correct or not?", [
                        "Yes", "No"
                    ], $question->getCorrectAnswer() ? 0 : 1)
                ],
                function (Player $player, CustomFormResponse $response) use($question, $index): void {
                    $question->setCorrectAnswer($response->getInt("correct") == 0);
                    TemplateManager::getInstance()->saveTemplate($this->template);
                    $player->sendForm(new self($this->template));
                }
            );
        }
    }
}