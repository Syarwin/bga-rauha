<?php

namespace RAUHA\Models;

/*
 * BiomeCard
 */

class BiomeCard extends \RAUHA\Helpers\DB_Model
{
  protected $table = 'biomes';
  protected $primary = 'biome_id';
  protected $attributes = [
    'id' => ['biome_id', 'int'],
    'location' => 'biome_location', //deckAge1, deck1, inPlay
    'state' => ['biome_state', 'int'], //0=out, 1=deck, 2=coord(played)
    'x' => ['x', 'int'],
    'y' => ['y', 'int'],
    'pId' => ['player_id', 'int'],
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
  }
}
