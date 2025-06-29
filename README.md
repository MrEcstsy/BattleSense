# BattleSense

BattleSense is a PocketMine-MP plugin that provides advanced PvP and PvE statistics, summaries, and configurable feedback for your Minecraft Bedrock server.

## Features

- 📊 **PvP and PvE Fight Summaries**  
  Get detailed breakdowns of damage dealt, combos, healing used, and more after every fight.

- 🛠️ **Configurable Output**  
  Choose between chat, actionbar, or title for displaying summaries.

- 🌐 **Multi-language Support**  
  Easily create or translate or customize all messages via language files.

## Commands

| Command            | Description                          | Permission                    |
|--------------------|--------------------------------------|-------------------------------|
| `/battlesense`     | Lists all BattleSense commands       | battlesense.command           |
| `/battlesense about` | Shows plugin info                  | battlesense.command           |
| `/battlesense reload` | Reloads all configs (admin only)  | battlesense.command.reload    |

## Installation

1. Download the latest `.phar` from [Poggit Releases](https://poggit.pmmp.io/p/BattleSense).
2. Place it in your server's `plugins/` folder.
3. Restart or reload your server.

## Configuration

- All settings are in `/BattleSense/config.yml`.
- Language files are in `/BattleSense/locale/`.

## Example PvP Summary

```
You killed Steve!
• Total Damage Dealt: 8
• Highest Combo: 3 hits
• Damage Breakdown:
   - Sword (4) (2x)
   - Air (1)
• Final Blow: Sword (4)
```
