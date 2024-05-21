<?php

namespace r3pt1s\kahoot\form\sub;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketmine\player\Player;
use r3pt1s\kahoot\game\template\Template;
use r3pt1s\kahoot\game\template\TemplateManager;

class MyCreationsForm extends MenuForm {

    public function __construct(Player $player) {
        parent::__construct(
            "§8» §e§lMy Creations",
            "",
            array_map(fn(Template $template) => new MenuOption("§e" . $template->getName()), $templates = array_values(TemplateManager::getInstance()->getGameTemplates($player))),
            function (Player $player, int $data) use($templates): void {
                $template = $templates[$data] ?? null;
                if ($template !== null) {
                    $player->sendForm(new EditTemplateForm($template));
                }
            }
        );
    }
}