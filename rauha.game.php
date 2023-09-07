<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Rauha implementation : Timothée Pecatte <tim.pecatte@gmail.com> & Emmanuel Albisser <emmanuel.albisser@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * rauha.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */

$swdNamespaceAutoload = function ($class) {
  $classParts = explode('\\', $class);
  if ($classParts[0] == 'RAUHA') {
    array_shift($classParts);
    $file = dirname(__FILE__) . '/modules/php/' . implode(DIRECTORY_SEPARATOR, $classParts) . '.php';
    if (file_exists($file)) {
      require_once $file;
    } else {
      var_dump('Cannot find file : ' . $file);
    }
  }
};
spl_autoload_register($swdNamespaceAutoload, true, true);

require_once APP_GAMEMODULE_PATH . 'module/table/table.game.php';

use RAUHA\Managers\Players;
use RAUHA\Managers\BiomeCards;
use RAUHA\Managers\GodCards;
use RAUHA\Core\Globals;
use RAUHA\Core\Preferences;
use RAUHA\Core\Stats;

class Rauha extends Table
{
  use RAUHA\DebugTrait;
  use RAUHA\States\NewRoundTrait;
  use RAUHA\States\ChooseBiomeTrait;
  use RAUHA\States\ChooseShamanTrait;
  use RAUHA\States\PlaceBiomeTrait;
  use RAUHA\States\ActivateTrait;
  use RAUHA\States\CountTurnTrait;

  public static $instance = null;
  function __construct()
  {
    parent::__construct();
    self::$instance = $this;
    self::initGameStateLabels([
      'logging' => 10,
      OPTION_BOARD_SIDE => 102,
    ]);
    Stats::checkExistence();
    $this->bIndependantMultiactiveTable=true;
  }
  public static function get()
  {
    return self::$instance;
  }

  protected function getGameName()
  {
    // Used for translations and stuff. Please do not modify.
    return 'rauha';
  }

  /*
   * setupNewGame:
   */
  protected function setupNewGame($players, $options = [])
  {
    Players::setupNewGame($players, $options);
    Globals::setupNewGame($players, $options);
    Preferences::setupNewGame($players, $this->player_preferences);
    //    Stats::checkExistence();
    BiomeCards::setupNewGame($players, $options);
    GodCards::setupNewGame($players, $options);

    $this->setGameStateInitialValue('logging', false);
    $this->activeNextPlayer();
  }

  /*
   * getAllDatas:
   */
  public function getAllDatas()
  {
    $pId = self::getCurrentPId();
    return [
      'side' => $this->getGameStateValue(\OPTION_BOARD_SIDE) == OPTION_A_SIDE ? 'faceA' : 'faceB',
      'prefs' => Preferences::getUiData($pId),
      'players' => Players::getUiData($pId),
      'turn' => Globals::getTurn(),
      'gods' => GodCards::getUiData(),
      'firstPlayer' => Globals::getFirstPlayer(),
    ];
  }

  /*
   * getGameProgression:
   */
  function getGameProgression()
  {
    return (Globals::getTurn() / 16) * 100;
  }

  function actChangePreference($pref, $value)
  {
    Preferences::set($this->getCurrentPId(), $pref, $value);
  }

  ////////////////////////////////////
  ////////////   Zombie   ////////////
  ////////////////////////////////////
  /*
   * zombieTurn:
   *   This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
   *   You can do whatever you want in order to make sure the turn of this player ends appropriately
   */
  public function zombieTurn($state, $activePlayerId)
  {
    $statename = $state['name'];

    switch ($statename) {
      case 'chooseBiome':
        $args = $this->argChooseBiome();
        $biomes = $args['_private'][$activePlayerId]['biomes'];
        $ids = [];
        foreach ($biomes as $id => $biome) {
          $ids[] = $id;
        }

        $choice = bga_rand(0, count($biomes) - 1);
        $this->actChooseBiome($ids[$choice], (int) $activePlayerId);
        break;
      case 'placeBiome':
        $this->actDiscardCrystals($activePlayerId);
        break;

      case 'countAction':
      case 'activate':
        $this->actSkip($activePlayerId);
        break;

      default:
        throw new feException('Zombie mode not supported at this game state: ' . $statename);
    }
  }

  /////////////////////////////////////
  //////////// Prevent deadlock ///////
  /////////////////////////////////////
  
   // Due to deadlock issues involving the playersmultiactive and player tables,
   //   standard tables are queried FOR UPDATE when any operation occurs -- AJAX or refreshing a game table.
   //
   // Otherwise at least two situations have been observed to cause deadlocks:
   //   * Multiple players in a live game with tabs open, two players trading multiactive state back and forth.
   //   * Two players trading multiactive state back and forth, another player refreshes their game page.
 function queryStandardTables() {
  // Query the standard global table.
  self::DbQuery("SELECT global_id, global_value FROM global WHERE 1 ORDER BY global_id FOR UPDATE");
  // Query the standard player table.
  self::DbQuery("SELECT player_id id, player_score score FROM player WHERE 1 ORDER BY player_id FOR UPDATE");
  // Query the playermultiactive  table. DO NOT USE THIS is you don't use $this->bIndependantMultiactiveTable=true
  self::DbQuery("SELECT ma_player_id player_id, ma_is_multiactive player_is_multiactive FROM playermultiactive ORDER BY player_id FOR UPDATE");

  // TODO should the stats table be queried as well?
}

  /** This is special function called by framework BEFORE any of your action functions */
  protected function initTable() {
    $this->queryStandardTables(); 
} 

  /////////////////////////////////////
  //////////   DB upgrade   ///////////
  /////////////////////////////////////
  // You don't have to care about this until your game has been published on BGA.
  // Once your game is on BGA, this method is called everytime the system detects a game running with your old Database scheme.
  // In this case, if you change your Database scheme, you just have to apply the needed changes in order to
  //   update the game database and allow the game to continue to run with your new version.
  /////////////////////////////////////
  /*
   * upgradeTableDb
   *  - int $from_version : current version of this game database, in numerical form.
   *      For example, if the game was running with a release of your game named "140430-1345", $from_version is equal to 1404301345
   */
  public function upgradeTableDb($from_version)
  {
  }

  /////////////////////////////////////////////////////////////
  // Exposing protected methods, please use at your own risk //
  /////////////////////////////////////////////////////////////

  // Exposing protected method getCurrentPlayerId
  public static function getCurrentPId()
  {
    return self::getCurrentPlayerId();
  }

  // Exposing protected method translation
  public static function translate($text)
  {
    return self::_($text);
  }

  // Shorthand
  public function getArgs()
  {
    return $this->gamestate->state()['args'];
  }
}
