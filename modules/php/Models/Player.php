<?php

namespace RAUHA\Models;

use RAUHA\Core\Stats;
use RAUHA\Core\Notifications;
use RAUHA\Core\Preferences;
use RAUHA\Managers\BiomeCards;
use RAUHA\Managers\GodCards;
use RAUHA\Managers\Players;

/*
 * Player: all utility functions concerning a player
 */

class Player extends \RAUHA\Helpers\DB_Model
{
  private $map = null;
  protected $table = 'player';
  protected $primary = 'player_id';
  protected $attributes = [
    'id' => ['player_id', 'int'],
    'no' => ['player_no', 'int'],
    'name' => 'player_name',
    'color' => 'player_color',
    'eliminated' => 'player_eliminated',
    'score' => ['player_score', 'int'],
    'scoreAux' => ['player_score_aux', 'int'],
    'crystal' => ['player_crystal', 'int'],
    'board' => ['player_board', 'obj'],
    'zombie' => 'player_zombie',
  ];

  public function getUiData($currentPlayerId = null)
  {
    $data = parent::getUiData();
    $current = $this->id == $currentPlayerId;
    $data['hand'] = $current ? $this->getBiomeInHand() : null;
    $data['biomes'] = BiomeCards::getAllBiomesOnPlayerBoard($this);
    $data['water'] = $this->getWaterSource();

    return $data;
  }

  public function getPref($prefId)
  {
    return Preferences::get($this->id, $prefId);
  }

  public function getStat($name)
  {
    $name = 'get' . \ucfirst($name);
    return Stats::$name($this->id);
  }

  public function hasBiomeInHand()
  {
    return !is_null($this->getBiomeInHand());
  }

  public function getBiomeInHand()
  {
    return BiomeCards::getInLocation('hand', $this->id)->first();
  }

  public function countSpores()
  {
    $result = 0;
    foreach ($this->board as $row) {
      foreach ($row as $cell) {
        $result += $cell;
      }
    }
    return $result;
  }

  /**
   *  Return an array with places with or without spores (depending on $boolWithSpore)
   */
  public function getSporesPlaces($boolWithSpore)
  {
    $seek = $boolWithSpore ? 1 : 0;
    $places = [];
    for ($y = 0; $y < 3; $y++) {
      for ($x = 0; $x < 3; $x++) {
        if ($this->board[$y][$x] == $seek) {
          //CHECK IF IT'S OK
          $places[] = [$x, $y];
        }
      }
    }
    return $places;
  }

  public function placeSpore($x, $y)
  {
    $board = $this->board;
    $board[$y][$x] = 1; //CHECK IF IT'S OK
    $this->setBoard($board);
  }

  // public function hasVuoriOnBoard(){
  //   return GodCards::
  // }

  public function getWaterSource()
  {
    $result = BiomeCards::countAllWaterSourceOnPlayerBoard($this);
    $result += GodCards::countAllWaterSourceOnPlayerGods($this);
    return $result;
  }

  public function movePointsToken($pointIncome, $statName)
  {
    $this->incScore($pointIncome);
    Stats::inc($statName, $this, $pointIncome);

    $score_aux = Players::countHowManyPlayersWithThatScore($this->score);
    $this->setScoreAux($score_aux);
  }

  public function setGodsUsed()
  {
    foreach (GodCards::getGodsByPlayer($this) as $id => $god) {
      $god->setUsed(USED);
    }
  }
}
