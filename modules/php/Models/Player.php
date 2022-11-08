<?php

namespace RAUHA\Models;

use RAUHA\Core\Stats;
use RAUHA\Core\Notifications;
use RAUHA\Core\Preferences;
use RAUHA\Managers\BiomeCards;
use RAUHA\Managers\GodCards;

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
    return (BiomeCards::getInLocation('hand', $this->id));
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

  // public function hasVuoriOnBoard(){
  //   return GodCards::
  // }
}
