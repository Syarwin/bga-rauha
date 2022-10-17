<?php
namespace RAUHA\States;
use RAUHA\Core\Globals;
use RAUHA\Core\Notifications;
use RAUHA\Core\Engine;
use RAUHA\Core\Stats;
use RAUHA\Managers\Players;

trait TurnTrait
{
  function stBeforeStartOfTurn()
  {
    $this->gamestate->nextState();
  }
}
