<?php

declare(strict_types=1);

namespace ecstsy\BattleSense\commands\sub;

use CortexPE\Commando\BaseSubCommand;
use ecstsy\BattleSense\Loader;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as C;

final class ReloadCommand extends BaseSubCommand {
    
    public function prepare(): void {
        $this->setPermission($this->getPermission());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $start = microtime(true);

        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");
        $config->reload();

        Loader::getLanguageManager()->reload();

        $elapsed = round((microtime(true) - $start) * 1000, 2); // ms

        $sender->sendMessage(C::colorize("&r&l&aAll BattleSense configurations have been reloaded! &r&7({$elapsed}ms)"));
    }

    public function getPermission(): string
    {
        return "battlesense.command.reload";
    }
}