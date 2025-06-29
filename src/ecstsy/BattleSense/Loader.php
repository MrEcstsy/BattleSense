<?php

declare(strict_types=1);

namespace ecstsy\BattleSense;

use ecstsy\BattleSense\commands\BattleSenseCommand;
use ecstsy\BattleSense\commands\ReloadCommand;
use ecstsy\BattleSense\listeners\BattleSenseListener;
use ecstsy\MartianUtilities\managers\LanguageManager;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use JackMD\ConfigUpdater\ConfigUpdater;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

/**
 * Made with ❤ by ecstsylove
 * 
 * Started on:
 * 6-27-2025
 */
final class Loader extends PluginBase {
    use SingletonTrait;

    const CONFIG_VERSIONS = ["config" => 1, "locale/en_us" => 1];

    private static LanguageManager $languageManager;

    protected function onLoad(): void
    {
        self::setInstance($this);
    }

    protected function onEnable(): void
    {

        foreach (self::CONFIG_VERSIONS as $file => $version) {
            $this->saveResource($file . ".yml");

            ConfigUpdater::checkUpdate($this, GeneralUtils::getConfiguration($this, $file . ".yml"), 'version', $version);
        }

        $listeners = [
            new BattleSenseListener(),
        ];

        foreach ($listeners as $listener) {
            $this->getServer()->getPluginManager()->registerEvents($listener, $this);
        }

        $config = GeneralUtils::getConfiguration($this, "config.yml");
        self::$languageManager = new LanguageManager($this, $config->getNested("settings.language", "en-us"));

        $this->getServer()->getCommandMap()->registerAll("BattleSense", [
            new BattleSenseCommand($this, "battlesense", "List all martian BattleSense commands", ["mbs"])
        ]);
    }

    public static function getLanguageManager(): LanguageManager
    {
        return self::$languageManager;
    }
}