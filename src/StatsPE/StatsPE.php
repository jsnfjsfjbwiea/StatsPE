<?php

namespace StatsPE;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;
use pocketmine\utils\Utils;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerFishEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use StatsPE\Updater\CheckVersionTask;
use StatsPE\Updater\UpdateTask;

class StatsPE extends PluginBase implements Listener
{
    public function onEnable()
    {
        if (!$this->getServer()->getName() === 'ClearSky') {
            $this->getLogger()->warning(TF::RED.'You are running this plugin on a not officially supported server software. Use with caution!');
        }
        @mkdir($this->getDataFolder());
        $this->saveResource('config.yml');
        $this->getServer()->getScheduler()->scheduleAsyncTask(new CheckVersionTask($this));
        $provider = strtolower($this->getConfig()->get('Provider'));
        if ($provider == 'json') {
            @mkdir($this->getDataFolder().'Stats');
        } elseif ($provider == 'mysql') {
            //Test Connection here and create database
        } else {
            $this->getLogger()->critical('Invalid provider: '.$provider.'!');
        }
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args)
    {
        if (strtolower($cmd) == 'stats') {
            if (count($args) == 0) {
                $this->showStats($sender, $sender->getName());

                return true;
            } elseif (count($args) == 1) {
                $this->showStats($sender, $args[0]);

                return true;
            } else {
                $sender->sendMessage(TF::RED.'Too many arguments!');

                return false;
            }
        } elseif (strtolower($cmd) == 'floatingstats') {
        }
    }

    public function saveData($player, $data)
    {
        if (strtolower($this->getConfig()->get('Provider')) == 'json') {
            fwrite(fopen($this->getDataFolder().'Stats/'.strtolower($player->getName()).'.json', 'w'), json_encode($data, JSON_PRETTY_PRINT));
        } elseif (strtolower($this->getConfig()->get('Provider')) == 'mysql') {
        }
    }

    public function getStats($player, $type, $data)
    {
        if ($player instanceof Player) {
            $player = $player->getName();
        }
        if (strtolower($type) == 'json') {
            if ($data == 'all') {
                return json_decode(file_get_contents($this->getDataFolder().'Stats/'.strtolower($player).'.json'), true);
            } else {
                return json_decode(file_get_contents($this->getDataFolder().'Stats/'.strtolower($player).'.json'), true)["$data"];
            }
        } elseif (strtolower($type) == 'mysql') {
        }
    }

    public function showStats($requestor, $target)
    {
        if ($target == 'CONSOLE') {
            $requestor->sendMessage(TF::RED.'You do not have permission to view Console stats!');
        } else {
            if (strtolower($this->getConfig()->get('Provider')) == 'json') {
                if (file_exists($this->getDataFolder().'Stats/'.strtolower($target).'.json')) {
                    $info = $this->getStats($target, 'json', 'all');
                    $requestor->sendMessage(TF::GOLD.'---Statistics for: '.TF::GREEN.$info['PlayerName'].TF::GOLD.'---');
                    if ($requestor->hasPermission('statspe.cmd.stats.advancedinfo')) {
                        $requestor->sendMessage(TF::AQUA.'Last ClientID: '.TF::LIGHT_PURPLE.$info['ClientID']);
                        $requestor->sendMessage(TF::AQUA.'Last ClientSecret: '.TF::LIGHT_PURPLE.$info['ClientSecret']);
                        $requestor->sendMessage(TF::AQUA.'XBoxAuthenticated: '.TF::LIGHT_PURPLE.$info['XBoxAuthenticated']);
                        $requestor->sendMessage(TF::AQUA.'Last IP: '.TF::LIGHT_PURPLE.$info['LastIP']);
                    }
                    $requestor->sendMessage(TF::AQUA.'First Join: '.TF::LIGHT_PURPLE.$info['FirstJoin']);
                    $requestor->sendMessage(TF::AQUA.'Last Join: '.TF::LIGHT_PURPLE.$info['LastJoin']);
                    $requestor->sendMessage(TF::AQUA.'Total Joins: '.TF::LIGHT_PURPLE.$info['JoinCount']);
                    $requestor->sendMessage(TF::AQUA.'Kills: '.TF::LIGHT_PURPLE.$info['KillCount']);
                    $requestor->sendMessage(TF::AQUA.'Deaths: '.TF::LIGHT_PURPLE.$info['DeathCount']);
                    $requestor->sendMessage(TF::AQUA.'K/D: '.TF::LIGHT_PURPLE.$info['KillCount'] / $info['DeathCount']);
                    $requestor->sendMessage(TF::AQUA.'Kicks: '.TF::LIGHT_PURPLE.$info['KickCount']);
                    $requestor->sendMessage(TF::AQUA.'Online Time: '.TF::LIGHT_PURPLE.$info['OnlineTime']);
                    $requestor->sendMessage(TF::AQUA.'Breaked Blocks: '.TF::LIGHT_PURPLE.$info['BlocksBreaked']);
                    $requestor->sendMessage(TF::AQUA.'Placed Blocks: '.TF::LIGHT_PURPLE.$info['BlocksPlaced']);
                    $requestor->sendMessage(TF::AQUA.'Chat Messages: '.TF::LIGHT_PURPLE.$info['ChatMessages']);
                    $requestor->sendMessage(TF::AQUA.'Catched Fishes: '.TF::LIGHT_PURPLE.$info['FishCount']);
                    $requestor->sendMessage(TF::AQUA.'Went to bed for: '.TF::LIGHT_PURPLE.$info['EnterBedCount'].TF::AQUA.' times');
                    $requestor->sendMessage(TF::AQUA.'Ate something for: '.TF::LIGHT_PURPLE.$info['EatCount'].TF::AQUA.' times');
                    $requestor->sendMessage(TF::AQUA.'Crafted something for: '.TF::LIGHT_PURPLE.$info['CraftCount'].TF::AQUA.' times');
                } else {
                    $requestor->sendMessage(TF::RED.'No Stats found for: '.TF::GOLD.$target."\n".TF::RED.'Please check your spelling.');
                }
            } elseif (strtolower($this->getConfig()->get('Provider')) == 'mysql') {
            }
        }
    }

    public function onJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        $provider = strtolower($this->getConfig()->get('Provider'));
        if ($provider == 'json') {
            if ($player->isXboxAuthenticated()) {
                $xa = 'true';
            } else {
                $xa = 'false';
            }
            $pn = $player->getName();
            if (file_exists($this->getDataFolder().'/Stats/'.$player->getName().'.json')) {
                $info = $this->getStats($player->getName(), 'JSON', 'all');
                $cid = $player->getClientId();
                $ip = $player->getAddress();
                $ls = date($this->getConfig()->get('TimeFormat'));
                $jc = $info['JoinCount'] + 1;
                $data = array(
                    'PlayerName' => $pn,
                      'ClientID' => $cid,
                      'ClientSecret' => $player->getClientSecret(),
                    'XBoxAuthenticated' => $xa,
                      'LastIP' => $ip,
                      'FirstJoin' => $info['FirstJoin'],
                      'LastJoin' => $ls,
                      'JoinCount' => $jc,
                      'KillCount' => $info['KillCount'],
                      'DeathCount' => $info['DeathCount'],
                      'KickCount' => $info['KickCount'],
                      'OnlineTime' => $info['OnlineTime'],
                      'BlocksBreaked' => $info['BlocksBreaked'],
                      'BlocksPlaced' => $info['BlocksPlaced'],
                      'ChatMessages' => $info['ChatMessages'],
                      'FishCount' => $info['FishCount'],
                      'EnterBedCount' => $info['EnterBedCount'],
                      'EatCount' => $info['EatCount'],
                      'CraftCount' => $info['CraftCount'],
                );
                $this->saveData($player, $data);
            } else {
                $fp = date($this->getConfig()->get('TimeFormat'));
                $cid = $player->getClientId();
                $ip = $player->getAddress();
                $data = array(
                    'PlayerName' => $pn,
                      'ClientID' => $cid,
                      'ClientSecret' => $player->getClientSecret(),
                      'XBoxAuthenticated' => $xa,
                      'LastIP' => $ip,
                      'FirstJoin' => $fp,
                      'LastJoin' => $fp,
                      'JoinCount' => '1',
                      'KillCount' => '0',
                      'DeathCount' => '0',
                      'KickCount' => '0',
                      'OnlineTime' => '0',
                      'BlocksBreaked' => '0',
                      'BlocksPlaced' => '0',
                      'ChatMessages' => '0',
                      'FishCount' => '0',
                      'EnterBedCount' => '0',
                      'EatCount' => '0',
                      'CraftCount' => '0',
                );
                $this->saveData($player, $data);
            }
        } elseif ($provider == 'mysql') {
        }
    }

    public function onDeath(PlayerDeathEvent $event)
    {
        $player = $event->getPlayer();
        $provider = strtolower($this->getConfig()->get('Provider'));
        if ($provider == 'json') {
            $info = $this->getStats($player->getName(), 'JSON', 'all');
            $d = $info['DeathCount'] + 1;
            $data = array(
              'PlayerName' => $info['PlayerName'],
                'ClientID' => $info['ClientID'],
                    'ClientSecret' => $info['ClientSecret'],
                    'XBoxAuthenticated' => $info['XBoxAuthenticated'],
                'LastIP' => $info['LastIP'],
                'FirstJoin' => $info['FirstJoin'],
                'LastJoin' => $info['LastJoin'],
                'JoinCount' => $info['JoinCount'],
                'KillCount' => $info['KillCount'],
                'DeathCount' => $d,
                'KickCount' => $info['KickCount'],
                'OnlineTime' => $info['OnlineTime'],
                'BlocksBreaked' => $info['BlocksBreaked'],
                  'BlocksPlaced' => $info['BlocksPlaced'],
                'ChatMessages' => $info['ChatMessages'],
                'FishCount' => $info['FishCount'],
                'EnterBedCount' => $info['EnterBedCount'],
              'EatCount' => $info['EatCount'],
                'CraftCount' => $info['CraftCount'],
          );
            $this->saveData($player, $data);
            $damagecause = $player->getLastDamageCause();
            if (method_exists($damagecause, 'getDamager')) {
                if ($damagecause->getDamager() instanceof Player) {
                    $killer = $player->getLastDamageCause()->getDamager();
                    $kinfo = $this->getStats($killer->getName(), 'JSON', 'all');
                    $k = $info['KillCount'] + 1;
                    $kdata = array(
                    'PlayerName' => $kinfo['PlayerName'],
                      'ClientID' => $kinfo['ClientID'],
                            'ClientSecret' => $kinfo['ClientSecret'],
                            'XBoxAuthenticated' => $kinfo['XBoxAuthenticated'],
                      'LastIP' => $kinfo['LastIP'],
                      'FirstJoin' => $kinfo['FirstJoin'],
                      'LastJoin' => $kinfo['LastJoin'],
                    'JoinCount' => $kinfo['JoinCount'],
                    'KillCount' => $k,
                    'DeathCount' => $kinfo['DeathCount'],
                    'KickCount' => $kinfo['KickCount'],
                      'OnlineTime' => $kinfo['OnlineTime'],
                      'BlocksBreaked' => $kinfo['BlocksBreaked'],
                      'BlocksPlaced' => $kinfo['BlocksPlaced'],
                    'ChatMessages' => $kinfo['ChatMessages'],
                    'FishCount' => $kinfo['FishCount'],
                    'EnterBedCount' => $kinfo['EnterBedCount'],
                    'EatCount' => $kinfo['EatCount'],
                      'CraftCount' => $kinfo['CraftCount'],
              );
                    $this->saveData($killer, $kdata);
                }
            }
        } elseif ($provider == 'mysql') {
        }
    }

    public function onKick(PlayerKickEvent $event)
    {
        $player = $event->getPlayer();
        $provider = strtolower($this->getConfig()->get('Provider'));
        if ($provider == 'JSON') {
            $info = $this->getStats($player->getName(), 'JSON', 'all');
            $kc = $info['KickCount'] + 1;
            $data = array(
              'PlayerName' => $info['PlayerName'],
                'ClientID' => $info['ClientID'],
                'ClientSecret' => $info['ClientSecret'],
                  'XBoxAuthenticated' => $info['XBoxAuthenticated'],
                'LastIP' => $info['LastIP'],
                'FirstJoin' => $info['FirstJoin'],
                'LastJoin' => $info['LastJoin'],
                'JoinCount' => $info['JoinCount'],
                'KillCount' => $info['KillCount'],
                'DeathCount' => $info['DeathCount'],
                'KickCount' => $kc,
                'OnlineTime' => $info['OnlineTime'],
                'BlocksBreaked' => $info['BlocksBreaked'],
                  'BlocksPlaced' => $info['BlocksPlaced'],
                'ChatMessages' => $info['ChatMessages'],
                'FishCount' => $info['FishCount'],
                'EnterBedCount' => $info['EnterBedCount'],
              'EatCount' => $info['EatCount'],
                'CraftCount' => $info['CraftCount'],
          );
            $this->saveData($player, $data);
        } elseif ($provider == 'mysql') {
        }
    }

    public function onBlockBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $provider = strtolower($this->getConfig()->get('Provider'));
        if ($provider == 'json') {
            $info = $this->getStats($player->getName(), 'JSON', 'all');
            $br = $info['BlocksBreaked'] + 1;
            $data = array(
              'PlayerName' => $info['PlayerName'],
                'ClientID' => $info['ClientID'],
                  'ClientSecret' => $info['ClientSecret'],
                  'XBoxAuthenticated' => $info['XBoxAuthenticated'],
                'LastIP' => $info['LastIP'],
                'FirstJoin' => $info['FirstJoin'],
                'LastJoin' => $info['LastJoin'],
                'JoinCount' => $info['JoinCount'],
                'KillCount' => $info['KillCount'],
                'DeathCount' => $info['DeathCount'],
              'KickCount' => $info['KickCount'],
                'OnlineTime' => $info['OnlineTime'],
                'BlocksBreaked' => $br,
                  'BlocksPlaced' => $info['BlocksPlaced'],
                'ChatMessages' => $info['ChatMessages'],
                'FishCount' => $info['FishCount'],
                'EnterBedCount' => $info['EnterBedCount'],
              'EatCount' => $info['EatCount'],
                'CraftCount' => $info['CraftCount'],
          );
            $this->saveData($player, $data);
        } elseif ($provider == 'mysql') {
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        $provider = strtolower($this->getConfig()->get('Provider'));
        if ($provider == 'json') {
            $info = $this->getStats($player->getName(), 'JSON', 'all');
            $bp = $info['BlocksPlaced'] + 1;
            $data = array(
               'PlayerName' => $info['PlayerName'],
                 'ClientID' => $info['ClientID'],
                   'ClientSecret' => $info['ClientSecret'],
                   'XBoxAuthenticated' => $info['XBoxAuthenticated'],
                 'LastIP' => $info['LastIP'],
                 'FirstJoin' => $info['FirstJoin'],
                 'LastJoin' => $info['LastJoin'],
                 'JoinCount' => $info['JoinCount'],
                 'KillCount' => $info['KillCount'],
               'DeathCount' => $info['DeathCount'],
               'KickCount' => $info['KickCount'],
                 'OnlineTime' => $info['OnlineTime'],
                 'BlocksBreaked' => $info['BlocksBreaked'],
                   'BlocksPlaced' => $bp,
                 'ChatMessages' => $info['ChatMessages'],
                 'FishCount' => $info['FishCount'],
                 'EnterBedCount' => $info['EnterBedCount'],
               'EatCount' => $info['EatCount'],
                 'CraftCount' => $info['CraftCount'],
            );
            $this->saveData($player, $data);
        } elseif ($provider == 'mysql') {
        }
    }

    public function onChat(PlayerChatEvent $event)
    {
        $player = $event->getPlayer();
        $provider = strtolower($this->getConfig()->get('Provider'));
        if ($provider == 'json') {
            $info = $this->getStats($player->getName(), 'JSON', 'all');
            $cm = $info['ChatMessages'] + 1;
            $data = array(
                'PlayerName' => $info['PlayerName'],
                  'ClientID' => $info['ClientID'],
                    'ClientSecret' => $info['ClientSecret'],
                    'XBoxAuthenticated' => $info['XBoxAuthenticated'],
                  'LastIP' => $info['LastIP'],
                  'FirstJoin' => $info['FirstJoin'],
                  'LastJoin' => $info['LastJoin'],
                  'JoinCount' => $info['JoinCount'],
                  'KillCount' => $info['KillCount'],
                  'DeathCount' => $info['DeathCount'],
                'KickCount' => $info['KickCount'],
                  'OnlineTime' => $info['OnlineTime'],
                  'BlocksBreaked' => $info['BlocksBreaked'],
                    'BlocksPlaced' => $info['BlocksPlaced'],
                  'ChatMessages' => $cm,
                  'FishCount' => $info['FishCount'],
                  'EnterBedCount' => $info['EnterBedCount'],
                'EatCount' => $info['EatCount'],
                  'CraftCount' => $info['CraftCount'],
            );
            $this->saveData($player, $data);
        } elseif ($provider == 'mysql') {
        }
    }

    public function onFish(PlayerFishEvent $event)
    {
        $player = $event->getPlayer();
        $provider = strtolower($this->getConfig()->get('Provider'));
        if ($provider == 'json') {
            $info = $this->getStats($player->getName(), 'JSON', 'all');
            $fc = $info['FishCount'] + 1;
            $data = array(
                'PlayerName' => $info['PlayerName'],
                'ClientID' => $info['ClientID'],
                'ClientSecret' => $info['ClientSecret'],
                'XBoxAuthenticated' => $info['XBoxAuthenticated'],
                'LastIP' => $info['LastIP'],
                'FirstJoin' => $info['FirstJoin'],
                'LastJoin' => $info['LastJoin'],
                'JoinCount' => $info['JoinCount'],
                'KillCount' => $info['KillCount'],
                'DeathCount' => $info['DeathCount'],
                'KickCount' => $info['KickCount'],
                'OnlineTime' => $info['OnlineTime'],
                'BlocksBreaked' => $info['BlocksBreaked'],
                'BlocksPlaced' => $info['BlocksPlaced'],
                'ChatMessages' => $info['ChatMessages'],
                'FishCount' => $fc,
                'EnterBedCount' => $info['EnterBedCount'],
                'EatCount' => $info['EatCount'],
                'CraftCount' => $info['CraftCount'],
            );
            $this->saveData($player, $data);
        } elseif ($provider == 'mysql') {
        }
    }

    public function onBedEnter(PlayerBedEnterEvent $event)
    {
        $player = $event->getPlayer();
        $provider = strtolower($this->getConfig()->get('Provider'));
        if ($provider == 'json') {
            $info = $this->getStats($player->getName(), 'JSON', 'all');
            $ebc = $info['EnterBedCount'] + 1;
            $data = array(
                'PlayerName' => $info['PlayerName'],
                'ClientID' => $info['ClientID'],
                'ClientSecret' => $info['ClientSecret'],
                'XBoxAuthenticated' => $info['XBoxAuthenticated'],
                'LastIP' => $info['LastIP'],
                'FirstJoin' => $info['FirstJoin'],
                'LastJoin' => $info['LastJoin'],
                'JoinCount' => $info['JoinCount'],
                'KillCount' => $info['KillCount'],
                'DeathCount' => $info['DeathCount'],
                'KickCount' => $info['KickCount'],
                'OnlineTime' => $info['OnlineTime'],
                'BlocksBreaked' => $info['BlocksBreaked'],
                'BlocksPlaced' => $info['BlocksPlaced'],
                'ChatMessages' => $info['ChatMessages'],
                'FishCount' => $info['FishCount'],
                'EnterBedCount' => $ebc,
                'EatCount' => $info['EatCount'],
                'CraftCount' => $info['CraftCount'],
            );
            $this->saveData($player, $data);
        } elseif ($provider == 'mysql') {
        }
    }

    public function onConsumeItem(PlayerItemConsumeEvent $event)
    {
        $player = $event->getPlayer();
        $provider = strtolower($this->getConfig()->get('Provider'));
        if ($provider == 'json') {
            $info = $this->getStats($player->getName(), 'JSON', 'all');
            $ec = $info['EatCount'] + 1;
            $data = array(
                'PlayerName' => $info['PlayerName'],
                'ClientID' => $info['ClientID'],
                'ClientSecret' => $info['ClientSecret'],
                'XBoxAuthenticated' => $info['XBoxAuthenticated'],
                'LastIP' => $info['LastIP'],
                'FirstJoin' => $info['FirstJoin'],
                'LastJoin' => $info['LastJoin'],
                'JoinCount' => $info['JoinCount'],
                'KillCount' => $info['KillCount'],
                'DeathCount' => $info['DeathCount'],
                'KickCount' => $info['KickCount'],
                'OnlineTime' => $info['OnlineTime'],
                'BlocksBreaked' => $info['BlocksBreaked'],
                'BlocksPlaced' => $info['BlocksPlaced'],
                'ChatMessages' => $info['ChatMessages'],
                'FishCount' => $info['FishCount'],
                'EnterBedCount' => $info['EnterBedCount'],
                'EatCount' => $ec,
                'CraftCount' => $info['CraftCount'],
            );
            $this->saveData($player, $data);
        } elseif ($provider == 'mysql') {
        }
    }

    public function onCraft(CraftItemEvent $event)
    {
        $player = $event->getPlayer();
        $provider = strtolower($this->getConfig()->get('Provider'));
        if ($provider == 'json') {
            $info = $this->getStats($player->getName(), 'JSON', 'all');
            $cc = $info['CraftCount'] + 1;
            $data = array(
                'PlayerName' => $info['PlayerName'],
                'ClientID' => $info['ClientID'],
                'ClientSecret' => $info['ClientSecret'],
                'XBoxAuthenticated' => $info['XBoxAuthenticated'],
                'LastIP' => $info['LastIP'],
                'FirstJoin' => $info['FirstJoin'],
                'LastJoin' => $info['LastJoin'],
                'JoinCount' => $info['JoinCount'],
                'KillCount' => $info['KillCount'],
                'DeathCount' => $info['DeathCount'],
                'KickCount' => $info['KickCount'],
                'OnlineTime' => $info['OnlineTime'],
                'BlocksBreaked' => $info['BlocksBreaked'],
                'BlocksPlaced' => $info['BlocksPlaced'],
                'ChatMessages' => $info['ChatMessages'],
                'FishCount' => $info['FishCount'],
                'EnterBedCount' => $info['EnterBedCount'],
                'EatCount' => $info['EatCount'],
                'CraftCount' => $cc,
            );
            $this->saveData($player, $data);
        } elseif ($provider == 'mysql') {
        }
    }

    public function update($newversion)
    {
        $url = Utils::getURL($this->getDescription()->getWebsite().'MCPE-Plugins/'.$this->getDescription()->getName().'/Updater.php?downloadurl');
        $md5 = Utils::getURL($this->getDescription()->getWebsite().'MCPE-Plugins/'.$this->getDescription()->getName().'/Updater.php?md5');
        $this->getLogger()->notice(TF::AQUA.'MD5 Hash: '.TF::GOLD.TF::BOLD.$md5);
        $this->getServer()->getScheduler()->scheduleAsyncTask(new UpdateTask($url, $md5, $this->getDataFolder(), $this->getDescription()->getVersion()));
    }
}
