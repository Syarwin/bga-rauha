<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Rauha implementation : Timothée Pecatte <tim.pecatte@gmail.com>
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
  use RAUHA\States\TurnTrait;
  use RAUHA\States\NewRoundTrait;
  use RAUHA\States\ActionTurnTrait;

  public static $instance = null;
  function __construct()
  {
    parent::__construct();
    self::$instance = $this;
    self::initGameStateLabels([
      'logging' => 10,
    ]);
    Stats::checkExistence();
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
      'prefs' => Preferences::getUiData($pId),
      'players' => Players::getUiData($pId),
      'turn' => Globals::getTurn(),
    ];
  }

  /*
   * getGameProgression:
   */
  function getGameProgression()
  {
    return Globals::getTurn() / 16 * 100;
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
        $biomesIds = $args['_private'][$activePlayerId]['biomesIds'];
        $answer = bga_rand(0, count($biomesIds) - 1);
        $this->actChooseBiome($biomesIds[$answer], $activePlayerId);

      case 'placeBiome':
        # code...actDiscard
        break;

      case 'actBiomes':
        # code...actSkip
        break;

      case 'placeBiome':
        # code...skip
        break;

      case 'countAction':
        # code...actSkipCount
        break;

      default:
        # code...
        break;
    }

    throw new feException("Zombie mode not supported at this game state: " . $statename);
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

  public static function test($arg)
  {
    echo "<pre>";
    var_dump(Players::countHowManyPlayerswithThatScore($arg));
    echo "</pre>";
    die('ok');
  }

  public static function displayVariable($var)
  {
    echo "<pre>";
    var_dump($var);
    echo "</pre>";
    die('ok');
  }
}
