<?php

namespace r3pt1s\kahoot\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;
use r3pt1s\kahoot\game\KahootGameManager;

class EventListener implements Listener {

    public function onQuit(PlayerQuitEvent $event): void {
        if (($game = KahootGameManager::getInstance()->getGameOfPlayer($event->getPlayer())) !== null) {
            $game->getGameHandler()->handleLeave($event->getPlayer());
        }
    }

    public function onChat(PlayerChatEvent $event): void {
        $recipients = $event->getRecipients();
        foreach ($recipients as $key => $recipient) {
            if (($game = KahootGameManager::getInstance()->getGameOfPlayer($recipient->getName())) !== null && $game->isRunning()) {
                unset($recipients[$key]);
            }
        }

        $event->setRecipients($recipients);
    }
}