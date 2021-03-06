<?php

namespace xenialdan\PocketAI\listener;

use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\InventoryHolder;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\ContainerSetDataPacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use xenialdan\PocketAI\entity\Horse;
use xenialdan\PocketAI\entitytype\AIEntity;
use xenialdan\PocketAI\interfaces\Tamable;

/**
 * Class EventListener
 * @package xenialdan\PocketAI
 * Listens for all events regarding inventory
 */
class InventoryEventListener implements Listener{
	public $owner;

	/**
	 * EventListener constructor.
	 * @param Plugin $plugin
	 */
	public function __construct(Plugin $plugin){
		$this->owner = $plugin;
	}

	public function onDataPacket(DataPacketReceiveEvent $event){
		$player = $event->getPlayer();
		$packet = $event->getPacket();
		switch ($packet::NETWORK_ID){
			case InventoryTransactionPacket::NETWORK_ID: {
				/** @var InventoryTransactionPacket $packet */
				$event->setCancelled($this->handleInventoryTransaction($packet, $player));
				break;
			}
			case InteractPacket::NETWORK_ID: {
				/** @var InteractPacket $packet */
				$event->setCancelled($this->handleInteract($packet, $player));
				break;
			}
		}
	}

	/**
	 * Don't expect much from this handler. Most of it is roughly hacked and duct-taped together.
	 *
	 * @param InteractPacket $packet
	 * @param Player $player
	 * @return bool
	 */
	public function handleInteract(InteractPacket $packet, Player $player): bool{
		switch ($packet->action){
			case InteractPacket::ACTION_OPEN_INVENTORY:
				/*if (Loader::isRiding($player)){*/
				$target = $player->getServer()->findEntity($packet->target, $player->getLevel());
				if (is_null($target)){
					return false;
				}
				if (!$target instanceof AIEntity) return false;
				if ($target instanceof InventoryHolder/* && $target instanceof Tamable && $target->isTamed()*/){
					return $this->onInventoryOpen($target, $player);
				}
				return true;
				#}
				break;
			case InteractPacket::ACTION_MOUSEOVER:
				$target = $player->getServer()->findEntity($packet->target, $player->getLevel());
				if (is_null($target)){
					return false;
				}
				if (!$target instanceof AIEntity) return false;
				return $this->onHover($target, $player);
				break;
		}

		return false;
	}

	/**
	 * Don't expect much from this handler. Most of it is roughly hacked and duct-taped together.
	 *
	 * @param InventoryTransactionPacket $packet
	 * @param Player $player
	 * @return bool
	 */
	public function handleInventoryTransaction(InventoryTransactionPacket $packet, Player $player): bool{
		switch ($packet->transactionType){
			case InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY: {
				$type = $packet->trData->actionType;
				switch ($type){
					case InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_INTERACT: {
						$target = $player->getLevel()->getEntity($packet->trData->entityRuntimeId);
						if (is_null($target)){
							return false;
						}
						if (!$target instanceof AIEntity) return false;
						return ($player->isSneaking() ? $this->onSneakRightClick($target, $player) : $this->onRightClick($target, $player));
					}
				}
				break;
			}
			default: {
			}
		}

		return false;
	}

	private function onRightClick(Entity $target, Player $player){//TODO move to AIEntity for better handling
		$itemInHand = $player->getInventory()->getItemInHand();
		$itemInHandId = $itemInHand->getId();
		switch ($itemInHandId){
			default:
				return true; //cancel all item events - for now.
		}
		return false;
	}

	private function onSneakRightClick(Entity $target, Player $player){//TODO move to AIEntity for better handling
		if ($target instanceof AIEntity){
			if ($target instanceof InventoryHolder/* && $target instanceof Tamable && $target->isTamed()*/){
				return $this->onInventoryOpen($target, $player);
			}
		}
		return false;
	}

	public function onInventoryOpen(InventoryHolder $inventoryHolder, Player $player){ //TODO other entities //TODO move to AIEntity for better handling
		if ($inventoryHolder instanceof AIEntity && !is_null($inventoryHolder->getInventory())){
			var_dump($inventoryHolder->getInventory()->getName());
			var_dump($inventoryHolder->getInventory()->getNetworkType());
			$player->addWindow($inventoryHolder->getInventory());
			return true;
		}
		return false;
	}

	private function onHover(Entity $target, Player $player){//TODO move to AIEntity for better handling
		$player->setDataProperty(Entity::DATA_INTERACTIVE_TAG, Entity::DATA_TYPE_STRING, $player->getInventory()->getItemInHand()->__toString()); //TODO getAction/getActionName
		return true;
	}
}