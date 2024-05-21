<?php

namespace r3pt1s\kahoot\form\sub;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketmine\player\Player;
use r3pt1s\kahoot\game\question\GameQuestion;
use r3pt1s\kahoot\game\template\Template;
use r3pt1s\kahoot\game\template\TemplateManager;
use r3pt1s\kahoot\Kahoot;

class RemoveQuestionForm extends MenuForm {

    public function __construct(private readonly Template $template) {
        parent::__construct(
            "§8» §l§cRemove Question",
            "§7Please select a question.",
            array_map(fn(GameQuestion $question) => new MenuOption("§e" . substr($question->getQuestion(), 0, 10) . "..."), $questions = array_values($this->template->getQuestions())),
            function (Player $player, int $data) use($questions): void {
                $question = $questions[$data] ?? null;
                if ($question !== null) {
                    $player->sendMessage(Kahoot::PREFIX . "§cQuestion has been removed!");
                    $this->template->removeQuestion($question);
                    TemplateManager::getInstance()->saveTemplate($this->template);
                }
            }
        );
    }
}