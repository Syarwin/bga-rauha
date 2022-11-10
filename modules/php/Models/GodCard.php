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
    'state' => ['god_state', 'int'], //useless in this game
    'location' => 'god_location', //"table" or "player" //TODO CHECK THIS
    'pId' => ['player_id', 'int'],
    'used' => ['used', 'int'], //0:not used, 1=used
    'extraDatas' => ['extra_datas', 'obj'], //not used for now
  ];

  protected $staticAttributes = [
    'name',
    'type',
    ['crystal_income', 'int'],
    ['point_income', 'int'],
    'multiplier', //string like "marine", "spore", "waterSource", or "1"
    ['usage_cost', 'int'], //in crystal
    ['spore_income', 'int'],
    ['waterSource', 'int'],
  ];

  public function __construct($row, $datas)
  {
    parent::__construct($row);
    foreach ($datas as $attribute => $value) {
      $this->$attribute = $value;
    }
  }

  public function moveOnPlayerBoard($player)
  {
    $this->setLocation('board');
    $this->setPId($player->getId());
    //when a god is taken, it can be used by its new owner
    $this->used(NOT_USED);
  }

  public function isSupported($players, $options)
  {
    return true; // Useful for expansion/ban list/ etc...
  }

  public function isActivable()
  {
    if ($this->name == 'MERI') return False;
    else return ($this->used == 0);
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
