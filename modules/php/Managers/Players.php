<?php

namespace RAUHA\Managers;

use RAUHA\Core\Game;
use RAUHA\Core\Globals;
use RAUHA\Core\Notifications;
use RAUHA\Core\Stats;
use RAUHA\Helpers\Utils;

/*
 * Players manager : allows to easily access players ...
 *  a player is an instance of Player class
 */

class Players extends \RAUHA\Helpers\DB_Manager
{
  protected static $table = 'player';
  protected static $primary = 'player_id';
  protected static function cast($row)
  {
    return new \RAUHA\Models\Player($row);
  }

  public function setupNewGame($players, $options)
  {
    // Create players
    $gameInfos = Game::get()->getGameinfos();
    $colors = $gameInfos['player_colors'];
    $query = self::DB()->multipleInsert([
      'player_id',
      'player_color',
      'player_canal',
      'player_name',
      'player_avatar',
      'player_board',
      'player_score_aux',
    ]);

    $values = [];
    $index = 1;
    shuffle($colors);
    foreach ($players as $pId => $player) {
      $color = array_shift($colors);

      $playerScoreAux = $index++;

      $values[] = [
        $pId,
        $color,
        $player['player_canal'],
        $player['player_name'],
        $player['player_avatar'],
        '[[0, 0, 0], [0, 0, 0], [0, 0, 0]]',
        $playerScoreAux,
      ];
    }

    $query->values($values);

    self::determineFirstPlayer();

    if ($options[OPTION_SYNTYMA] != OPTION_SYNTYMA_ON){
      Game::get()->reattributeColorsBasedOnPreferences($players, $gameInfos['player_colors']);
    }
    
    Game::get()->reloadPlayersBasicInfos();
  }

  public function getActiveId()
  {
    return Game::get()->getActivePlayerId();
  }

  public function getCurrentId()
  {
    return (int) Game::get()->getCurrentPId();
  }

  public function getAll()
  {
    return self::DB()->get(false);
  }

  /*
   * get : returns the Player object for the given player ID
   */
  public function get($pId = null)
  {
    $pId = $pId ?: self::getActiveId();
    return self::DB()
      ->where($pId)
      ->getSingle();
  }

  public static function getActive()
  {
    return self::get();
  }

  public static function getCurrent()
  {
    return self::get(self::getCurrentId());
  }

  public function getNextId($player = null)
  {
    $player = $player ?? Players::getCurrent();
    $pId = is_int($player) ? $player : $player->getId();
    $table = Game::get()->getNextPlayerTable();
    return $table[$pId];
  }

  /*
   * Return the number of players
   */
  public function count()
  {
    return self::DB()->count();
  }

  /*
   * getUiData : get all ui data of all players
   */
  public function getUiData($pId)
  {
    return self::getAll()
      ->map(function ($player) use ($pId) {
        return $player->getUiData($pId);
      })
      ->toAssoc();
  }

  /**
   * Get current turn order according to first player variable
   */
  public function getTurnOrder($firstPlayer = null)
  {
    $firstPlayer = $firstPlayer ?? Globals::getFirstPlayer();
    $order = [];
    $p = $firstPlayer;
    do {
      $order[] = $p;
      $p = self::getNextId($p);
    } while ($p != $firstPlayer);
    return $order;
  }

  /**
   * This allow to change active player
   */
  public static function changeActive($pId)
  {
    Game::get()->gamestate->changeActivePlayer($pId);
  }

  /////////////////////////
  ///// RAUHA Specific ////
  /////////////////////////

  public static function clearAuxScores()
  {
    self::DB()
      ->update(['player_score_aux' => 0])
      ->run();
  }

  public static function determineFirstPlayer()
  {
    $pId = self::getFirstPlayerId();
    Globals::setFirstPlayer($pId);
    Notifications::updateFirstPlayer($pId);
  }

  /*
   * Get first player according to points
   */
  public static function getFirstPlayerId()
  {
    //TODO what is select columns
    return self::DB()
      ->select(['player_id'])
      ->orderBy('player_score', 'DESC')
      ->orderBy('player_score_aux', 'DESC')
      ->getSingle()
      ->getId();
  }

  public static function countHowManyPlayerswithThatScore($score)
  {
    return self::DB()
      ->where('player_score', $score)
      ->count();
  }

  // Deprecated -> Biomes::refreshAll()
  // public function refreshBiomes()
  // {
  //   foreach (self::getAll() as $id => $player) {
  //     foreach (BiomeCards::getAllBiomesOnPlayerBoard($player) as $biome) {
  //       $biome->setUsed(NOT_USED);
  //     }
  //   }
  // }

  public static function GetPointsForWaterSource($player)
  {
    $minWaterSource = Players::getMinWaterSource();

    $waterSource = $player->getWaterSource();
    $waterSourceDelta = $waterSource - $minWaterSource;
    $points = POINTS_FOR_WATER_SOURCE[min(5, $waterSourceDelta)];
    if ($points > 0) {
      $player->movePointsToken($points, STAT_NAME_WATER_SOURCES_POINTS);
      Notifications::waterSourceCount($player, $waterSourceDelta, $points);
    }
  }

  public static function getMinWaterSource()
  {
    $min = 50;
    foreach (self::getAll() as $id => $player) {
      $min = min($player->getWaterSource(), $min);
    }
    return $min;
  }
}
