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

trait CountTurnTrait
{
    /**
     * Determine who's next player (the first player in turn order with spore NOT_USED)
     */
    public function stCountNextPlayer()
    {

        // $playerToPlay = null;
        // foreach (Players::getTurnOrder() as $pId) {
        //     if (Players::get($pId)->hasBiomeInHand()) {
        //         $playerToPlay = $pId;
        //         break;
        //     }
        // }

        // // active a player and change state
        // if ($playerToPlay) {
        //     Players::changeActive($playerToPlay);
        //     $this->gamestate->nextState('next_player_action');
        // } else {
        //     $this->gamestate->nextState('end_turn');
        // }
    }
}
