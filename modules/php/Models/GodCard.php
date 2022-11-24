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
    'used' => ['used', 'int'], //USED or NOT_USED
    'extraDatas' => ['extra_datas', 'obj'], //not used for now
  ];

  protected $staticAttributes = [
    'name',
    'type',
    ['crystalIncome', 'int'],
    ['pointIncome', 'int'],
    'multiplier', //string like "marine", "spore", "waterSource", or "1"
    ['usageCost', 'int'], //in crystal
    ['sporeIncome', 'int'],
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
    $this->setUsed(NOT_USED);
  }

  public function isSupported($players, $options)
  {
    return true; // Useful for expansion/ban list/ etc...
  }

  public function isActivable()
  {
    if ($this->name == 'VUORI') {
      return false;
    } else {
      return $this->used == NOT_USED;
    }
  }

  // a god can be automatic if it has no usage cost and has no SPORE multiplier
  public function isAutomatic($sporeCanBeAdded)
  {
    return (!$sporeCanBeAdded || $this->multiplier != SPORE) && $this->usageCost == 0;
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
