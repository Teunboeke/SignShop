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
