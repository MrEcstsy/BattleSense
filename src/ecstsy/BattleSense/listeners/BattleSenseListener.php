<?php

declare(strict_types=1);

namespace ecstsy\BattleSense\listeners;

use ecstsy\BattleSense\Loader;
use ecstsy\BattleSense\utils\Utils;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class BattleSenseListener implements Listener
{

    private array $damageMap = [];
    public static array $pvpStats = [];

    public function onPvPStatTrack(EntityDamageByEntityEvent $event): void
    {
        $entity = $event->getEntity();
        $damager = $event->getDamager();

        if ($damager instanceof Player && $entity instanceof Player) {
            $now = microtime(true);
            $aName = $damager->getName();
            $vName = $entity->getName();
            $damage = $event->getFinalDamage();

            Utils::initPvPStats($aName, $vName, $now);
            Utils::initPvPStats($vName, $aName, $now);

            self::$pvpStats[$aName][$vName]['damageDealt'] += $damage;
            self::$pvpStats[$vName][$aName]['damageReceived'] += $damage;

            $last = self::$pvpStats[$aName][$vName]['lastHitTime'];
            
            if ($now - $last <= 3) {
                self::$pvpStats[$aName][$vName]['currentCombo']++;
            } else {
                self::$pvpStats[$aName][$vName]['currentCombo'] = 1;
            }

            self::$pvpStats[$aName][$vName]['lastHitTime'] = $now;

            if (self::$pvpStats[$aName][$vName]['currentCombo'] > self::$pvpStats[$aName][$vName]['highestCombo']) {
                self::$pvpStats[$aName][$vName]['highestCombo'] = self::$pvpStats[$aName][$vName]['currentCombo'];
            }
                        
            $cause = $event->getCause();
            $causeName = match ($cause) {
                EntityDamageEvent::CAUSE_ENTITY_ATTACK => $damager->getInventory()->getItemInHand()->getName(),
                EntityDamageEvent::CAUSE_PROJECTILE => "Projectile",
                EntityDamageEvent::CAUSE_FALL => "Fall",
                EntityDamageEvent::CAUSE_LAVA => "Lava",
                EntityDamageEvent::CAUSE_FIRE => "Fire",
                EntityDamageEvent::CAUSE_MAGIC => "Magic",
                default => "Other"
            };
            self::$pvpStats[$aName][$vName]['damageBreakdown'][] = ['source' => $causeName, 'amount' => $damage];
        }
    }

    public function onPlayerDeath(EntityDeathEvent $event): void
    {
        $victim = $event->getEntity();
        if (!$victim instanceof Player) return;

        $lastDamage = $victim->getLastDamageCause();
        if ($lastDamage instanceof EntityDamageByEntityEvent && $lastDamage->getDamager() instanceof Player) {
            $killer = $lastDamage->getDamager();
            $vName = $victim->getName();
            if (!$killer instanceof Player) return;
            $kName = $killer->getName();

            $now = microtime(true);
            self::$pvpStats[$kName][$vName]['fightEnd'] = $now;
            self::$pvpStats[$vName][$kName]['fightEnd'] = $now;

            $weapon = $killer->getInventory()->getItemInHand()->getName();
            $damage = $lastDamage->getFinalDamage();
            self::$pvpStats[$kName][$vName]['finalBlow'] = ['weapon' => $weapon, 'damage' => $damage];

            $lang = Loader::getLanguageManager();
            $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml")->getNested("settings.modules.pvp-summary", []);

            foreach ([[$killer, $victim, true], [$victim, $killer, false]] as [$player, $opponent, $isWinner]) {
                $pName = $player->getName();
                $oName = $opponent->getName();
                $stats = self::$pvpStats[$pName][$oName] ?? null;
                if (!is_array($stats)) continue;

                $lines = [];
                $lines[] = $lang->getNested($isWinner ? "pvp-summary.winner-title" : "pvp-summary.loser-title")
                    ? str_replace(["{victim}", "{killer}"], [$oName, $oName], $lang->getNested($isWinner ? "pvp-summary.winner-title" : "pvp-summary.loser-title"))
                    : ($isWinner ? "You killed $oName!" : "You were killed by $oName!");

                if ($config["show-damage-dealt"] ?? true) {
                    $lines[] = str_replace("{damage}", (string)round($stats['damageDealt'], 1), $lang->getNested("pvp-summary.damage-dealt"));
                }
                if ($config["show-damage-recieved"] ?? true) {
                    $lines[] = str_replace("{damage}", (string)round($stats['damageReceived'], 1), $lang->getNested("pvp-summary.damage-received"));
                }
                if ($config["show-combo"] ?? true) {
                    $lines[] = str_replace("{combo}", (string)$stats['highestCombo'], $lang->getNested("pvp-summary.highest-combo"));
                }
                if (!empty($stats['healingUsed'])) {
                    $healStr = [];
                    foreach ($stats['healingUsed'] as $type => $count) {
                        $healStr[] = "$type ($count)";
                    }
                    $lines[] = str_replace("{healing}", implode(", ", $healStr), $lang->getNested("pvp-summary.healing-used"));
                }
                if (!empty($stats['damageBreakdown']) && ($config["show-damage-breakdown"] ?? true)) {
                    $lines[] = $lang->getNested("pvp-summary.damage-breakdown-title");

                    $grouped = [];
                    foreach ($stats['damageBreakdown'] as $entry) {
                        $key = $entry['source'] . '|' . $entry['amount'];
                        if (!isset($grouped[$key])) {
                            $grouped[$key] = [
                                'source' => $entry['source'],
                                'amount' => $entry['amount'],
                                'count' => 1
                            ];
                        } else {
                            $grouped[$key]['count']++;
                        }
                    }

                    $breakdownFormat = $lang->getNested("pvp-summary.damage-breakdown-line-grouped");

                    foreach ($grouped as $entry) {
                        if ($entry['count'] > 1) {
                            $lines[] = str_replace(
                                ["{source}", "{amount}", "{count}"],
                                [$entry['source'], round($entry['amount'], 1), $entry['count']],
                                $breakdownFormat
                            );
                        } else {
                            $lines[] = str_replace(
                                ["{source}", "{amount}"],
                                [$entry['source'], round($entry['amount'], 1)],
                                $lang->getNested("pvp-summary.damage-breakdown-line")
                            );
                        }
                    }
                }
                if (($config["show-kill-weapon"] ?? true) && is_array($stats['finalBlow']) && isset($stats['finalBlow']['weapon'], $stats['finalBlow']['damage'])) {
                    $lines[] = str_replace(
                        ["{weapon}", "{damage}"],
                        [$stats['finalBlow']['weapon'], (string)round($stats['finalBlow']['damage'], 1)],
                        $lang->getNested("pvp-summary.final-blow")
                    );
                }

                $summary = implode("\n", $lines);

                switch ($config['output'] ?? "chat") {
                    case "actionbar":
                        $player->sendActionBarMessage(C::colorize($summary));
                        break;
                    case "title":
                        $player->sendTitle(C::colorize($lines[0]), implode("\n", array_slice($lines, 1)));
                        break;
                    default:
                        $player->sendMessage(C::colorize($summary));
                }
            }

            unset(self::$pvpStats[$kName][$vName], self::$pvpStats[$vName][$kName]);
        }
    }

    public function onDamageMapUpdate(EntityDamageByEntityEvent $event): void
    {
        $entity = $event->getEntity();
        $damager = $event->getDamager();

        if ($entity instanceof Living && $damager instanceof Player) {
            $eid = spl_object_id($entity);
            $name = $damager->getName();
            $damage = $event->getFinalDamage();

            $this->damageMap[$eid][$name] = ($this->damageMap[$eid][$name] ?? 0) + $damage;
        }
    }

    public function onEntityDeath(EntityDeathEvent $event): void
    {
        $entity = $event->getEntity();
        $eid = spl_object_id($entity);

        if (!isset($this->damageMap[$eid])) {
            return;
        }

        $lastDamage = $entity->getLastDamageCause();
        if ($entity instanceof Player && $lastDamage instanceof EntityDamageByEntityEvent && $lastDamage->getDamager() instanceof Player) {
            return;
        }

        $damageList = $this->damageMap[$eid];
        arsort($damageList);

        $lang = Loader::getLanguageManager();
        $title = $lang->getNested("kill-summary.title");
        $lineFormat = $lang->getNested("kill-summary.line");
        $messageFormat = $lang->getNested("kill-summary.message");

        $lines = [];
        $rank = 1;

        foreach ($damageList as $player => $damage) {
            $lines[] = str_replace(
                ["{rank}", "{player}", "{damage}"],
                [$rank, $player, round($damage, 1)],
                $lineFormat
            );
            $rank++;
        }
        $summary = str_replace(
            ["{title}", "{lines}"],
            [$title, implode("\n", $lines)],
            $messageFormat
        );

        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml")->getNested("settings.modules.kill-summary", []);
        if (($config["enabled"] ?? true) && ($config["show-top-damage"] ?? true)) {
            foreach ($damageList as $playerName => $_) {
                $player = PlayerUtils::getPlayerByPrefix($playerName);
                if (!$player instanceof Player) {
                    continue;
                }

                switch ($config['output'] ?? "chat") {
                    case "actionbar":
                        $player->sendActionBarMessage(C::colorize($summary));
                        break;
                    case "title":
                        $player->sendTitle(C::colorize($title), implode("\n", $lines));
                        break;
                    default:
                        $player->sendMessage(C::colorize($summary));
                }
            }
        }
        unset($this->damageMap[$eid]);
    }
}
