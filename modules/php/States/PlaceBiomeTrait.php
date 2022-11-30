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

trait PlaceBiomeTrait
{
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
      'possibleSporePlaces' => $player->getSporesPlaces(false),
    ];
  }

  /**
   * Instead of placing their Biome, player can discard it to win 4 crystals
   */
  public function actDiscardCrystals($pId = null)
  {
    // Sanity checks
    $this->checkAction('actDiscardCrystals');

    // get infos
    $currentPlayer = $pId ? Players::get($pId) : Players::getCurrent();
    BiomeCards::moveAllInLocation('hand', 'discard', $currentPlayer->getId());
    $currentPlayer->incCrystal(4);
    Stats::inc(STAT_NAME_COLLECTED_CRISTAL, $currentPlayer, 4);


    // Notification
    Notifications::discardBiomeCrystals($currentPlayer, BiomeCards::countInLocation('discard'));

    // Change state
    $this->gamestate->nextState('');
  }

  /**
   * Instead of placing their Biome, player can discard it to place a Spore
   */
  public function actDiscardSpore($x, $y)
  {
    // Sanity checks
    $this->checkAction('actDiscardSpore');
    $args = $this->argPlaceBiome();
    if (!in_array([$x, $y], $args['possibleSporePlaces'])) {
      throw new \BgaVisibleSystemException('You can\'t choose this place for spore. Should not happen');
    }

    // get infos
    $currentPlayer = Players::getCurrent();
    BiomeCards::moveAllInLocation('hand', 'discard', $currentPlayer->getId());
    $currentPlayer->placeSpore($x, $y);

    // Notification
    Notifications::discardBiomeSpore($currentPlayer, BiomeCards::countInLocation('discard'), $x, $y);

    // Change state
    $this->gamestate->nextState('');
  }

  public function actPlaceBiome($x, $y)
  {
    // Sanity checks
    $this->checkAction('actPlaceBiome');

    $currentPlayer = Players::getCurrent();
    $args = $this->argPlaceBiome();

    if (!in_array([$x, $y], $args['possiblePlaces'])) {
      throw new \BgaVisibleSystemException('You can\'t place this Biome here. Should not happen');
    }

    //remove previous card on 'board' 'y' 'x'
    BiomeCards::getBiomeOnPlayerBoard($currentPlayer, $x, $y)->setLocation('discard');

    // move biome on 'board' 'y' 'x'
    $biome = $currentPlayer->getBiomeInHand();
    $biome->placeOnPlayerBoard($currentPlayer, $x, $y);

    // pay for it
    $cost = $biome->getLayingCost();
    $currentPlayer->incCrystal(-$cost);

    // notification
    Notifications::placeBiome($currentPlayer, $x, $y, $biome, $cost);

    // check if there is alignment
    $alignedTypes = BiomeCards::checkAlignment($currentPlayer, $x, $y);

    foreach ($alignedTypes as $type) {
      // get God
      $god = GodCards::getGodByType($type);
      $playerLoosingGodId = $god->getPId();

      //if currentPlayer has already the god, nothing happen
      if ($currentPlayer->getId() == $playerLoosingGodId) continue;

      $playerLoosingGod = null;
      if ($playerLoosingGodId != null) {
        $playerLoosingGod = Players::get($playerLoosingGodId);
      }

      $god->moveOnPlayerBoard($currentPlayer);

      // notification
      Notifications::newAlignment($currentPlayer, $god, $type, $playerLoosingGod);
      Stats::inc(STAT_NAME_ALIGNMENTS, $currentPlayer);
    }

    $this->gamestate->nextState('');
  }
}
