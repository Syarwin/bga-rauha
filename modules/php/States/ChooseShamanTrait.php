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

trait ChooseShamanTrait
{
  public function stChooseShaman()
  {
    if (!Globals::isSyntyma()){
      $this->gamestate->nextState('skip');
    }
  }

  public function argChooseShaman()
  {
    $this->queryStandardTables();
    $choices = Globals::getShamanChoices();
    $private = [];

    foreach (Players::getAll() as $id => $player) {
      $shaman = $player->getShamanName();

      $private[$id] = [
        'choice' => $choices[$id] ?? null,
        'shaman' => $shaman,
      ];
    }

    return [
      '_private' => $private,
    ];
  }

  public function updateActivePlayersAndChangeStateShaman()
  {
    // Compute players that still need to select their card
    // => use that instead of BGA framework feature because in some rare case a player
    //    might become inactive eventhough the selection failed (seen in Agricola at least already)
    $selections = Globals::getShamanChoices();
    $players = Players::getAll();
    $ids = $players->getIds();
    $ids = array_diff($ids, array_keys($selections));

    // At least one player need to make a choice
    if (!empty($ids)) {
      $this->gamestate->setPlayersMultiactive($ids, '', true);
    }
    // Everyone is done => discard cards and proceed
    else {
      $this->gamestate->nextState('done');
    }
  }


  public function actChooseShaman($sideId, $pId = null)
  {
    $this->queryStandardTables();
    // Sanity checks
    $this->gamestate->checkPossibleAction('actChooseShaman');
    $pId = $pId ?? Players::getCurrentId();
    //check that the side is correct
    if ($sideId != 1 || $sideId != 2) {
      throw new \BgaVisibleSystemException('You can\'t choose this side. Should not happen');
    }

    // Highlight that card and make the player inactive
    $choices = Globals::getShamanChoices();
    $choices[$pId] = $sideId;
    Globals::setBiomeChoices($choices);
    Notifications::chooseShaman(Players::get($pId), $sideId);
    $this->updateActivePlayersAndChangeStateShaman();
  }

  /**
   * Confirm player choices by moving the selected cards to hand and removing other cards
   */
  public function stConfirmChoicesShaman()
  {
    $choices = Globals::getShamanChoices();

    foreach (Players::getAll() as $pId => $player) {
      $choice = $choices[$pId] ?? null;
      if (is_null($choice)) {
        throw new \BgaVisibleSystemException('Someone hasnt made any choice yet. Should not happen');
      }

    }

    Notifications::confirmShamanChoices();
    $this->gamestate->nextState();
  }
}
