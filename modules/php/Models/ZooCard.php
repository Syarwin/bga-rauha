<?php
namespace ARK\Models;

/*
 * ZooCard
 */

class ZooCard extends \ARK\Helpers\DB_Model
{
  protected $implemented = true; // For DEV only

  protected $table = 'cards';
  protected $primary = 'card_id';
  protected $attributes = [
    'id' => ['card_id', 'int'],
    'location' => 'card_location',
    'pId' => ['player_id', 'int'],
    'extraDatas' => ['extra_datas', 'obj'],
  ];

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

  public function getPoolNumber()
  {
    $t = explode('-', $this->location);
    return $t[0] == 'pool' ? ((int) $t[1]) : null;
  }

  public function getPlayer($checkPlayed = false)
  {
    if (!$this->isPlayed() && $checkPlayed) {
      throw new \feException("Trying to get the player for a non-played card : {$this->id}");
    }

    return Players::get($this->pId);
  }

  // /**
  //  * Scores functions
  //  */
  // protected function addScoringEntry($score, $desc, $isBonus = true, $player = null)
  // {
  //   if (!$this->isPlayed()) {
  //     throw new \feException("Trying to addScoringEntry for a non-played card : {$this->id}");
  //   }
  //
  //   if (is_string($desc)) {
  //     $desc = [
  //       'log' => $desc,
  //       'args' => [],
  //     ];
  //   }
  //   $player = $player ?? $this->getPlayer();
  //   $desc['args']['i18n'][] = 'card_name';
  //   $desc['args']['card_name'] = $this->getName();
  //   $desc['args']['player_name'] = $player ?? $player->getName();
  //   $desc['args']['score'] = $score;
  //   Scores::addEntry($player, $isBonus ? SCORING_CARDS_BONUS : SCORING_CARDS, $score, $desc, null, $this->getName());
  // }
  //
  // protected function addBonusScoringEntry($score, $desc = null, $player = null)
  // {
  //   $desc = $desc ?? $this->getBonusDescription();
  //   $this->addScoringEntry($score, $desc, true, $player);
  // }
  //
  // protected function addQuantityScoringEntry($n, $scoresMap, $descSingular, $descPlural)
  // {
  //   if (!$this->isPlayed()) {
  //     throw new \feException("Trying to addScoringEntry for a non-played card : {$this->id}");
  //   }
  //
  //   Scores::addQuantityEntry(
  //     $this->getPlayer(),
  //     SCORING_CARDS_BONUS,
  //     $n,
  //     $scoresMap,
  //     $descSingular,
  //     $descPlural,
  //     $this->getName()
  //   );
  // }
  //
  // public function computeScore()
  // {
  //   if ($this->vp != 0) {
  //     $this->addScoringEntry(
  //       $this->vp,
  //       clienttranslate('${player_name} earns ${score} for owning ${card_name}'),
  //       false
  //     );
  //   }
  //
  //   $this->computeBonusScore();
  // }
  //
  // public function getBonusDescription()
  // {
  //   return clienttranslate('${player_name} earns ${score} for bonus of ${card_name}');
  // }
  //
  // public function computeBonusScore()
  // {
  //   $bonus = $this->getBonusScore();
  //   if ($bonus != 0) {
  //     $this->addBonusScoringEntry($bonus);
  //   }
  // }
  //
  // public function getBonusScore()
  // {
  //   return $this->getExtraDatas(BONUS_VP) ?? 0;
  // }
  //
  // public function incBonusScore($amount)
  // {
  //   $newScore = $this->getBonusScore() + $amount;
  //   $this->setExtraDatas(BONUS_VP, $newScore);
  //   return $newScore;
  // }

  /**
   * Event modifiers template
   **/
  public function isListeningTo($event)
  {
    return false;
  }

  // public function isAnytime($event, $action = null)
  // {
  //   $node = Engine::getNextUnresolved();
  //   $ctxArgs = $node == null ? [] : $node->getArgs();
  //   return ($event['type'] ?? null) == 'anytime' &&
  //     $this->getPlayer()->getId() == Players::getActiveId() &&
  //     ($action == null || $event['action'] == $action) &&
  //     ($ctxArgs['cardId'] ?? null) != $this->id;
  // }
  //
  // protected function isActionCardEvent($event, $actionCardType, $playerConstraint = 'player', $immediately = false)
  // {
  //   return $event['type'] == 'action' &&
  //     $event['action'] == 'PlaceFarmer' &&
  //     ($event['actionCardType'] ?? null) == $actionCardType &&
  //     (is_null($playerConstraint) ||
  //       ($playerConstraint == 'player' && $this->pId == $event['pId']) ||
  //       ($playerConstraint == 'opponent' && $this->pId != $event['pId'])) &&
  //     ((!$immediately && $event['method'] == 'PlaceFarmer') ||
  //       ($immediately && $event['method'] == 'ImmediatelyAfterPlaceFarmer'));
  // }
  //
  // protected function isActionCardTurnEvent($event, $turns, $playerConstraint = 'player', $immediately = false)
  // {
  //   $cardId = $event['actionCardId'] ?? null;
  //   if ($cardId != null) {
  //     $card = Utils::getActionCard($cardId);
  //     $turn = $card->getTurn();
  //     if (in_array($turn, $turns)) {
  //       $type = $card->getActionCardType();
  //       return $this->isActionCardEvent($event, $type);
  //     }
  //   }
  // }
  //
  // protected function isActionEvent($event, $action, $playerConstraint = 'player', $immediately = false)
  // {
  //   return $event['type'] == 'action' &&
  //     $event['action'] == $action &&
  //     (is_null($playerConstraint) ||
  //       ($playerConstraint == 'player' && $this->pId == $event['pId']) ||
  //       ($playerConstraint == 'opponent' && $this->pId != $event['pId'])) &&
  //     (($immediately && $event['method'] == 'ImmediatelyAfter' . $action) ||
  //       (!$immediately && $event['method'] == 'After' . $action));
  // }

  /****************************
   ****** SYNTAXIC SUGAR *******
   ****************************/
  // public function gainNode($gain, $pId = null)
  // {
  //   $gain['pId'] = $pId ?? $this->pId;
  //   return [
  //     'action' => GAIN,
  //     'args' => $gain,
  //     'source' => $this->name,
  //     'cardId' => $this->getId(),
  //   ];
  // }
  //
  // public function payNode($cost, $sourceName = null, $nb = 1, $to = null, $pId = null)
  // {
  //   return [
  //     'action' => PAY,
  //     'args' => [
  //       'pId' => $pId ?? $this->pId,
  //       'nb' => $nb,
  //       'costs' => Utils::formatCost($cost),
  //       'source' => $sourceName ?? $this->name,
  //       'to' => $to,
  //     ],
  //   ];
  // }
  //
  // public function payGainNode($cost, $gain, $sourceName = null, $optional = true, $pId = null)
  // {
  //   $pId = $pId ?? $this->pId;
  //
  //   return [
  //     'type' => NODE_SEQ,
  //     'optional' => $optional,
  //     'pId' => $pId,
  //     'childs' => [$this->payNode($cost, $sourceName), $this->gainNode($gain, $pId)],
  //   ];
  // }
  //
  // public function receiveNode($mId, $updateObtained = false)
  // {
  //   return [
  //     'action' => RECEIVE,
  //     'args' => [
  //       'meeple' => $mId,
  //       'updateObtained' => $updateObtained,
  //     ],
  //   ];
  // }
}
