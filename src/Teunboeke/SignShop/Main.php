<?php

namespace Teunboeke\SignShop;
  
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory; 

use onebone\economyapi\EconomyAPI;

#define TAG 1

class Main extends PluginBase implements Listener {
  	private $sell;
  	private $placeQueue;
  
  /**
	 *
   * @var Config
   */
  	private $sellSign, $lang;

		public function onEnable(){
			@mkdir($this->getDataFolder());
			
			$this->saveDefaultConfig();
			
			$this->sell = (new Config($this->getDataFolder()."Sell.yml", Config::YAML))->getAll();
			$this->getServer()->getPluginManager()->registerEvents($this, $this);
			$this->prepareLangPref();
			$this->placeQueue = [];
	}
	
		public function onDisable(){
			$cfg = new Config($this->getDataFolder()."Sell.yml", Config::YAML);
			$cfg->setAll($this->sell);
			$cfg->save();
	}
	
		private function prepareLangPref(){
			$this->lang = new Config($this->getDataFolder()."language.properties", Config::PROPERTIES, array(
							"wrong-format" => "§aPlease write your sign with right format",
							"item-not-support" => "§bItem %1 is not supported on EconomySell",
							"no-permission-create" => "§cYou don't have permission to create sell center",
							"sell-created" => "§aSell center has been created (%1 = %MONETARY_UNIT%%2)",
							"removed-sell" => "§aSell center has been removed",
							"creative-mode" => "§cYou are in creative mode",
							"no-permission-sell" => "§You don't have permission to sell item",
							"no-permission-break" => "§cYou don't have permission to break sell center",
							"tap-again" => "Are you sure to sell %1 (%MONETARY_UNIT%%2)? Tap again to confirm",
							"no-item" => "§cYou have no item to sell",
							"sold-item" => "§aYou have sold %1 of %2 for %MONETARY_UNIT%%3"
						));
			
					$this->sellSign = new Config($this->getDataFolder()."SellSign.yml", Config::YAML, array(
							"sell" => array(	
									"§l§cSELL",
									"§l§d%MONETARY_UNIT%%1",							
									"§l§e%2",
									"§b§lAmount : §l%3"
												)
								));
			}
	
		public function getMessage($key, $val = array("%1", "%2", "%3")){
					if($this->lang->exists($key)){
						return str_replace(array("%MONETARY_UNIT%", "%1","%2", "%3"), array(EconomyAPI::getInstance()->getMonetaryUnit(), $val[0], $val[1], $val[2]),$this->lang->get($key));
								}
					return "There's no message named \"$key\"";
				}
	
		public function onSignChange(SignChangeEvent $event){
			$tag = $event->getLine(0);
			if(($val = $this->checkTag($tag)) !== false){
					$player = $event->getPlayer();
					if(!$player->hasPermission ("economysell.sell.create")){
						$player->sendMessage($this->getMessage("no-permission-create"));
										return;
									}
							if(!is_numeric($event->getLine(1)) or !is_numeric($event->getLine(3))){
								$player->sendMessage($this->getMessage("wrong-format"));
								return;
							}
			$item = ItemFactory::fromString($event->getLine(2));
							if($item === false){
							$player->sendMessage($this->getMessage("item-not-support", array($event->getLine (2),"", "" )));
							return;
											}
				
							$block = $event->getBlock();
							$this->sell[$block->getX().":".$block->getY().":".$block->getZ().":".$player->getLevel()->getName()] = array(
													"x" => $block->getX(),
													"y" => $block->getY(),
													"z" => $block->getZ(),
													"level" => $player->getLevel()->getName(),
													"cost" => (int) $event->getLine(1),
													"item" =>  (int) $item->getID(),
													"itemName" => $item->getName(),
													"meta" => (int) $item->getDamage(),
													"amount" => (int) $event->getLine(3)
											);

							$player->sendMessage($this->getMessage("sell-created", [$item->getName(), (int)$event->getLine(3), ""]));
				
							$mu = EconomyAPI::getInstance()->getMonetaryUnit();
							$event->setLine(0, $val[0]);
							$event->setLine(1, str_replace(["%MONETARY_UNIT%", "%1"], [$mu, $event->getLine(1)], $val[1]));
							$event->setLine(2, str_replace(["%MONETARY_UNIT%", "%2"], [$mu, $item->getName()], $val[2]));
							$event->setLine(3, str_replace(["%MONETARY_UNIT%", "%3"], [$mu, $event->getLine(3)], $val[3]));
						}
					}
	
		public function onTouch(PlayerInteractEvent $event){
				if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
								return;
							}
		$block = $event->getBlock();
		$loc = $block->getX().":".$block->getY().":".$block->getZ().":".$block->getLevel()->getName();
		if(isset($this->sell[$loc])){
						$sell = $this->sell[$loc];
						$player = $event->getPlayer();

						if($player->getGamemode() % 2 === 1){
	$player->sendMessage($this->getMessage("creative-mode"));
											$event->setCancelled();
											return;
																}
						if(!$player->hasPermission("economysell.sell.sell")){
									$player->sendMessage($this->getMessage("no-permission-sell"));
									$event->setCancelled();
									return;
										}
						$cnt = 0;
						foreach($player->getInventory()->getContents() as $item){
								if($item->getID() == $sell["item"] and $item->getDamage() == $sell["meta"]){
														$cnt += $item->getCount();
													}
										}
						
			if(!isset($sell["itemName"])){
						$item = $this->getItem($sell["item"], $sell["meta"], $sell["amount"]);
						if($item === false){
						$item = $sell["item"].":".$sell["meta"];
											}else{
