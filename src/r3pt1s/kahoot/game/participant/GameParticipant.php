<?php

namespace r3pt1s\kahoot\game\participant;

use pocketmine\player\Player;
use pocketmine\Server;

class GameParticipant {

    private int $points = 0;

    public function __construct(
        private readonly string $originName,
        private readonly ?string $customName,
        private readonly bool $isHost
    ) {}

    public function addPoints(int $amount): int {
        $this->points += $amount;
        return $amount;
    }

    public function getOriginName(): string {
        return $this->originName;
    }

    public function getOrigin(): ?Player {
        return Server::getInstance()->getPlayerExact($this->originName);
    }

    public function getCustomName(): string {
        return $this->customName ?? $this->originName;
    }

    public function isHost(): bool {
        return $this->isHost;
    }

    public function getPoints(): int {
        return $this->points;
    }
}