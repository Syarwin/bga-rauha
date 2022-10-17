<?php
namespace ARK\Managers;
use ARK\Core\Stats;
use ARK\Helpers\UserException;

/* Class to manage all the meeples for ArkNova */

class Meeples extends \ARK\Helpers\Pieces
{
  protected static $table = 'meeples';
  protected static $prefix = 'meeple_';
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
    return [];
  }

  /* Creation of various meeples */
  public static function setupNewGame($players, $options)
  {
  }

  public function createResourceInLocation($type, $location, $player_id, $x, $y, $nbr = 1, $state = null)
  {
    $meeples = [
      [
        'type' => $type,
        'player_id' => $player_id,
        'location' => $location,
        'x' => $x,
        'y' => $y,
        'nbr' => $nbr,
        'state' => $state,
      ],
    ];

    $ids = self::create($meeples);
    return $ids;
  }

  // Default function to create a resource in reserve
  public function createResourceInReserve($pId, $type, $nbr = 1)
  {
    return self::createResourceInLocation($type, 'reserve', $pId, null, null, $nbr);
  }

  public function countReserveResource($pId, $type = null)
  {
    return self::getReserveResource($pId, $type)->count();
  }

  public function getReserveResource($pId, $type = null)
  {
    $query = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('meeple_location', 'reserve');

    if ($type != null) {
      $query = $query->where('type', $type);
    }
    return $query->get();
  }

  public function useResource($player_id, $resourceType, $amount)
  {
    $deleted = [];
    if ($amount == 0) {
      return [];
    }

    // $resource = self::getReserveResource($player_id, $resourceType);
    $resource = self::getResourceOfType($player_id, $resourceType);

    if (count($resource) < $amount) {
      throw new UserException(sprintf(clienttranslate('You do not have enough %s'), $resourceType));
    }

    foreach ($resource as $id => $res) {
      $deleted[] = $res;
      self::DB()->delete($id);
      $amount--;
      if ($amount == 0) {
        break;
      }
    }

    return $deleted;
  }

  public function getResourceOfType($pId, $type)
  {
    $query = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', $type)
      ->where('meeple_location', 'NOT LIKE', 'turn_%')
      ->orderBy('meeple_location', 'DESC');

    return $query->get();
  }

  public function getPartnerZoo($pId, $continent)
  {
    if (is_null($continent)) {
      return [];
    }

    $query = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', 'partner_' . $continent);

    return $query->get();
  }

  public function hasPartnerZoo($pId, $continent)
  {
    return count(self::getPartnerZoo($pId, $continent)) > 0;
  }
}
