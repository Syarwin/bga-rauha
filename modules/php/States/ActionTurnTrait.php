<?php

namespace RAUHA\States;

use PDO;
use RAUHA\Core\Game;
use RAUHA\Core\Globals;
use RAUHA\Core\Notifications;
use RAUHA\Core\Engine;
use RAUHA\Core\Stats;
use RAUHA\Managers\Players;
use RAUHA\Managers\BiomeCards;
use RAUHA\Managers\GodCards;

trait ActionTurnTrait
{
  public function argChooseBiome()
  {
    $choices = Globals::getBiomeChoices();
    $turn = Globals::getTurn();
    $private = [];

    foreach (Players::getAll() as $id => $player) {
      //select a deck depending on turn id
      $deckId = $player->getNo() + DECK_TO_CHOOSE[$turn];
      if ($deckId > Players::count()) {
        $deckId = 1;
      }
      $deck = 'deck' . $deckId;

      $biomes = BiomeCards::getInLocation($deck);

      $private[$id] = [
        'choice' => $choices[$id] ?? null,
        'deck' => $deck,
        'biomes' => $biomes,
      ];
    }

    return [
      '_private' => $private,
    ];
  }

  public function actChooseBiome($biomeId, $pId)
  {
    // Sanity checks
    $this->gamestate->checkPossibleAction('actChooseBiome');
    $pId = $pId ?? Players::getCurrentId();
    //check that this biome was available to be choosen
    $args = $this->argChooseBiome();
    if (!array_key_exists($biomeId, $args['_private'][$pId]['biomes'])) {
      throw new \BgaVisibleSystemException('You can\'t choose this biome. Should not happen');
    }

    // Highligh that card and make the player inactive
    $choices = Globals::getBiomeChoices();
    $choices[$pId] = $biomeId;
    Globals::setBiomeChoices($choices);
    Notifications::chooseBiome($pId, $biomeId);
    $this->gamestate->setPlayerNonMultiactive($pId, '');
  }

  /**
   * Confirm player choices by moving the selected cards to hand and removing other cards
   */
  public function stConfirmChoices()
  {
    $choices = Globals::getBiomeChoices();
    foreach (Players::getAll() as $pId => $player) {
      $choice = $choices[$pId] ?? null;
      if (is_null($choice)) {
        throw new \BgaVisibleSystemException('Someone hasnt made any choice yet. Should not happen');
      }

      BiomeCards::move($choice, 'hand', $pId);
    }

    $turn = Globals::getTurn();
    Notifications::confirmChoices($turn);
    $this->gamestate->nextState();
  }

  /**
   * Determine who's next player (the first player in turn order with a Biome in hand)
   */
  public function stNextPlayer()
  {
    $playerToPlay = null;
    foreach (Players::getTurnOrder() as $pId) {
      if (Players::get($pId)->hasBiomeInHand()) {
        $playerToPlay = $pId;
        break;
      }
    }

    // active a player and change state
    if ($playerToPlay) {
      Players::changeActive($playerToPlay);
      $this->gamestate->nextState('next_player_action');
    } else {
      $this->gamestate->nextState('end_turn');
    }
  }

  public function argPlaceBiome()
  {
    $player = Players::getActive();
    $biome = $player->getBiomeInHand();

    // if no layingconstraints all places are possible
    // TODO error on getLayingConstraints
    if (empty($biome->getLayingConstraints())) {
      $possiblePlaces = ALL_BIOME_PLACES;
    } else {
      $possiblePlaces = $biome->getLayingConstraints();
    }

    // but if BIOME too expensive no possible place
    if ($biome->getLayingCost() > $player->getCrystal()) {
      $possiblePlaces = [];
    }

    return [
      'biome' => $biome,
      'possiblePlaces' => $possiblePlaces,
      'possibleSporePlaces' => $player->getSporesPlaces(false)
    ];
  }

  /**
   * Instead of placing their Biome, player can discard it to win 4 crystals
   */
  public function actDiscardCrystal()
  {
    // Sanity checks
    $this->gamestate->checkPossibleAction('actDiscardCrystal');

    // get infos
    $currentPlayer = Players::getCurrent();
    BiomeCards::moveAllInLocation('hand', 'discard', $currentPlayer->getId());
    $currentPlayer->incCrystal(4);

    // Notification
    Notifications::discard($currentPlayer, BiomeCards::countInLocation('discard'));

    // Change state
    $this->gamestate->nextState('');
  }

  /**
   * Instead of placing their Biome, player can discard it to place a Spore
   */
  public function actDiscardSpore($x, $y)
  {
    // Sanity checks
    $this->gamestate->checkPossibleAction('actDiscardSpore');
    $args = $this->argPlaceBiome();
    if (!in_array([$x, $y], $args['possibleSporePlaces'])) {
      throw new \BgaVisibleSystemException('You can\'t choose this place for spore. Should not happen');
    }

    // get infos
    $currentPlayer = Players::getCurrent();
    BiomeCards::moveAllInLocation('hand', 'discard', $currentPlayer->getId());
    $currentPlayer->placeSpore($x, $y);

    // Notification
    Notifications::discardSpore($currentPlayer, BiomeCards::countInLocation('discard'), $x, $y);

    // Change state
    $this->gamestate->nextState('');
  }

  public function actPlaceBiome($x, $y)
  {
    // Sanity checks
    $this->gamestate->checkPossibleAction('actPlaceBiome');

    $currentPlayer = Players::getCurrent();
    $args = $this->argPlaceBiome();

    if (!in_array([$x, $y], $args['possiblePlaces'])) {
      throw new \BgaVisibleSystemException('You can\'t place this Biome here. Should not happen');
    }

    //remove previous card on 'board' 'y' 'x'
    BiomeCards::getBiomeOnPlayerBoard($currentPlayer, $x, $y)->setLocation('discard');

    // move biome on 'board' 'y' 'x'
    $biome = BiomeCards::getInLocation('hand', $currentPlayer);
    $biome->placeOnPlayerBoard($x, $y);

    // pay for it
    $currentPlayer->incCrystal(-$biome->getLayingCost());

    // notification
    Notifications::placeBiome($currentPlayer, $x, $y, $biome);

    // check if there is alignment
    $alignedTypes = BiomeCards::checkAlignment($currentPlayer, $x, $y, $biome);

    foreach ($alignedTypes as $type) {
      // get God
      $god = GodCards::getGodByType($type);
      $god->moveOnPlayerBoard($currentPlayer);

      // notification
      Notifications::newAlignment($currentPlayer, $god, $type);
    }

    $this->gamestate->nextState('');
  }

  public function argActBiome()
  {
    $player = Players::getActive();
    return [
      //TODO choose Biomes or Places
      'activableBiomes' => BiomeCards::getActivableBiomes($player, Globals::getTurn()),
      'activableGods' => GodCards::getActivableGods($player),
      'possibleSporePlaces' => $player->getSporesPlaces(false)
    ];
  }

  public function actSkip()
  {
    // Sanity checks
    $this->gamestate->checkPossibleAction('actSkip');

    // Notification
    Notifications::skip(Players::getCurrent());

    // Change state
    $this->gamestate->nextState('actSkip');
  }

  public function actActivateElement($elementId, $isGod)
  {
    // Sanity checks
    $this->gamestate->checkPossibleAction('actActivateBiome');
    $args = $this->argActBiome();
    if ((!in_array($elementId, $args['activableBiomes']) && !$isGod) ||
      (!in_array($elementId, $args['activableGods']) && $isGod)
    ) {
      throw new \BgaVisibleSystemException('You can\'t activate this Biome/God now. Should not happen');
    }

    $isGod ? GodCards::activate($elementId) : BiomeCards::activate($elementId);

    // Change state
    $this->gamestate->nextState('actActivate');
  }
}
