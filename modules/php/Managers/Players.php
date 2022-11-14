<?php

namespace RAUHA\Managers;

use RAUHA\Core\Game;
use RAUHA\Core\Globals;
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

    Game::get()->reattributeColorsBasedOnPreferences($players, $gameInfos['player_colors']);
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

  public function getActive()
  {
    return self::get();
  }

  public function getCurrent()
  {
    return self::get(self::getCurrentId());
  }

  public function getNextId($player)
  {
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
  public function changeActive($pId)
  {
    Game::get()->gamestate->changeActivePlayer($pId);
  }

  /////////////////////////
  ///// RAUHA Specific ////
  /////////////////////////

  public function determineFirstPlayer()
  {
    Globals::setFirstPlayer(self::getFirstPlayerId());
  }

  /*
   * Get first player according to points
   */
  public function getFirstPlayerId()
  {
    //TODO what is select columns
    return self::DB()
      ->select(['player_id'])
      ->orderBy('player_score', 'DESC')
      ->orderBy('player_score_aux', 'DESC')
      ->getSingle()
      ->getId();
  }

  public function countHowManyPlayerswithThatScore($score)
  {
    return self::DB()->where('player_score', $score)
      ->count();
  }

  public function movePointsToken($player, $pointIncome)
  {
    $player->incScore($pointIncome);

    $score_aux = self::countHowManyPlayersWithThatScore($player->getScore);
    $player->setScore_aux($score_aux);
  }

  public function refreshBiomes()
  {
    foreach (self::getAll() as $id => $player) {
      foreach (BiomeCards::getAllBiomesOnPlayerBoard($player) as $biome) {
        $biome->setUsed(NOT_USED);
      }
    }
  }

  public function refreshGods()
  {
  }
}
