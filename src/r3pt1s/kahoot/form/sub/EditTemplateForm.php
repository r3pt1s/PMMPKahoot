<?php

namespace r3pt1s\kahoot\form\sub;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Slider;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use dktapps\pmforms\ModalForm;
use pocketmine\player\Player;
use r3pt1s\kahoot\game\template\Template;
use r3pt1s\kahoot\game\template\TemplateManager;
use r3pt1s\kahoot\Kahoot;

class EditTemplateForm extends MenuForm {

    public function __construct(
        private readonly Template $template
    ) {
        $text = "§e" . $this->template->getName();
        $text .= "\n\n§e" . $this->template->getDescription();
        $text .= "\n\n§7Max Points per Question: §e" . $this->template->getMaxPoints() . " Points";
        $text .= "\n§7Status: §" . (count($this->template->getQuestions()) > 0 ? "aPlayable" : "cNot Playable");
        parent::__construct(
            "§8» §e§l" . $this->template->getId(),
            $text,
            [
                new MenuOption("§eChange name"),
                new MenuOption("§eChange description"),
                new MenuOption("§eChange max points"),
                new MenuOption("§aAdd Question"),
                new MenuOption("§cRemove Question"),
                new MenuOption("§eEdit Question"),
                new MenuOption("§4Delete")
            ],
            function (Player $player, int $data): void {
                if ($data == 0) {
                    $player->sendForm(new CustomForm(
                        "§8» §l§eChange name",
                        [new Input("name", "What is the new name?", $this->template->getName(), $this->template->getName())],
                        function (Player $player, CustomFormResponse $response): void {
                            $name = trim($response->getString("name"));
                            if (strlen($name) >= 3 && strlen($name) <= 32) {
                                $this->template->setName($name);
                                TemplateManager::getInstance()->saveTemplate($this->template);
                                $player->sendForm(new self($this->template));
                            } else $player->sendMessage(Kahoot::PREFIX . "§cThe name is too short or too long! §8(§c3-32 characters§8)");
                        }
                    ));
                } else if ($data == 1) {
                    $player->sendForm(new CustomForm(
                        "§8» §l§eChange description",
                        [new Input("desc", "What is the new description?", $this->template->getDescription(), $this->template->getDescription())],
                        function (Player $player, CustomFormResponse $response): void {
                            $description = trim($response->getString("desc"));
                            if (strlen($description) <= 100) {
                                $this->template->setDescription($description);
                                TemplateManager::getInstance()->saveTemplate($this->template);
                                $player->sendForm(new self($this->template));
                            } else $player->sendMessage(Kahoot::PREFIX . "§cThe description is too long! §8(§cmax. 100 characters§8)");
                        }
                    ));
                } else if ($data == 2) {
                    $player->sendForm(new CustomForm(
                        "§8» §l§eChange max points",
                        [new Slider(
                            "max_points",
                            "§7What are the new max points you can get per question?",
                            100,
                            1500,
                            100,
                            $this->template->getMaxPoints()
                        )],
                        function (Player $player, CustomFormResponse $response): void {
                            $this->template->setMaxPoints($response->getFloat("max_points"));
                            TemplateManager::getInstance()->saveTemplate($this->template);
                            $player->sendForm(new self($this->template));
                        }
                    ));
                } else if ($data == 3) {
                    $player->sendForm(new AddQuestionForm($this->template));
                } else if ($data == 4) {
                    $player->sendForm(new RemoveQuestionForm($this->template));
                } else if ($data == 5) {
                    $player->sendForm(new EditQuestionForm($this->template));
                } else if ($data == 6) {
                    $player->sendForm(new ModalForm(
                        "§8» §l§aConfirmation",
                        "§eAre you sure you want to §4delete §eyour creation called §8'§e" . $this->template->getName() . "§8' §ewith the id §8'§e" . $this->template->getId() . "§8'§e?",
                        function (Player $player, bool $data): void {
                            if ($data) {
                                $player->sendMessage(Kahoot::PREFIX . "§cYour creation has been removed!");
                                TemplateManager::getInstance()->removeTemplate($this->template);
                            } else $player->sendForm(new self($this->template));
                        },
                        "§aYes, I am",
                        "§cNo"
                    ));
                }
            }
        );
    }
}