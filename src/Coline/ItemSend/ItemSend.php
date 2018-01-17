<?php
namespace Coline\ItemSend;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\utils\Utils; 
use pocketmine\Server;
use pocketmine\item\Item;
use pocketmine\network\protocol\AddItemEntityPacket;

class ItemSend extends PluginBase implements Listener {
     const Prfix = '§f[§aItemSend§f]§e ';
     protected $scope = [];
     public function onEnable(){
         (new \ColineServices\Updater($this, 199, $this->getFile()))->update();
            $this->saveDefaultConfig();
               if(!file_exists($this->getDataFolder().'lang.json')){
                   $this->languageInitialization();
               }
               
           
         $this->getServer()->getPluginManager()->registerEvents($this, $this);
   }
   public function onDisable() {
       unlink($this->getDataFolder().'lang.json');
   }
   public function onLogin(PlayerLoginEvent $event){
         $player = $event->getPlayer();
         if(@$this->scope[$player->getName()]['displayMessage'] != null){
             $player->sendMessage($this->scope[$player->getName()]['displayMessage'] );
             $this->scope[$player->getName()]['displayMessage']  = null;
         }
      if(@$this->scope[$player->getName()]['additem']  != null){
          $player->sendMessage($this->scope[$player->getName()]['displayMessage'] );
             $itemdata = explode(':', $this->scope[$player->getName()]['additem']);
              $id = $itemdata[0];
              $damage = $itemdata[1];
              $count = $itemdata[2]; 
              $player->getInventory()->addItem(Item::get($id, $damage, $count));
              unset($this->scope[$player->getName()]);
      }
       
   }

   public function onChat(PlayerChatEvent $event) {
            $player = $event->getPlayer();
            if($this->scope[$player->getName()]['getchat'] == true){
                $message = $event->getMessage();
                if(is_numeric($message)){
                     $itemdata = explode(':', $this->scope[$player->getName()]['item']); // TODO: Передавать класс
                    $id = $itemdata[0];
                    $damage = $itemdata[1];
                    $count = $itemdata[2];
                    
                    if($message <= $count){
                        $this->scope[$player->getName()]['getchat'] = false;
                        $event->setCancelled(TRUE);
                        if($this->getOnline($this->scope[$player->getName()]['sendto'])){
                            $player->getInventory()->removeItem(Item::get($id, $damage, $message));
                            $this->scope[$this->scope[$player->getName()]['sendto']]['item'] =  $id.':'.$damage.':'.$message;
                            $this->scope[$this->scope[$player->getName()]['sendto']]['sendby'] =  $player->getName();
                            $this->getServer()->getPlayer($this->scope[$player->getName()]['sendto'])->sendMessage(ItemSend::Prfix.$this->lang('player').": ".$player->getName().' '.$this->lang("send_you").' '.Item::get($id)->getName()." ". $this->lang('in_count').": ".$message.". ".$this->lang("is_send"));
                            $this->getServer()->getScheduler()->scheduleDelayedTask(new tpTimer($this, $this->scope[$player->getName()]['sendto']), 20*120);
                            $player->sendMessage(ItemSend::Prfix.$this->lang("success_sendto").$this->scope[$player->getName()]['sendto']);
                        } else {
                            $player->sendMessage(ItemSend::Prfix.$this->lang("no_online"));
                             $event->setCancelled(TRUE);
                        }
                    } else {
                        $player->sendMessage(ItemSend::Prfix.$this->lang("no_blocks"));
                         $event->setCancelled(TRUE);
                    }
                   
                }else{
                    $player->sendMessage(ItemSend::Prfix.$this->lang("is_numeric"));
                     $event->setCancelled(TRUE);
                }
            }
        }   
     public function onPlayerTouch(PlayerInteractEvent $event){
         $player = $event->getPlayer();
         if(@$this->scope[$player->getName()]['senditem'] == TRUE){
             if($event->getItem()->getId() != 0){
                 
             $sendto = $this->scope[$player->getName()]['sendto']; 
             unset($this->scope[$player->getName()]);
             $this->scope[$player->getName()]['item'] = $event->getItem()->getId().':'.$event->getItem()->getDamage().':'.$event->getItem()->getCount();
             $this->scope[$player->getName()]['getchat'] = true;
             $this->scope[$player->getName()]['sendto'] = $sendto;
             $player->sendMessage(ItemSend::Prfix.$this->lang("item_successfully_received"));
             $event->setCancelled(TRUE);
         } else {
             $player->sendMessage(ItemSend::Prfix.$this->lang("air_transport_forbidden"));
         }
         
             }
     }
	
         public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
             if($sender instanceof \pocketmine\command\ConsoleCommandSender){
                 return FALSE;
             }

		switch($command->getName()){
                    case 'itemsend':
                        if(count($args) == 0){
                              $sender->sendMessage($this->lang("all_cmd"));
                            break; 
                        }
                        $player = $sender;
                          switch (@$args[0]) {
                            case 'send':
                                if($args[1] != NULL){
                                    if($this->getOnline($args[1])){
                                        if($sender->getGamemode() != 0){
                                            $sender->sendMessage(ItemSend::Prfix.$this->lang('no_creative'));
                                        } else {
                                            $this->scope[$player->getName()]['senditem'] = TRUE;
                                            $this->scope[$player->getName()]['sendto']  = $this->getServer()->getPlayer($args[1])->getName();
                                        $sender->sendMessage(ItemSend::Prfix.$this->lang("send_successfully"));
                                        }
                                    } else {
                                        $sender->sendMessage(ItemSend::Prfix.$this->lang("offline_player"));
                                    }
                                } else {
                                    $sender->sendMessage(ItemSend::Prfix.$this->lang("no_player"));
                                } 
                                break;
                            case 'accept':
                                if(@$this->scope[$player->getName()]['item'] != NULL){
                                     $itemdata = explode(':', $this->scope[$player->getName()]['item']);
                                    $id = $itemdata[0];
                                    $damage = $itemdata[1];
                                    $count = $itemdata[2]; 
                                    if($sender->getInventory()->canAddItem(Item::get($id, $damage, $count))){
                                  
                                   $sender->getInventory()->addItem(Item::get($id, $damage, $count));
                                   @$this->scope[$player->getName()]['item'] = null;
                                   $sender->sendMessage(ItemSend::Prfix.$this->lang('success_taken'));
                                   $this->getServer()->getPlayer($this->scope[$player->getName()]['sendby'])->sendMessage(ItemSend::Prfix.$this->lang("player").": ".$sender->getName()." ".$this->lang('received_request'));
                                 }else{
                                    $sender->sendMessage($this->lang('no_inventory'));
                                 }
                                   
                                    } else {
                                    $sender->sendMessage(ItemSend::Prfix.$this->lang("no_requests"));
                                }
                                break;
                                case 'deny':
                                     if(@$this->scope[$player->getName()]['item'] != NULL){
                                        $itemdata = explode(':',$this->scope[$player->getName()]['item']);
                                        $id = $itemdata[0];
                                        $damage = $itemdata[1];
                                        $count = $itemdata[2]; 
                                        $this->scope[$player->getName()]['item'] = null;
                                        $this->getServer()->getPlayer($this->scope[$player->getName()]['sendby'])->getInventory()->addItem(Item::get($id, $damage, $count));
                                        $this->getServer()->getPlayer($this->scope[$player->getName()]['sendby'])->sendMessage(ItemSend::Prfix.$this->lang('player').":".$sender->getName().$this->lang('undo_send'));
                                        $sender->sendMessage(ItemSend::Prfix.$this->lang('success_canceled'));

                                     } else {
                                   $sender->sendMessage(ItemSend::Prfix.$this->lang("no_requests"));
                                   
                                     }
                                break;
                            case 'setlang':
                                
                                break;
                                 default:
                                
                                $sender->sendMessage(ItemSend::Prfix.$this->lang('no_sub-command'));
                                
                                 break;
                          }
                }
                return true;
         }
                public function getOnline($nickname) {
                   if($player = $this->getServer()->getPlayer($nickname)){
                       return TRUE;
            } else{
                return false;
            }
                   
         }
         public function languageInitialization(){
             switch ($this->getConfig()->get("lang")) {
                 case 'rus':
                    $this->saveResource('rus.json');
                     if(file_exists($this->getDataFolder().'lang.json')){
                         unlink($this->getDataFolder().'lang.json');
                     }
                     rename($this->getDataFolder().'rus.json', $this->getDataFolder().'lang.json');

                     break;
                     case 'eng':
                    $this->saveResource('eng.json');
                     if(file_exists($this->getDataFolder().'lang.json')){
                         unlink($this->getDataFolder().'lang.json');
                     }
                     rename($this->getDataFolder().'eng.json', $this->getDataFolder().'lang.json');

                     break;
                      case 'jpn':
                    $this->saveResource('jpn.json');
                     if(file_exists($this->getDataFolder().'lang.json')){
                         unlink($this->getDataFolder().'lang.json');
                     }
                     rename($this->getDataFolder().'jpn.json', $this->getDataFolder().'lang.json');

                     break;

                 default:
                     $this->saveResource('rus.json');
                     if(file_exists($this->getDataFolder().'lang.json')){
                         unlink($this->getDataFolder().'lang.json');
                     }
                     rename($this->getDataFolder().'rus.json', $this->getDataFolder().'lang.json');
                     break;
             }
         }

         public function lang($phrase){
        $file = json_decode(file_get_contents($this->getDataFolder()."lang.json"), TRUE);
        return $file["{$phrase}"];
		}
          public function curl_get_contents($url){
  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
  $data = curl_exec($curl);
  curl_close($curl);
  return $data;
          }
}

