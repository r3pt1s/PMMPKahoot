<?php

namespace r3pt1s\kahoot;

use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use r3pt1s\kahoot\command\KahootCommand;
use r3pt1s\kahoot\command\KahootLeaveCommand;
use r3pt1s\kahoot\game\template\TemplateManager;
use r3pt1s\kahoot\listener\EventListener;

class Kahoot extends PluginBase {
    use SingletonTrait;

    public const PREFIX = "§2§lKahoot §r§8» §7";

    protected function onEnable(): void {
        self::setInstance($this);
        if (!file_exists($this->getDataFolder() . "creations/")) mkdir($this->getDataFolder() . "creations/");
        $this->saveDefaultConfig();

        if (($p = $this->get("public-lobbies.permissionToCreate", "none")) !== "none") {
            DefaultPermissions::registerPermission(new Permission($p), [PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR)]);
        }

        if (($p = $this->get("create-templates.permissionToCreate", "none")) !== "none") {
            DefaultPermissions::registerPermission(new Permission($p), [PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR)]);
        }

        TemplateManager::getInstance()->loadTemplates();

        $this->getServer()->getCommandMap()->registerAll("pmmpkahoot", [new KahootCommand(), new KahootLeaveCommand()]);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }

    public function get(string $key, mixed $default = null): mixed {
        return $this->getConfig()->getNested($key, $default);
    }
}