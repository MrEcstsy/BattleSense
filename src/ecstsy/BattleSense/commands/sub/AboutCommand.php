<?php

declare(strict_types=1);

namespace ecstsy\BattleSense\commands\sub;

use CortexPE\Commando\BaseSubCommand;
use ecstsy\BattleSense\Loader;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as C;

final class AboutCommand extends BaseSubCommand 
{

    public function prepare(): void {
        $this->setPermission($this->getPermission());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
            $plugin = Loader::getInstance();
            $name = "&l&b" . $plugin->getName() . "&r";
            $version = "&7v" . $plugin->getDescription()->getVersion();
            $author = "&7Author(s): " . implode(", ", $plugin->getDescription()->getAuthors());

            $sender->sendMessage(C::colorize("{$name} {$version}\n{$author}"));
    }

    public function getPermission(): string
    {
        return "battlesense.command";
    }
}