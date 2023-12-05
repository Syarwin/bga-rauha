<?php

namespace RAUHA\States;

use RAUHA\Core\Globals;
use RAUHA\Core\Notifications;
use RAUHA\Core\Engine;
use RAUHA\Core\Stats;
use RAUHA\Managers\Players;

use RAUHA\Managers\BiomeCards;
use RAUHA\Managers\GodCards;

trait NewRoundTrait
{
  function stNextRound()
  {
    //after 16 turns, end the game
    if (Globals::getTurn() == 16) {
      $this->gamestate->nextState('game_end');
    } else {
      //shuffle deckAge1 ou DeckAge2
      $active_deck = Globals::getTurn() < 8 ? 'DeckAge1' : 'DeckAge2';
      BiomeCards::shuffle($active_deck);

      //trash remaining cards and pick 4 cards per players and put it in deck1, deck2, deck3...
      for ($i = 1; $i <= Players::count(); $i++) {
        BiomeCards::moveAllInLocation('deck' . $i, 'trash');
        BiomeCards::pickForLocation(CARDS_PER_DECK, $active_deck, 'deck' . $i);
      }
    }

    $this->gamestate->nextState('round_start');
  }

  function stMoveAvatars()
  {
    Globals::incTurn(1);
    Globals::setBiomeChoices([]);

    Notifications::newTurn(Globals::getTurn());

    Players::determineFirstPlayer();
    BiomeCards::refreshAll();

    //each 4 turn, that's a 'count turn', else it's a normal turn
    if (Globals::getTurn() % 4 == 0) {
      GodCards::refreshAll();    

      if (Globals::isSyntymaShamans()){
        $playerIds = Players::getAll()->getIds();
        Globals::setActivableShamans($playerIds);
      }
      
      $this->gamestate->nextState('count_turn');
    } else {
      $this->gamestate->setAllPlayersMultiactive();
      foreach (Players::getAll() as $id => $player) {
        self::giveExtraTime($id);
      }
      $this->gamestate->nextState('action_turn');
    }
  }

  function stPreEndOfGame()
  {
    Players::clearAuxScores();

    $this->gamestate->nextState('');
  }
}
