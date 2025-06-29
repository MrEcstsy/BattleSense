<?php

declare(strict_types=1);

namespace ecstsy\BattleSense\utils;

use ecstsy\BattleSense\listeners\BattleSenseListener;

# srry was feeling a lil frisky.. 
final class Utils { public static function initPvPStats(string $attacker, string $victim, float $now): void { if (!isset(BattleSenseListener::$pvpStats[$attacker][$victim])) { BattleSenseListener::$pvpStats[$attacker][$victim] = ['damageDealt'=>0,'damageReceived'=>0,'currentCombo'=>0,'highestCombo'=>0,'lastHitTime'=>0,'healingUsed'=>[],'damageBreakdown'=>[],'fightStart'=>$now,'fightEnd'=>0,'finalBlow'=>null]; } } }