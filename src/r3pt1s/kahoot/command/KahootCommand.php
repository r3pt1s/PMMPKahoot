<?php

namespace r3pt1s\kahoot\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use r3pt1s\kahoot\form\MainForm;
use r3pt1s\kahoot\game\KahootGameManager;
use r3pt1s\kahoot\Kahoot;

class KahootCommand extends Command {

    public function __construct() {
        parent::__construct("kahoot", "Kahoot Command", "/kahoot");
        $this->setPermission(DefaultPermissions::ROOT_USER);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if (($game = KahootGameManager::getInstance()->getGameOfPlayer($sender)) !== null) {
                if ($game->getHostName() == $sender->getName()) {
                    if ($game->isRunning()) {
                        if (!$game->getSettings()->isHostCanPlay()) {
                            if ($game->isQuestioning()) {
                                $sender->sendForm($game->getCurrentGameQuestion()->buildForm($game, true));
                            } else {
                                $sender->sendMessage(Kahoot::PREFIX . "§cYou can't use this command right now!");
                            }
                        } else {
                            $sender->sendMessage(Kahoot::PREFIX . "§cYou can't use this command while participating in a game!");
                        }
                        return true;
                    } else {
                        if (count($args) == 0) {
                            $sender->sendMessage(Kahoot::PREFIX . "§cIf you want to start, type §8'§e/kahoot start§8'§c!");
                            return true;
                        }

                        if (strtolower($args[0]) == "start") {
                            $game->startGame();
                            return true;
                            if (count($game->getParticipants()) >= 2) {
                                $game->startGame();
                            } else $sender->sendMessage(Kahoot::PREFIX . "§cYou can't start the game yet!");
                            return true;
                        }
                    }
                }

                $sender->sendMessage(Kahoot::PREFIX . "§cYou can't use this command while participating in a game!");
                return true;
            }

            $sender->sendForm(new MainForm());
        }
        return true;
    }
}