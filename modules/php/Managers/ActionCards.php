<?php
namespace ARK\Managers;

use ARK\Core\Globals;
use ARK\Core\Meeples;

/* Class to manage all the action cards for Ark Nova */

class ActionCards extends \ARK\Helpers\Pieces
{
  protected static $table = 'actioncards';
  protected static $prefix = 'card_';
  protected static $customFields = ['level', 'player_id', 'extra_datas', 'type'];
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;

  protected static function cast($card)
  {
    return self::getInstance($card['type'], $card);
  }

  protected static function getInstance($type, $row = null)
  {
    $className = '\ARK\Cards\Actions\\Action' . $type;
    return new $className($row);
  }

  /* Creation of the cards */
  protected static $actionCards = ['Build', 'Cards', 'Animals', 'Association', 'Sponsors'];
  public static function setupNewGame($players, $options)
  {
    $cards = [];
    $turn = 1;
    foreach ($players as $pId => $player) {
      $rand = range(2, 5);
      shuffle($rand);

      foreach (self::$actionCards as $type) {
        $cards[] = [
          'type' => $type,
          'player_id' => $pId,
          'location' => $type == 'Animals' ? 1 : array_pop($rand),
          'state' => 0,
          'level' => 1,
        ];
      }
    }

    self::create($cards, null);
  }

  public function getOfPlayer($pId)
  {
    return self::getFilteredQuery($pId)->get();
  }
}
