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
  protected static $customFields = ['player_id', 'extra_datas'];

  protected static function cast($row)
  {
    $data = self::getGods()[$row['id']];
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
      0 => $f(['TAIVAS', 'flying', 0, 7, 1, 4, 0, 0]),
      1 => $f(['SIENET', 'mushroom', 3, 0, 1, 0, 0, 0]),
      2 => $f(['MERI', 'marine', 0, 1, 'waterSource', 0, 0, 0]),
      3 => $f(['METSAT', 'forest', 0, 1, 'animals', 0, 0, 0]),
      4 => $f(['KITEET', 'crytal', 0, 3, 1, 0, 0, 0]),
      5 => $f(['VUORI', 'mountain', 0, 0, 1, 0, 0, 2]),
      6 => $f(['MAA', 'walking', 0, 1, 'spore', 0, 0, 0])
    ];
  }
}
