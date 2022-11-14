<?php

namespace RAUHA\Managers;

use RAUHA\Helpers\Utils;
use RAUHA\Helpers\Collection;
use RAUHA\Core\Notifications;

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
    return [];
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
  public function getActivableGods($player)
  {
    $result = [];
    foreach (self::getAll() as $id => $god) {
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

  public static function activate($godId)
  {
    $message = "";
    $god = self::get($godId);
    $playerId = $god->getPId();
    $player = Players::get($playerId);

    $multiplier = ($god->getMultiplier() == 1) ? 1 : BiomeCards::countOnAPlayerBoard($player, $god->getMultiplier());

    $cost = $god->getUsageCost();

    if ($cost > 0)    $message .= clienttranslate('By paying ${cost} crystal(s), ');

    $crystalIncome = $god->getCrystalIncome() * $multiplier;
    if ($crystalIncome > 0) {
      $message .= clienttranslate('${player_name} activate ${godName} and receives ${crystalIncome} crystal(s)');
    }

    $pointIncome = $god->getPointIncome() * $multiplier;
    if ($pointIncome > 0) {
      $message .= clienttranslate('${player_name} activate ${godName} and receives ${pointIncome} point(s)');
    }

    $player->incCrystal($crystalIncome - $cost);
    $player->movePointsToken($pointIncome);
    $god->setUsed(USED);

    // TODO Notifications
    Notifications::actCountGod($player, $message, $god, $cost, $crystalIncome, $pointIncome);
  }

  public function getGods()
  {
    $f = function ($t) {
      return [
        'name' => $t[0],
        'type' => $t[1],
        'crystalIncome' => $t[2],
        'pointIcome' => $t[3],
        'multiplier' => $t[4],
        'usageCost' => $t[5],
        'sporeIcome' => $t[6],
        'waterSource' => $t[7],
      ];
    };

    return [
      1 => $f(['TAIVAS', 'flying', 0, 7, 1, 4, 0, 0]),
      2 => $f(['SIENET', MUSHROOM, 3, 0, 1, 0, 0, 0]),
      3 => $f(['MERI', 'marine', 0, 1, 'waterSource', 0, 0, 0]),
      4 => $f(['METSAT', FOREST, 0, 1, 'animals', 0, 0, 0]),
      5 => $f(['KITEET', 'crytal', 0, 3, 1, 0, 0, 0]),
      6 => $f(['VUORI', MOUNTAIN, 0, 0, 1, 0, 0, 2]),
      7 => $f(['MAA', 'walking', 0, 1, 'spore', 0, 0, 0]),
    ];
  }
}
