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

trait ChooseBiomeTrait
{
  public function argChooseBiome()
  {
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

  public function actChooseBiome($biomeId, $pId = null)
  {
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
    $this->gamestate->setPlayerNonMultiactive($pId, '');
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
