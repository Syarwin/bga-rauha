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

trait CountTurnTrait
{
    /**
     * Determine who's next player (the first player if turn is not on going, the next player else)
     */
    public function stCountNextPlayer()
    {
        if (Globals::getTurnOnGoing() == 0) {
            Globals::setTurnOnGoing(1);
            Players::changeActive(Globals::getFirstPlayer());
            $this->gamestate->nextState('next_player_count');
        } else {
            $nextPlayerId = Players::getNextId();

            //if next player is first player, round is done
            if ($nextPlayerId == Globals::getFirstPlayer()) {
                Globals::setTurnOnGoing(0);
                $this->gamestate->nextState('end_turn');
            } else {
                Players::changeActive($nextPlayerId);
                $this->gamestate->nextState('next_player_count');
            }
        }
    }

    public function argCountAction()
    {
        $player = Players::getActive();
        return [
            'activableBiomes' => BiomeCards::getActivableBiomes($player),
            'activableGods' => GodCards::getActivableGods($player),
            'possibleSporePlaces' => $player->getSporesPlaces(false),
        ];
    }
}
