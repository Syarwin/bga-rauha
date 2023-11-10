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

    // if no layingconstraints (or you are KELTAINEN1) all places are possible
    if (empty($biome->getLayingConstraints()) || $player->is(KELTAINEN_1)) {
      $possiblePlaces = ALL_BIOME_PLACES;
    } else {
      $possiblePlaces = $biome->getLayingConstraints();
    }

    $cost = $biome->getLayingCost();

    if ($player->is(HARMAA_2)){
      $cost = $cost - 1;
    }

    // but if BIOME too expensive no possible place
    if ($cost > $player->getCrystal()) {
      $possiblePlaces = [];
    }

    return [
      'biome' => $biome,
      'possiblePlaces' => $possiblePlaces,
      'possibleSporePlaces' => $player->getSporesPlaces(false),
      'firstPlayer' => Globals::getFirstPlayer()
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
    
    if ($currentPlayer->is(HARMAA_2)){
      $cost = max(0, $cost - 1);
    }

    $currentPlayer->incCrystal(-$cost);

    // notification
    Notifications::placeBiome($currentPlayer, $x, $y, $biome, $cost);

    if($currentPlayer->is(HARMAA_1)){
      $animalsCount = count($biome->getAnimals());

      if ($animalsCount){
        $currentPlayer->movePointsToken($animalsCount, STAT_NAME_SHAMAN_POINTS);
        Notifications::shaman($currentPlayer, SHAMAN_ON_GOING_POWER, $animalsCount, "points");
      }
    } elseif ($currentPlayer->is(SININEN_1)){
      $waterCount = $biome->getWaterSource();
      //check if this player has VUORI 2 then add waterSource corresponding to marine animal
      if ($currentPlayer->hasVuori2()) {
        $waterCount += in_array(MARINE, $biome->getAnimals()) ? 1: 0;
      }
      if ($waterCount){
        $currentPlayer->incCrystal($waterCount);
        Stats::inc(STAT_NAME_SHAMAN_CRISTAL, $currentPlayer, $waterCount);
        Notifications::shaman($currentPlayer, SHAMAN_ON_GOING_POWER, $waterCount, "crystal");
      }
    }

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


      //give points or crystal to player according to their ongoing shaman power
      if ($currentPlayer->is(PUNAINEN_1)){
        $currentPlayer->incCrystal(2);
        Stats::inc(STAT_NAME_SHAMAN_CRISTAL, $currentPlayer, 2);
        Notifications::shaman($currentPlayer, SHAMAN_ON_GOING_POWER, 2, "crystal");
      } elseif ($currentPlayer->is(SININEN_2)){
        $currentPlayer->movePointsToken(4, STAT_NAME_SHAMAN_POINTS);
        Notifications::shaman($currentPlayer, SHAMAN_ON_GOING_POWER, 4, "points");
      } elseif ($currentPlayer->is(SININEN_1)){ // 1 crytal for each water source
        if ($god->getName() === 'Vuori') {
          Stats::inc(STAT_NAME_SHAMAN_CRISTAL, $currentPlayer, 2);
          Notifications::shaman($currentPlayer, SHAMAN_ON_GOING_POWER, 2, "crystal");
        } elseif ($god->getName() === 'Vuori II') {
          $crystalIncome = BiomeCards::countOnAPlayerBoard($currentPlayer, MARINE);
          Stats::inc(STAT_NAME_SHAMAN_CRISTAL, $currentPlayer, $crystalIncome);
          Notifications::shaman($currentPlayer, SHAMAN_ON_GOING_POWER, $crystalIncome, "crystal");
        } 
      }
    }

    $this->gamestate->nextState('');
  }
}
