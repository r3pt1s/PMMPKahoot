<?php

namespace r3pt1s\kahoot\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use r3pt1s\kahoot\game\KahootGameManager;
use r3pt1s\kahoot\Kahoot;

class KahootLeaveCommand extends Command implements PluginOwned {

    public function __construct() {
        parent::__construct("kahootleave", "Leave a Kahoot game", "/kahootleave", ["kleave"]);
        $this->setPermission("pmmpkahoot.command.leave");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if (($game = KahootGameManager::getInstance()->getGameOfPlayer($sender)) !== null) {
                $game->getGameHandler()->handleLeave($sender);
            } else {
                $sender->sendMessage(Kahoot::PREFIX . "§cYou are not playing!");
            }
        }
        return true;
    }

    public function getOwningPlugin(): Plugin {
        return Kahoot::getInstance();
    }
}