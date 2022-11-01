<?php

namespace RAUHA\States;

use PDO;
use RAUHA\Core\Globals;
use RAUHA\Core\Notifications;
use RAUHA\Core\Engine;
use RAUHA\Core\Stats;
use RAUHA\Managers\Players;
use RAUHA\Managers\BiomeCards;

trait ActionTurnTrait
{
    function argsChooseBiome()
    {
        $turn = Globals::getTurn();
        $private = [];

        foreach (Players::getAll() as $id => $player) {
            //select a deck depending on turn id
            $deck_id = $player->getNo() + DECK_TO_CHOOSE[$turn];
            if ($deck_id > Players::count()) $deck_id = 1;
            $deck = "deck" . $deck_id;

            $cards_ids = BiomeCards::getInLocation($deck)->getIds();

            $private[$id] = [
                'deck' => $deck,
                'cards_ids' => $cards_ids,
            ];
        }

        return [
            '_private' => $private
        ];
    }
}
