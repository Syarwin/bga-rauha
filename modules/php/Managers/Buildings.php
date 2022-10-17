<?php
namespace ARK\Managers;

/* Class to manage all the Buildings for ArkNova */

class Buildings extends \ARK\Helpers\Pieces
{
  protected static $table = 'buildings';
  protected static $prefix = 'building_';
  protected static $customFields = ['type', 'player_id', 'x', 'y'];

  protected static function cast($meeple)
  {
    return [
      'id' => (int) $meeple['id'],
      'location' => $meeple['location'],
      'pId' => $meeple['player_id'],
      'type' => $meeple['type'],
      'x' => $meeple['x'],
      'y' => $meeple['y'],
      'state' => $meeple['state'],
    ];
  }

  public static function getUiData()
  {
    return self::getAll();
  }

  public static function getOfPlayer($pId)
  {
    return self::getSelectQuery()
      ->wherePlayer($pId)
      ->get();
  }
}
