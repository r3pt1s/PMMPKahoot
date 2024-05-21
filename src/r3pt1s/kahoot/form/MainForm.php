<?php

namespace r3pt1s\kahoot\form;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Label;
use dktapps\pmforms\element\Slider;
use dktapps\pmforms\element\Toggle;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use r3pt1s\kahoot\form\sub\JoinLobbyForm;
use r3pt1s\kahoot\form\sub\MyCreationsForm;
use r3pt1s\kahoot\form\sub\PublicLobbiesForm;
use r3pt1s\kahoot\game\GameSettings;
use r3pt1s\kahoot\game\KahootGameManager;
use r3pt1s\kahoot\game\template\Template;
use r3pt1s\kahoot\game\template\TemplateManager;
use r3pt1s\kahoot\Kahoot;

class MainForm extends MenuForm {

    public function __construct() {
        parent::__construct(
            "§8» §2§lKahoot",
            "",
            [
                new MenuOption("§cCreate Game Template"),
                new MenuOption("§6My Creations"),
                new MenuOption("§aCreate Lobby"),
                new MenuOption("§eJoin Lobby"),
                new MenuOption("§9Public Lobbies"),
            ],
            function (Player $player, int $data): void {
                if ($data == 0) {
                    $player->sendForm(new CustomForm(
                        "§8» §l§cCreate Game Template",
                        [
                            new Input("id", "§7Unique ID for the game template", "template.earth"),
                            new Input("name", "§7What's the name of your creation?", "About Earth"),
                            new Input("description", "§7Describe your creation §8(§cOptional§8)", "It is about the planet Earth"),
                            new Slider("max_points", "§7What are the max points you can get per question?", 100, 1200, 100, 1000)
                        ],
                        function (Player $player, CustomFormResponse $response): void {
                            $id = trim($response->getString("id"));
                            $name = trim($response->getString("name"));
                            $desc = trim($response->getString("description"));
                            $maxPoints = $response->getFloat("max_points");

                            if ($id == "") {
                                $player->sendMessage(Kahoot::PREFIX . "§cPlease provide an unique id!");
                                return;
                            }

                            if (strlen($id) > 10) {
                                $player->sendMessage(Kahoot::PREFIX . "§cThe unique Id is too long!");
                                return;
                            }

                            if ($name == "") {
                                $player->sendMessage(Kahoot::PREFIX . "§cPlease provide a name for your creation!");
                                return;
                            }

                            if (strlen($name) >= 3 && strlen($name) <= 32) {
                                $player->sendMessage(Kahoot::PREFIX . "§cThe name is too short or too long! §8(§c3-32 characters§8)");
                                return;
                            }

                            if (TemplateManager::getInstance()->getGameTemplate($id)) {
                                $player->sendMessage(Kahoot::PREFIX . "§cA creation with that id already exists!");
                                return;
                            }

                            TemplateManager::getInstance()->createBaseTemplate($player, $id, $name, $desc, $maxPoints);
                            $player->sendMessage(Kahoot::PREFIX . "§aYour creation has been created. To add questions you have to type §8'§e/kahoot§8' §aand click on §8'§eMy Creations§8'§a.");
                        }
                    ));
                } else if ($data == 1) {
                    if (count(TemplateManager::getInstance()->getGameTemplates($player)) == 0) {
                        $player->sendMessage(Kahoot::PREFIX . "§cYou don't have any creations!");
                        return;
                    }

                    $player->sendForm(new MyCreationsForm($player));
                } else if ($data == 2) {
                    if (count(TemplateManager::getInstance()->getPlayableGameTemplates()) == 0) {
                        $player->sendMessage(Kahoot::PREFIX . "§cThere are no playable game templates!");
                        return;
                    }

                    $player->sendForm(new MenuForm(
                        "§8» §l§aCreate Lobby",
                        "§7Please choose a game template.",
                        array_map(fn(Template $template) => new MenuOption("§e" . $template->getName() . "\n§b" . $template->getCreator()), $templates = array_values(TemplateManager::getInstance()->getPlayableGameTemplates())),
                        function (Player $player, int $data) use($templates): void {
                            if (isset($templates[$data])) {
                                $template = $templates[$data];
                                $player->sendForm(new CustomForm(
                                    "§8» §l§aCreate Lobby",
                                    array_merge(
                                        [
                                            new Label("desc", "§e" . $template->getDescription()),
                                            new Toggle("allowCustomNames", "§7Can players choose their own name?", false),
                                            new Toggle("canHostPlay", "§7Do you want to play too?", false)
                                        ],
                                        (Kahoot::getInstance()->get("public-lobbies.permissionToCreate", "none") == "none" ? [
                                            new Toggle("publicLobby", "§7Should this be public for everyone?", false)
                                        ] : ($player->hasPermission(Kahoot::getInstance()->get("public-lobbies.permissionToCreate", DefaultPermissions::ROOT_OPERATOR)) ? [
                                            new Toggle("publicLobby", "§7Should this be public for everyone?", false)
                                        ]: []))
                                    ),
                                    function (Player $player, CustomFormResponse $response) use($template): void {
                                        try {
                                            $public = $response->getBool("publicLobby");
                                        } catch (\InvalidArgumentException) {
                                            $public = false;
                                        }

                                        KahootGameManager::getInstance()->createGame($player, $template, new GameSettings(
                                            $response->getBool("allowCustomNames"),
                                            $response->getBool("canHostPlay")
                                        ), $public);
                                    }
                                ));
                            }
                        }
                    ));
                } else if ($data == 3) {
                    $player->sendForm(new JoinLobbyForm());
                } else if ($data == 4) {
                    if (count(KahootGameManager::getInstance()->getPublicGames()) == 0) {
                        $player->sendMessage(Kahoot::PREFIX . "§cThere are no public lobbies!");
                        return;
                    }

                    $player->sendForm(new PublicLobbiesForm());
                }
            }
        );
    }
}