<?php

namespace r3pt1s\kahoot\game\handler;

use pocketmine\player\Player;
use r3pt1s\kahoot\game\KahootGame;
use r3pt1s\kahoot\game\KahootGameManager;
use r3pt1s\kahoot\Kahoot;

class KahootGameHandler {

    public function __construct(
        private readonly int $gameId
    ) {}

    public function handleJoin(Player $player, ?string $customName = null): void {
        if (count($this->getGame()->getParticipants()) >= KahootGame::MAX_PLAYERS) {
            $player->sendMessage(Kahoot::PREFIX . "§cThis lobby is already full.");
            return;
        }

        $this->getGame()->addParticipant($player, $customName);
        $this->getGame()->sendMessage("§e" . $player->getName() . " §8(§e" . ($customName ?? $player->getName()) . "§8) §ahas joined the game.");
    }

    public function handleLeave(Player $player): void {
        $this->getGame()->sendMessage("§e" . $player->getName() . " §8(§e" . ($this->getGame()->getParticipant($player)?->getOriginName() ?? $player->getName()) . "§8) §chas left the game.");
        $this->getGame()->removeParticipant($player);
    }
    
    public function handleAnswer(Player $player, mixed $answer): void {
        $this->getGame()->addAnswer($player, $answer);
    }

    public function getGameId(): int {
        return $this->gameId;
    }

    public function getGame(): KahootGame {
        return KahootGameManager::getInstance()->getGameById($this->gameId);
    }
}