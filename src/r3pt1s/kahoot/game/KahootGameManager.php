<?php

namespace r3pt1s\kahoot\game;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use r3pt1s\kahoot\game\template\Template;
use r3pt1s\kahoot\game\util\Utils;
use r3pt1s\kahoot\Kahoot;

class KahootGameManager {
    use SingletonTrait;

    /** @var array<KahootGame> */
    private array $games = [];

    public function __construct() {
        self::setInstance($this);
    }

    public function createGame(Player $creator, Template $template, GameSettings $settings, bool $public): void {
        $this->games[$id = Utils::nextGameId()] = ($game = new KahootGame(
            $id,
            Utils::generateInvCode(),
            $creator->getName(),
            $template,
            $settings,
            $public
        ));

        $creator->sendMessage(Kahoot::PREFIX . "§7The invitation code is: §e" . $game->getInvitationCode());

        if ($public) {
            Server::getInstance()->broadcastMessage(Kahoot::PREFIX . "§e" . $creator->getName() . " §7is hosting a §9PUBLIC §2Kahoot §7game! Join with §8'§e/kahoot§8'§7!");
        }
    }

    public function removeGame(KahootGame $game): void {
        if (isset($this->games[$game->getGameId()])) unset($this->games[$game->getGameId()]);
    }

    public function isPlaying(Player|string $player): bool {
        return count(array_filter($this->games, fn(KahootGame $game) => $game->isParticipant($player))) > 0;
    }

    public function getGameOfPlayer(Player|string $player): ?KahootGame {
        return array_values(array_filter($this->games, fn(KahootGame $game) => $game->isParticipant($player)))[0] ?? null;
    }

    public function getGameById(int $id): ?KahootGame {
        return $this->games[$id] ?? null;
    }

    public function getGameByCode(string $code): ?KahootGame {
        return array_values(array_filter($this->games, fn(KahootGame $game) => $game->getInvitationCode() == $code))[0] ?? null;
    }

    /** @return array<KahootGame> */
    public function getPublicGames(): array {
        return array_filter($this->games, fn(KahootGame $game) => $game->isPublicLobby());
    }

    public function getGames(): array {
        return $this->games;
    }
}