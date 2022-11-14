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

trait ActivateTrait
{
  public function argActBiome()
  {
    $player = Players::getActive();
    return [
        //TODO choose Biomes or Places
        //   'activableBiomes' => BiomeCards::getActivableBiomes($player, Globals::getTurn()),
        //   'activableGods' => GodCards::getActivableGods($player),
        //   'possibleSporePlaces' => $player->getSporesPlaces(false),
      ];
  }

  public function actSkip()
  {
    // Sanity checks
    $this->checkAction('actSkip');

    // Notification
    Notifications::skip(Players::getCurrent());

    // Change state
    $this->gamestate->nextState('actSkip');
  }

  public function actActivateElement($elementId, $isGod)
  {
    // Sanity checks
    $this->checkAction('actActivateBiome');
    $args = $this->argActBiome();
    if (
      (!in_array($elementId, $args['activableBiomes']) && !$isGod) ||
      (!in_array($elementId, $args['activableGods']) && $isGod)
    ) {
      throw new \BgaVisibleSystemException('You can\'t activate this Biome/God now. Should not happen');
    }

    $isGod ? GodCards::activate($elementId) : BiomeCards::activate($elementId);

    // Change state
    $this->gamestate->nextState('actActivate');
  }
}