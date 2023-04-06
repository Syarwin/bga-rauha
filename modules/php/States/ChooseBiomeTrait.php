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

trait ChooseBiomeTrait
{
  public function argChooseBiome()
  {
    $this->queryStandardTables();
    $choices = Globals::getBiomeChoices();
    $turn = Globals::getTurn();
    $private = [];

    foreach (Players::getAll() as $id => $player) {
      //select a deck depending on turn id
      $deckId = $player->getNo() + DECK_TO_CHOOSE[$turn];
      if ($deckId > Players::count()) {
        $deckId = 1;
      }
      $deck = 'deck' . $deckId;

      $biomes = BiomeCards::getInLocation($deck);

      $private[$id] = [
        'choice' => $choices[$id] ?? null,
        'deck' => $deck,
        'biomes' => $biomes,
        'isMoon' => DECK_TO_CHOOSE[$turn],
      ];
    }

    return [
      '_private' => $private,
    ];
  }

  public function updateActivePlayersAndChangeState()
  {
    // Compute players that still need to select their card
    // => use that instead of BGA framework feature because in some rare case a player
    //    might become inactive eventhough the selection failed (seen in Agricola at least already)
    $selections = Globals::getBiomeChoices();
    $players = Players::getAll();
    $ids = $players->getIds();
    $ids = array_diff($ids, array_keys($selections));

    // At least one player need to make a choice
    if (!empty($ids)) {
      $this->gamestate->setPlayersMultiactive($ids, '', true);
    }
    // Everyone is done => discard cards and proceed
    else {
      $this->gamestate->nextState('');
    }
  }


  public function actChooseBiome($biomeId, $pId = null)
  {
    $this->queryStandardTables();
    // Sanity checks
    $this->gamestate->checkPossibleAction('actChooseBiome');
    $pId = $pId ?? Players::getCurrentId();
    //check that this biome was available to be choosen
    $args = $this->argChooseBiome();
    if (!array_key_exists($biomeId, $args['_private'][$pId]['biomes'])) {
      throw new \BgaVisibleSystemException('You can\'t choose this biome. Should not happen');
    }

    // Highlight that card and make the player inactive
    $choices = Globals::getBiomeChoices();
    $choices[$pId] = $biomeId;
    Globals::setBiomeChoices($choices);
    Notifications::chooseBiome(Players::get($pId), $biomeId);
    $this->updateActivePlayersAndChangeState();
  }

  /**
   * Confirm player choices by moving the selected cards to hand and removing other cards
   */
  public function stConfirmChoices()
  {
    $choices = Globals::getBiomeChoices();

    foreach (Players::getAll() as $pId => $player) {
      $choice = $choices[$pId] ?? null;
      if (is_null($choice)) {
        throw new \BgaVisibleSystemException('Someone hasnt made any choice yet. Should not happen');
      }

      BiomeCards::move($choice, 'hand', $pId);
    }

    $turn = Globals::getTurn();
    $isMoon = DECK_TO_CHOOSE[$turn];
    Notifications::confirmChoices($turn, $isMoon);
    $this->gamestate->nextState();
  }
}
