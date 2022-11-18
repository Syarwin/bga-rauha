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
      'activableBiomes' => BiomeCards::getActivableBiomes($player, Globals::getTurn()),
      'activableGods' => GodCards::getActivableGods($player),
      'possibleSporePlaces' => $player->getSporesPlaces(false),
    ];
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

  public function actActivateElement($elementId, $isGod, $x = null, $y = null)
  {
    // Sanity checks
    $this->checkAction('actActivateBiome');

    $state = $this->gamestate->state();

    //possibilities depend on which state we are (ST_ACT_BIOMES or ST_COUNT_ACTION)
    $args = $state['id'] == ST_ACT_BIOMES ? $this->argActBiome() : $this->argCountAction();
    if (
      (!in_array($elementId, $args['activableBiomes']) && !$isGod) ||
      (!in_array($elementId, $args['activableGods']) && $isGod)
    ) {
      throw new \BgaVisibleSystemException('You can\'t activate this Biome/God now. Should not happen');
    }

    $boolSpore = $isGod ? GodCards::activate($elementId) : BiomeCards::activate($elementId);

    if ($boolSpore && $x != null && $y != null) {
      Players::getCurrent()->placeSpore($x, $y);
    }

    // Change state
    $this->gamestate->nextState('actActivate');
  }
}
