<?php
namespace ARK\Managers;
use ARK\Core\Globals;
use ARK\Core\Game;
use ARK\Helpers\Utils;
use ARK\Helpers\Log;

/* Class to manage all the cards for Ark nova */
// ZooCards in contrast to ActionCards that are the cards on the board

class ZooCards extends \ARK\Helpers\Pieces
{
  protected static $table = 'cards';
  protected static $prefix = 'card_';
  protected static $customFields = ['player_id', 'extra_datas'];
  protected static $autoIncrement = false;
  protected static $autoremovePrefix = false;

  protected static function cast($card)
  {
    return self::getCardInstance($card['card_id'], $card);
  }

  public static function getCardInstance($id, $data = null)
  {
    $t = explode('_', $id);
    // First part before _ specify the type and the numbering
    $prefixes = [
      'A' => 'Animals',
      'S' => 'Sponsors',
      'P' => 'Projects',
    ];
    $prefix = $prefixes[$t[0][0]];
    $className = "\ARK\Cards\\$prefix\\$id";
    return new $className($data);
  }

  /* Creation of the cards */
  public static function setupNewGame($players, $options)
  {
    // Load list of cards
    include dirname(__FILE__) . '/../Cards/list.inc.php';

    foreach ($cardIds as $cId) {
      $card = self::getCardInstance($cId);
      if ($card->isSupported($players, $options)) {
        $cards[$cId] = [
          'id' => $cId,
          'location' => 'deck',
        ];
      }
    }

    foreach ($players as $pId => $player) {
      // TODO : draw 8, keep 4
      self::drawCards($cards, $pId, 4);
    }

    // Create the cards
    self::create($cards, null);

    // TODO : remove. This should only be setup after player picked their cards/map
    self::fillPool();
  }

  public static function drawCards(&$cards, $pId, $n, $location = 'hand')
  {
    $pool = array_filter($cards, function ($card) {
      return $card['location'] == 'deck';
    });
    $hand = array_rand($pool, $n);
    foreach ($hand as $cId) {
      $cards[$cId]['location'] = $location;
      $cards[$cId]['player_id'] = $pId;
    }
  }

  public static function draw($player, $n = 1)
  {
    $cards = self::pickForLocation($n, 'deck', 'hand');
    foreach ($cards as $cId => &$c) {
      $c->setPId($player->getId());
    }
    return $cards;
  }

  /**
   * fillPool: slide the cards on the pool to the left and draw additional cards to fill the pool
   */
  public static function fillPool()
  {
    if (self::countInLocation(['pool', '%']) == 6) {
      return false;
    }

    // Moving cards to fill in hole on their left
    $lastHole = null;
    for ($i = 1; $i <= 6; $i++) {
      $card = self::getInLocation(['pool', $i])->first();
      if (is_null($card) && is_null($lastHole)) {
        $lastHole = $i;
      } elseif (!is_null($card) && !is_null($lastHole)) {
        self::move($card->getId(), ['pool', $lastHole]);
        $lastHole++;
      }
    }

    // Drawing cards to fill remaining holes
    for ($i = $lastHole ?? 7; $i <= 6; $i++) {
      self::pickOneForLocation('deck', ['pool', $i]);
    }

    return self::getInLocation(['pool', '%']);
  }

  public static function getUiData()
  {
    return self::getPool()
      ->merge(self::getInLocationOrdered('inPlay'))
      ->ui();
  }

  public static function getPool($limit = null)
  {
    $limitMap = [
      1 => 1,
      2 => 2,
      3 => 2,
      4 => 3,
      5 => 3,
      6 => 3,
      7 => 4,
      8 => 4,
      9 => 4,
      10 => 5,
      11 => 5,
      12 => 5,
      13 => 6,
    ];
    $cards = self::getInLocationOrdered(['pool', '%']);
    if (!is_null($limit)) {
      $limit = $limitMap[$limit] ?? 6;
      $cards = $cards->filter(function ($card) use ($limit) {
        return $card->getPoolNumber() <= $limit;
      });
    }
    return $cards;
  }

  public static function getOfPlayer($pId)
  {
    return self::getFilteredQuery($pId, 'hand')->get();
  }

  public static function getHand($pId, $type = null)
  {
    return self::getFiltered($pId, 'hand')->filter(function ($card) use ($type) {
      return $type == null || $card->getType() == $type;
    });
  }

  /**
   * Get all the cards triggered by an event
   */
  public function getListeningCards($event)
  {
    return self::getInLocation('inPlay')
      ->merge(self::getInLocation('hand'))
      ->filter(function ($card) use ($event) {
        return $card->isListeningTo($event);
      })
      ->getIds();
  }

  /**
   * Get reaction in form of a PARALLEL node with all the activated card
   */
  public function getReaction($event, $returnNullIfEmpty = true)
  {
    $listeningCards = self::getListeningCards($event);
    if (empty($listeningCards) && $returnNullIfEmpty) {
      return null;
    }

    $childs = [];
    $passHarvest = Globals::isHarvest() ? Globals::getSkipHarvest() ?? [] : [];
    foreach ($listeningCards as $cardId) {
      if (
        in_array(
          self::get($cardId)
            ->getPlayer()
            ->getId(),
          $passHarvest
        )
      ) {
        continue;
      }

      $childs[] = [
        'action' => ACTIVATE_CARD,
        'pId' => $event['pId'],
        'args' => [
          'cardId' => $cardId,
          'event' => $event,
        ],
      ];
    }

    if (empty($childs) && $returnNullIfEmpty) {
      return null;
    }

    return [
      'type' => NODE_PARALLEL,
      'pId' => $event['pId'],
      'childs' => $childs,
    ];
  }

  /**
   * Go trough all played cards to apply effects
   */
  public function getAllCardsWithMethod($methodName)
  {
    return self::getInLocation('inPlay')->filter(function ($card) use ($methodName) {
      return \method_exists($card, 'on' . $methodName) ||
        \method_exists($card, 'onPlayer' . $methodName) ||
        \method_exists($card, 'onOpponent' . $methodName);
    });
  }

  public function applyEffects($player, $methodName, &$args)
  {
    // Compute a specific ordering if needed
    $cards = self::getAllCardsWithMethod($methodName)->toAssoc();
    $nodes = array_keys($cards);
    $edges = [];
    $orderName = 'order' . $methodName;
    foreach ($cards as $cId => $card) {
      if (\method_exists($card, $orderName)) {
        foreach ($card->$orderName() as $constraint) {
          $cId2 = $constraint[1];
          if (!in_array($cId2, $nodes)) {
            continue;
          }
          $op = $constraint[0];

          // Add the edge
          $edge = [$op == '<' ? $cId : $cId2, $op == '<' ? $cId2 : $cId];
          if (!in_array($edge, $edges)) {
            $edges[] = $edge;
          }
        }
      }
    }
    $topoOrder = Utils::topological_sort($nodes, $edges);
    $orderedCards = [];
    foreach ($topoOrder as $cId) {
      $orderedCards[] = $cards[$cId];
    }

    // Apply effects
    $result = false;
    foreach ($orderedCards as $card) {
      $res = self::applyEffect($card, $player, $methodName, $args, false);
      $result = $result || $res;
    }
    return $result;
  }

  public function applyEffect($card, $player, $methodName, &$args, $throwErrorIfNone = false)
  {
    $card = $card instanceof \AGR\Models\PlayerCard ? $card : self::get($card);
    $res = null;
    $listened = false;
    if ($player != null && $player->getId() == $card->getPId() && \method_exists($card, 'onPlayer' . $methodName)) {
      $n = 'onPlayer' . $methodName;
      $res = $card->$n($player, $args);
      $listened = true;
    } elseif (
      $player != null &&
      $player->getId() != $card->getPId() &&
      \method_exists($card, 'onOpponent' . $methodName)
    ) {
      $n = 'onOpponent' . $methodName;
      $res = $card->$n($player, $args);
      $listened = true;
    } elseif (\method_exists($card, 'on' . $methodName)) {
      $n = 'on' . $methodName;
      $res = $card->$n($player, $args);
      $listened = true;
    } elseif ($card->isAnytime($args) && \method_exists($card, 'atAnytime')) {
      $res = $card->atAnytime($player, $args);
      $listened = true;
    }

    if ($throwErrorIfNone && !$listened) {
      throw new \BgaVisibleSystemException(
        'Trying to apply effect of a card without corresponding listener : ' . $methodName . ' ' . $card->getId()
        //print_r(\debug_print_backtrace())
      );
    }

    return $res;
  }
}
