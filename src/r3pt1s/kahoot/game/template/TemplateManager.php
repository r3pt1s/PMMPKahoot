<?php

namespace r3pt1s\kahoot\game\template;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use r3pt1s\kahoot\game\question\GameQuestion;
use r3pt1s\kahoot\Kahoot;

class TemplateManager {
    use SingletonTrait;

    /** @var array<Template> */
    private array $gameTemplates = [];

    public function __construct() {
        self::setInstance($this);
    }

    public function loadTemplates(): void {
        foreach (array_diff(scandir($base = Kahoot::getInstance()->getDataFolder() . "creations/"), [".", ".."]) as $file) {
            $name = pathinfo($base . $file, PATHINFO_FILENAME);
            $config = $this->getConfig($name);
            foreach ($config->getAll() as $template) {
                if (($template = Template::fromArray($template)) !== null) {
                    $this->gameTemplates[$template->getName()] = $template;
                }
            }
        }
    }

    public function createBaseTemplate(Player $player, string $id, string $name, string $desc, int $maxPoints): void {
        $this->gameTemplates[$id] = ($template = new Template($id, $player->getName(), $name, $desc, [], $maxPoints));
        $config = $this->getConfig($player);
        $config->set($id, $template->toArray());
        $config->save();
    }

    public function saveTemplate(Template $template): void {
        $config = $this->getConfig($template->getCreator());
        $config->set($template->getId(), $template->toArray());
        $config->save();
    }

    public function removeTemplate(Template|string $template): void {
        $template = $template instanceof Template ? $template : $this->gameTemplates[$template];
        if ($template === null) return;
        if (isset($this->gameTemplates[$template->getId()])) unset($this->gameTemplates[$template->getId()]);
        $config = $this->getConfig($template->getCreator());
        $config->remove($template->getId());
        $config->save();
    }

    public function getGameTemplate(string $id): ?Template {
        return $this->gameTemplates[$id] ?? null;
    }

    /** @return array<Template> */
    public function getGameTemplates(Player|string $player): array {
        $player = $player instanceof Player ? $player->getName() : $player;
        return array_filter($this->gameTemplates, fn(Template $template) => $template->getCreator() == $player);
    }

    /** @return array<Template> */
    public function getPlayableGameTemplates(): array {
        return array_filter($this->gameTemplates, fn(Template $template) => count($template->getQuestions()) > 0);
    }

    public function getAllGameTemplates(): array {
        return $this->gameTemplates;
    }

    private function getConfig(Player|string $player): Config {
        $player = $player instanceof Player ? $player->getName() : $player;
        return new Config(Kahoot::getInstance()->getDataFolder() . "creations/" . $player . ".json", Config::JSON);
    }
}