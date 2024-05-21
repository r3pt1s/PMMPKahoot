<?php

namespace r3pt1s\kahoot\form\sub;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use pocketmine\player\Player;
use r3pt1s\kahoot\game\KahootGameManager;
use r3pt1s\kahoot\Kahoot;

class JoinLobbyForm extends CustomForm {

    public function __construct() {
        parent::__construct(
            "§8» §l§eJoin Lobby",
            [
                new Input("code", "§7Put the invitation code here!", "ABCDE")
            ],
            function (Player $player, CustomFormResponse $response): void {
                $code = $response->getString("code");
                if (trim($code) == "") {
                    $player->sendMessage(Kahoot::PREFIX . "§cPlease provide a code!");
                    return;
                }

                if (($game = KahootGameManager::getInstance()->getGameByCode($code)) !== null) {
                    if ($game->getSettings()->isAllowCustomNames()) {
                        $player->sendForm(new CustomForm(
                            "§8» §l§eCustom Name",
                            [new Input("custom_name", "§7What should be your in-game name?", $player->getName(), $player->getName())],
                            function (Player $player, CustomFormResponse $response) use($game): void {
                                $name = trim($response->getString("custom_name"));
                                if (strlen($name) >= 3 && strlen($name) <= 10) {
                                    $game->getGameHandler()->handleJoin($player, $name);
                                } else {
                                    if ($name == $player->getName()) {
                                        $game->getGameHandler()->handleJoin($player, $name);
                                    } else $player->sendMessage(Kahoot::PREFIX . "§cYour custom name is too short or too long! §8(§c3-10 characters§8)");
                                }
                            }
                        ));
                    } else {
                        $game->getGameHandler()->handleJoin($player);
                    }
                } else $player->sendMessage(Kahoot::PREFIX . "§cNo game has been found.");
            }
        );
    }
}