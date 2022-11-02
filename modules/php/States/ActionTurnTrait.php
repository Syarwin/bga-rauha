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
    public function argChooseBiome()
    {
        $turn = Globals::getTurn();
        $private = [];

        foreach (Players::getAll() as $id => $player) {
            //select a deck depending on turn id
            $deckId = $player->getNo() + DECK_TO_CHOOSE[$turn];
            if ($deckId > Players::count()) $deckId = 1;
            $deck = "deck" . $deckId;

            $cardsIds = BiomeCards::getInLocation($deck)->getIds();

            $private[$id] = [
                'deck' => $deck,
                'cardsIds' => $cardsIds,
            ];
        }

        return [
            '_private' => $private,
        ];
    }

    public function actChooseBiome($cardId)
    {
        // Sanity checks
        $this->checkAction('actPlayCard');
        $pId = Players::getCurrent()->getId();
        //check that this card was available to be choosen
        $args = $this->argsChooseBiome();
        if (!in_array($cardId, $args['_private'][$pId]['cards_ids'])) {
            throw new \BgaVisibleSystemException('You can\'t play this card. Should not happen');
        }

        BiomeCards::move($cardId, 'hand', $pId);

        //record that player has played.
        $this->gamestate->setPlayerNonMultiactive($pId, '');
        //TODO HOW to allow player to cancel and play again
    }

    public function stNextPlayer()
    {
        //TODO
    }

    public function argPlaceBiome()
    {
        $player = Players::getActive()->getId();
        $biome = BiomeCards::getInLocation('hand', $player->getId());

        // if no layingconstraints all places are possible 
        if (empty($biome->getLayingConstraints())) $possiblePlaces = ALL_BIOME_PLACES;
        else $possiblePlaces = $biome->getLayingConstraints();

        // but if BIOME too expensive no possible place
        if ($biome->getLayingCost() > $player->getCrystal()) $possiblePlaces = [];

        return [
            'playerId' => $player->getId(),
            'cardId' => $biome->getId(),
            'possiblePlaces' => $possiblePlaces
        ];
    }

    /**
     * Instead of placing their Biome, player can discard it to win 4 crystals
     */
    function actDiscard()
    {
        // Sanity checks
        $this->checkAction('actDiscard');

        $currentPlayer = Players::getCurrent();

        BiomeCards::moveAllInLocation('hand', 'discard', $currentPlayer->getId());

        $currentPlayer->incCrystal(4);
    }
}
