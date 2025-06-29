<?php

declare(strict_types=1);

namespace ecstsy\BattleSense\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\BattleSense\commands\sub\AboutCommand;
use ecstsy\BattleSense\commands\sub\ReloadCommand;
use ecstsy\BattleSense\Loader;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as C;

final class BattleSenseCommand extends BaseCommand
{
    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerSubCommand(new ReloadCommand(Loader::getInstance(), "reload", "Reload all configurations"));
        $this->registerSubCommand(new AboutCommand(Loader::getInstance(), "about", "Shows information about plugin"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $lines = [];
        $lines[] = "&r&bAvailable BattleSense Commands:";
        $lines[] = "  &a/about &7- Shows information about plugin";

        if ($sender->hasPermission("battlesense.command.reload")) {
            $lines[] = "  &a/reload &7- Reload all configurations";
        }

        $sender->sendMessage(C::colorize(implode("\n", $lines)));
    }

    public function getPermission(): string
    {
        return "battlesense.command";
    }
}