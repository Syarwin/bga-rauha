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
use RAUHA\Models\Player;

trait ActivateTrait
{
  public function argActBiomes()
  {
    $player = Players::getActive();
    return [
      'activableBiomes' => BiomeCards::getActivableBiomes($player, Globals::getTurn()),
      'activableGods' => GodCards::getActivableGods($player),
      'possibleSporePlaces' => $player->getSporesPlaces(false),
    ];
  }

  public function stActBiomes()
  {
    $player = Players::getActive();
    $arg = self::argActBiomes();

    if (empty($arg['activableGods']) && empty($arg['activableBiomes'])) {
      // Change state
      $this->gamestate->nextState('actSkip');
    } else self::activateAutomaticElements($player, $arg, Globals::getTurn());
  }

  public function activateAutomaticElements($player, $arg, $turn = null)
  {
    $isGod = false;
    $elementIdToActivate = null;

    $sporeCanBeAdded = false;
    foreach ($arg['activableBiomes'] as $biome) {
      if ($biome->getSporeIncome()) $sporeCanBeAdded = true;
    }
    foreach ($arg['activableGods'] as $god) {
      if ($god->getSporeIncome()) $sporeCanBeAdded = true;
    }

    // search if a biome can be automaticly activated
    foreach ($arg['activableBiomes'] as $biome) {
      if ($biome->isAutomatic($sporeCanBeAdded)) {
        $elementIdToActivate = $biome->getId();
        $isGod = false;
        break;
      }
    }
    //if not search if a god can be automaticly activated
    if ($elementIdToActivate === null) {
      foreach ($arg['activableGods'] as $god) {
        if ($god->isAutomatic($sporeCanBeAdded)) {
          $elementIdToActivate = $god->getId();
          $isGod = true;
          break;
        }
      }
    }
    //if something found activate it
    if ($elementIdToActivate !== null) {
      self::actActivateElement($elementIdToActivate, $isGod);
    }
  }

  public function actSkip($pId = null)
  {
    // Sanity checks
    $this->checkAction('actSkip');

    $player = $pId ? Players::get($pId) : Players::getCurrent();
    $player->setGodsUsed();

    // Notification
    Notifications::skip($player);

    // Change state
    $this->gamestate->nextState('actSkip');
  }

  public function actActivateElement($elementId, $isGod, $player = null, $x = null, $y = null)
  {
    // Sanity checks
    $this->checkAction('actActivateBiome');
    $player = $player ?? Players::getCurrent();

    $state = $this->gamestate->state();

    //possibilities depend on which state we are (ST_ACT_BIOMES or ST_COUNT_ACTION)
    $args = $state['name'] == 'actBiomes' ? self::argActBiomes() : self::argCountAction();
    if (
      (!$isGod && !in_array(BiomeCards::get($elementId), $args['activableBiomes'])) ||
      ($isGod && !in_array(GodCards::get($elementId), $args['activableGods']))
    ) {
      throw new \BgaVisibleSystemException('You can\'t activate this Biome/God now. Should not happen');
    }

    $boolSpore = $isGod ? GodCards::activate($elementId) : BiomeCards::activate($elementId);

    if ($boolSpore && $x != null && $y != null) {
      $player->placeSpore($x, $y);
    }

    // Change state
    $this->gamestate->nextState('actActivate');
  }
}
