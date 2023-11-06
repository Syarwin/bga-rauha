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
      'activableShaman' => in_array($player->getId(), Globals::getActivableShamans()),
      'possibleSporePlaces' => $player->getSporesPlaces(false),
    ];
  }

  public function stActBiomes()
  {
    $player = Players::getActive();
    $arg = $this->getArgs();

    if (empty($arg['activableGods']) && empty($arg['activableBiomes'])) {
      $this->actSkip($player->getId(), true);
    } elseif ($player->getPref(OPTION_ACTIVATION) == OPTION_AUTOMATIC_ACTIVATION) {
      $this->activateAutomaticElements($arg);
    }
  }

  public function activateAutomaticElements($arg)
  {
    $isGod = false;
    $elementIdToActivate = null;

    $sporeCanBeAdded = false;
    foreach ($arg['activableBiomes'] as $biome) {
      if ($biome->getSporeIncome()) {
        $sporeCanBeAdded = true;
      }
    }
    foreach ($arg['activableGods'] as $god) {
      if ($god->getSporeIncome()) {
        $sporeCanBeAdded = true;
      }
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

    //if not search if shaman is activable
    if ($elementIdToActivate === null) {
      if ($arg['activableShaman']){
        $elementIdToActivate = Players::getActive()->getId();
      }
    }


    //if something found activate it
    if ($elementIdToActivate !== null) {
      self::actActivateElement($elementIdToActivate, $isGod, Players::getActive());
    } else {
      //it means that there is element to activate but it can't be automatic, then give time to player
      self::giveExtraTime(Players::getActive()->getId());
    }
  }

  public function actSkip($pId = null, $auto = false)
  {
    // Sanity checks and bypass for automatisation
    if ($pId) {
      $this->gamestate->checkPossibleAction('actSkip');
      $player = Players::get($pId);
    } else {
      $this->checkAction('actSkip');
      $player = Players::getCurrent();
    }

    $player->setGodsUsed();
    $player->setUsed();

    //if we are in a count turn -> count for water source
    if (Globals::getTurn() % 4 == 0) {
      Players::getPointsForWaterSource($player);
    }

    // Notification
    Notifications::skip($player, $auto);

    // Change state
    $this->gamestate->nextState('actSkip');
  }

  public function actActivateElement($elementId, $isGod, $player = null, $x = null, $y = null)
  {
    // Sanity checks and bypass for automatisation
    if ($player) {
      $this->gamestate->checkPossibleAction('actActivateBiome');
    } else {
      $this->checkAction('actActivateBiome');
      $player = Players::getCurrent();
    }

    $args = $this->getArgs();
    if (
      (!$isGod && $elementId != $player->getId() && !in_array(BiomeCards::get($elementId), $args['activableBiomes'])) ||
      ($isGod && !in_array(GodCards::get($elementId), $args['activableGods']))
    ) {
      throw new \BgaVisibleSystemException('You can\'t activate this Shaman/Biome/God now. Should not happen');
    }

    $isGod ? 
      GodCards::activate($elementId) : 
      ($elementId != $player->getId()) ? 
        BiomeCards::activate($elementId, $x, $y) :
        $player->activate();

    //record a new activation in Stats
    if (Globals::getTurn() % 4 == 0) {
      Stats::inc(STAT_NAME_END_ROUND_ACTIVATIONS, $player);
    } else {
      Stats::inc(STAT_NAME_END_STEP_ACTIVATIONS, $player);
    }

    // Change state
    $this->gamestate->nextState('actActivate');
  }
}
