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
        'crystalIncome' => $t[1],
        'pointIcome' => $t[2],
        'multiplier' => $t[3],
        'usageCost' => $t[4],
        'sporeIcome' => $t[5],
        'waterSource' => $t[6],
      ];
    };

    return [
      0 => $f(['TAIVAS', 0, 7, 1, 4, 0, 0]),
      1 => $f(['SIENET', 3, 0, 1, 0, 0, 0]),
      2 => $f(['MERI', 0, 1, 'waterSource', 0, 0, 0]),
      3 => $f(['METSAT', 0, 1, 'animals', 0, 0, 0]),
      4 => $f(['KITEET', 0, 3, 1, 0, 0, 0]),
      5 => $f(['VUORI', 0, 0, 1, 0, 0, 2]),
      6 => $f(['MAA', 0, 1, 'spore', 0, 0, 0])
    ];
  }
}
