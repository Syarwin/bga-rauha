<?php

namespace RAUHA\Models;

/*
 * GodCard
 */

class GodCard extends \RAUHA\Helpers\DB_Model
{
  protected $table = 'gods';
  protected $primary = 'god_id';
  protected $attributes = [
    'id' => ['god_id', 'int'],
    'state' => ['god_state', 'int'], //0=not used 1=used
    'location' => 'god_location', //"table" or "player"
    'pId' => ['player_id', 'int'],
    // 'used' => ['used', 'int'], //0:not used, 1=used
    'extraDatas' => ['extra_datas', 'obj'], //not used for now
  ];

  protected $staticAttributes = [
    'name',
    'type',
    ['crystal_income', 'int'],
    ['point_income', 'int'],
    'multiplier', //string like "marine", "spore", "water_source", or "1"
    ['usage_cost', 'int'], //in crystal
    ['spore_income', 'int'],
    ['water_source', 'int'],
  ];

  public function isSupported($players, $options)
  {
    return true; // Useful for expansion/ban list/ etc...
  }

  public function isPlayable()
  {
    if ($this->name == 'MERI') return False;
    else return ($this->state == 0);
  }

  /* NOT IMPLEMENTED
  public function getTypeStr()
  {
    return '';
  }
  public function isPlayed()
  {
    return $this->location == 'inPlay';
  }
  public function getPoolNumber()
  {
    $t = explode('-', $this->location);
    return $t[0] == 'pool' ? ((int) $t[1]) : null;
  }
  public function getPlayer($checkPlayed = false)
  {
    if (!$this->isPlayed() && $checkPlayed) {
      throw new \feException("Trying to get the player for a non-played card : {$this->id}");
    }
    return Players::get($this->pId);
  }*/
}
