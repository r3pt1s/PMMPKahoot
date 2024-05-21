<?php

namespace r3pt1s\kahoot\form\sub;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketmine\player\Player;
use r3pt1s\kahoot\game\KahootGame;
use r3pt1s\kahoot\game\KahootGameManager;
use r3pt1s\kahoot\Kahoot;

class PublicLobbiesForm extends MenuForm {

    public function __construct() {
        $publicLobbies = array_values(KahootGameManager::getInstance()->getPublicGames());
        parent::__construct(
            "§8» §l§9Public Lobbies",
            "",
            array_map(fn(KahootGame $game) => new MenuOption("§e" . $game->getHostName() . "\n§r§b" . $game->getTemplate()->getName()), $publicLobbies),
            function (Player $player, int $data) use($publicLobbies): void {
                $publicLobby = $publicLobbies[$data] ?? null;
                if ($publicLobby !== null) {
                    if ($publicLobby->getSettings()->isAllowCustomNames()) {
                        $player->sendForm(new CustomForm(
                            "§8» §l§eCustom Name",
                            [new Input("custom_name", "§7What should be your in-game name?", $player->getName(), $player->getName())],
                            function (Player $player, CustomFormResponse $response) use($publicLobby): void {
                                $name = trim($response->getString("custom_name"));
                                if (strlen($name) >= 3 && strlen($name) <= 10) {
                                    $publicLobby->getGameHandler()->handleJoin($player, $name);
                                } else {
                                    if ($name == $player->getName()) {
                                        $publicLobby->getGameHandler()->handleJoin($player, $name);
                                    } else $player->sendMessage(Kahoot::PREFIX . "§cYour custom name is too short or too long! §8(§c3-10 characters§8)");
                                }
                            }
                        ));
                    } else {
                        $publicLobby->getGameHandler()->handleJoin($player);
                    }
                }
            }
        );
    }
}