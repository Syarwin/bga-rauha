<?php

namespace RAUHA\States;

use PDO;
use RAUHA\Core\Globals;
use RAUHA\Core\Notifications;
use RAUHA\Core\Engine;
use RAUHA\Core\Stats;
use RAUHA\Managers\Players;
use RAUHA\Managers\BiomeCards;
use RAUHA\Managers\GodCards;

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

            $biomesIds = BiomeCards::getInLocation($deck)->getIds();

            $private[$id] = [
                'deck' => $deck,
                'biomesIds' => $biomesIds,
            ];
        }

        return [
            '_private' => $private,
        ];
    }

    public function actChooseBiome($biomeId)
    {
        // Sanity checks
        $this->checkAction('actPlaybiome');
        $pId = Players::getCurrent()->getId();
        //check that this biome was available to be choosen
        $args = $this->argChooseBiome();

        if (!in_array($biomeId, $args['_private'][$pId]['biomes_ids'])) {
            throw new \BgaVisibleSystemException('You can\'t choose this biome. Should not happen');
        }

        BiomeCards::move($biomeId, 'hand', $pId);

        //record that player has played.
        $this->gamestate->setPlayerNonMultiactive($pId, '');

        //notification
        Notifications::message(
            clienttranslate('${player_name} chooses their Biome'),
            ['player' => Players::getCurrent()]
        );

        //TODO HOW to allow player to cancel and play again
    }

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
        $player = Players::getActive()->getId();
        $biome = BiomeCards::getInLocation('hand', $player->getId());

        // if no layingconstraints all places are possible 
        if (empty($biome->getLayingConstraints())) $possiblePlaces = ALL_BIOME_PLACES;
        else $possiblePlaces = $biome->getLayingConstraints();

        // but if BIOME too expensive no possible place
        if ($biome->getLayingCost() > $player->getCrystal()) $possiblePlaces = [];

        return [
            'playerId' => $player->getId(),
            'biomeId' => $biome->getId(),
            'possiblePlaces' => $possiblePlaces
        ];

        // Change state
        $this->gamestate->nextState('actDiscard');
    }

    /**
     * Instead of placing their Biome, player can discard it to win 4 crystals
     */
    public function actDiscard()
    {
        // Sanity checks
        $this->checkAction('actDiscard');

        // get infos
        $currentPlayer = Players::getCurrent();
        BiomeCards::moveAllInLocation('hand', 'discard', $currentPlayer->getId());
        $currentPlayer->incCrystal(4);

        // Notification
        Notifications::discard($currentPlayer, BiomeCards::countInLocation('discard'));

        // Change state
        $this->gamestate->nextState('actDiscard');
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
        $biome = BiomeCards::getInLocation('hand', $currentPlayer);
        $biome->placeOnPlayerBoard($x, $y);

        // pay for it
        $currentPlayer->incCrystal(-$biome->getLayingCost());

        // notification
        Notifications::placeBiome($currentPlayer, $x, $y, $biome);

        // check if there is alignment
        $alignedTypes = BiomeCards::checkAlignment($currentPlayer, $x, $y, $biome);

        foreach ($alignedTypes as $type) {
            // get God
            $god = GodCards::getGodByType($type);
            $god->moveOnPlayerBoard($currentPlayer);

            // notification
            Notifications::newAlignment($currentPlayer, $god);
        }

        $this->gamestate->nextState('');
    }
}
