<?php

namespace RAUHA\Managers;

use RAUHA\Helpers\Utils;
use RAUHA\Helpers\Collection;
use RAUHA\Core\Notifications;
use RAUHA\Core\Stats;

/* Class to manage all the god cards for Rauha */

class GodCards extends \RAUHA\Helpers\Pieces
{
  protected static $table = 'gods';
  protected static $prefix = 'god_';
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;
  protected static $customFields = ['player_id', 'used', 'extra_datas'];

  protected static function cast($row)
  {
    $data = self::getGods()[$row['god_id']];
    return new \RAUHA\Models\GodCard($row, $data);
  }

  public static function getUiData()
  {
    return self::getAll();
  }

  public static function refreshAll()
  {
    self::DB()
      ->update(['used' => NOT_USED])
      ->run();
    Notifications::refreshGods();
  }

  /* Creation of the gods */
  public static function setupNewGame($players, $options)
  {
    $gods = [];

    foreach (self::getGods() as $id => $god) {
      $gods[] = [
        'location' => 'table',
      ];
    }

    self::create($gods);
  }

  /* Select a god by his type */
  public function getGodByType($type)
  {
    foreach (self::getAll() as $id => $god) {
      if ($god->getType() == $type) {
        return $god;
      }
    }
  }

  /*
   * Get activable god for a player
   */
  public static function getActivableGods($player)
  {
    $result = [];
    foreach (self::getGodsByPlayer($player) as $id => $god) {
      if ($god->isActivable()) {
        $result[] = $god;
      }
    }
    return $result;

    // return self::DB()->select('god_id')
    //   ->where('player_id', $player->getId())
    //   ->where('used', NOT_USED)
    //   ->get();
  }

  public function getGodsByPlayer($player)
  {
    return self::getInLocationQ('board')
      ->where('player_id', $player->getId())
      ->get();
  }

  public function countAllWaterSourceOnPlayerGods($player)
  {
    $result = 0;
    foreach (self::getGodsByPlayer($player) as $id => $god) {
      $result += $god->getWaterSource();
    }
    return $result;
  }

  public static function activate($godId)
  {
    $message = '';
    $god = self::get($godId);
    $playerId = $god->getPId();
    $player = Players::get($playerId);

    $multiplier = $god->getMultiplier() == 1 ? 1 : BiomeCards::countOnAPlayerBoard($player, $god->getMultiplier());

    $cost = $god->getUsageCost();
    $crystalIncome = $god->getCrystalIncome() * $multiplier;
    $pointIncome = $god->getPointIncome() * $multiplier;

    $player->incCrystal($crystalIncome - $cost);
    Stats::inc(STAT_NAME_COLLECTED_CRISTAL, $player, $crystalIncome);

    if ($pointIncome) {
      $player->movePointsToken($pointIncome, Stats::getStatName($god->getMultiplier()));
    }
    $god->setUsed(USED);

    // Notifications
    Notifications::activateGod($player, $god, $cost, $crystalIncome, $pointIncome);
    return false;
  }

  public function getGods()
  {
    $f = function ($t) {
      return [
        'name' => $t[0],
        'type' => $t[1],
        'crystalIncome' => $t[2],
        'pointIncome' => $t[3],
        'multiplier' => $t[4],
        'usageCost' => $t[5],
        'sporeIncome' => $t[6],
        'waterSource' => $t[7],
      ];
    };

    return [
      1 => $f([clienttranslate('Taivas'), FLYING, 0, 7, 1, 4, 0, 0]),
      2 => $f([clienttranslate('Sienet'), MUSHROOM, 3, 0, 1, 0, 0, 0]),
      3 => $f([clienttranslate('Meri'), MARINE, 0, 1, WATER_SOURCE, 0, 0, 0]),
      4 => $f([clienttranslate('Metsat'), FOREST, 0, 1, ANIMALS, 0, 0, 0]),
      5 => $f([clienttranslate('Kiteet'), CRYSTAL, 0, 3, 1, 0, 0, 0]),
      6 => $f([clienttranslate('Vuori'), MOUNTAIN, 0, 0, 1, 0, 0, 2]),
      7 => $f([clienttranslate('Maa'), WALKING, 0, 1, SPORE, 0, 0, 0]), 

      8 => $f([clienttranslate('Taivas II'), FLYING, 0, 0.5, CRYSTAL, 0, 0, 0]),
      9 => $f([clienttranslate('Sienet II'), MUSHROOM, 1, 0, WALKING, 0, 0, 0]),
      10 => $f([clienttranslate('Meri II'), MARINE, 0, 1, ANY_BIOME, 0, 0, 0]),
      11 => $f([clienttranslate('Metsat II'), FOREST, 0, 3, ALL_ANIMALS, 0, 0, 0]),
      12 => $f([clienttranslate('Kiteet II'), CRYSTAL, 0, 2, FLYING, 2, 0, 0]),
      13 => $f([clienttranslate('Vuori II'), MOUNTAIN, 0, 0, MARINE, 0, 0, 1]),
      14 => $f([clienttranslate('Maa II'), WALKING, 0, 0, 1, 0, 1, 0]),
    ];
  }
}
