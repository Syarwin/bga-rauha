<?php

namespace RAUHA\Models;

use RAUHA\Managers\Players;

/*
 * BiomeCard
 */

class BiomeCard extends \RAUHA\Helpers\DB_Model
{
  protected $table = 'biomes';
  protected $primary = 'biome_id';
  protected $attributes = [
    'dataId' => ['data_id', 'int'],
    'id' => ['biome_id', 'int'],
    'location' => 'biome_location', //deckAge1, deck1, (inPlay), hand, board, discard
    'state' => ['biome_state', 'int'], //useless in this game
    'x' => ['x', 'int'],
    'y' => ['y', 'int'],
    'pId' => ['player_id', 'int'],
    'used' => ['used', 'int'], //USED or NOT_USED
    'extraDatas' => ['extra_datas', 'obj'], //not used for now
  ];

  protected $staticAttributes = [
    ['types', 'obj'], // array of string
    ['animals', 'obj'], //array of string
    ['layingConstraints', 'obj'], //array of coords
    ['layingCost', 'int'], //in crystal
    ['crystalIncome', 'int'],
    ['pointIncome', 'int'],
    'multiplier', //string like "marine", "spore", "waterSource", "animals" or "1"
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

  public function isSupported($players, $options)
  {
    return true; // Useful for expansion/ban list/ etc...
  }

  public function placeOnPlayerBoard($player, $x, $y)
  {
    $this->setX($x);
    $this->setY($y);
    $this->setPId($player->getId());
    $this->setLocation('board');
  }

  public function isActivable()
  {
    if ($this->crystalIncome == 0 && $this->pointIncome == 0 && $this->sporeIncome == 0) {
      return false;
    }

    if ($this->used == USED) return false;

    $owner = Players::get($this->pId);

    if ($this->usageCost > $owner->getCrystal()) return false;

    //if this biome give a new spore and this player has no available spot for spore, return false;
    if ($this->sporeIncome && empty($owner->getSporesPlaces(false))) return false;

    return true;
  }

  public function getElements()
  {
    return array_merge($this->types, $this->animals);
  }

  // a biome can be automatic if it has no usage cost and has no SPORE multiplier
  public function isAutomatic($sporeCanBeAdded)
  {
    return (!$sporeCanBeAdded || $this->multiplier != SPORE) && $this->usageCost == 0;
  }

  /*NOT IMPLEMENTED
  public function getTypeStr()
  {
    return '';
  }

  public function isPlayed()
  {
    return $this->location == 'inPlay';
  }

  public function getPlayer($checkPlayed = false)
  {
    if (!$this->isPlayed() && $checkPlayed) {
      throw new \feException("Trying to get the player for a non-played card : {$this->id}");
    }

    return Players::get($this->pId);
  }*/
}
