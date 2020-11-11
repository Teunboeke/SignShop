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
