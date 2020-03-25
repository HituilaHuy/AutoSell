<?php

namespace LamPocketVN\AutoSell;

use pocketmine\Player;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\block\BlockBreakEvent;

use pocketmine\item\Item;

use pocketmine\plugin\PluginBase;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use onebone\economyapi\EconomyAPI;

class Main extends PluginBase implements Listener {

	private $config;
	private $mode = [];
	
	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->saveDefaultConfig();
		$this->config = new  Config($this->getDataFolder() . "sell.yml" , Config::YAML);
	}

	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$this->mode[$player->getName()] = "off";
	}
	
	public function replaceVars(string $string, array $vars) : string{ 
		foreach($vars as $key => $value){
			$str = str_replace("{" . $key . "}", $value, $str);
		}
		return $str;
	}
	
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($command->getName()){
			case "autosell":
			if(!$sender->hasPermission("autosell.command")){
				$sender->sendMessage(TextFormat::colorize($this->msg["error.permission"]));
				return true;
			}
			switch($args[0]){
				case "on":
				$sender->sendMessage("§aAuto sell turned on!");
				$this->mode[$sender->getName()] = "on";
				break;
				case "off":
				$sender->sendMessage("§cAuto sell turned off!");
				$this->mode[$sender->getName()] = "off";
				break;
				default:
				$sender->sendMessage("§cUsage:§7 /autosell <on/off>");
				break;
			}
		}
		return true;
	}
	
	public function sell(Player $player){
		$items = $player->getInventory()->getContents();
		foreach($items as $item){
			if($this->config->get($item->getId()) !== null && $this->config->get($item->getId()) > 0){
				$price = $this->cfg->get($item->getId()) * $item->getCount();
				EconomyAPI::getInstance()->addMoney($player, $price);
				$player->getInventory()->remove($item);
			}
		}
	}
	
	public function onBreak(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();
		foreach($event->getDrops() as $drop){
			if(!$player->getInventory()->canAddItem($drop)){
				if($this->mode[$player->getName()] === "on"){
					$this->sell($player);
					$player->sendMessage("§aSell all your contents");
				}
			}
		}
	}

	public function onQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		if(isset($this->mode[$player->getName())])){
			unset($this->mode[$player->getName())]);
		}
	}
}
