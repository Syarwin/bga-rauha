<?php

namespace RAUHA\Core;

use RAUHA\Managers\Players;
use RAUHA\Helpers\Utils;
use RAUHA\Core\Globals;

class Notifications
{
  public static function newTurn($turn)
  {
    $data = [
      'turn' => $turn,
    ];
    $msg = clienttranslate('New turn : Avatars move to next area');
    self::notifyAll('newTurn', $msg, $data);
  }

  public static function chooseBiome($currentPlayer, $biomeId)
  {
    self::notify($currentPlayer, 'chooseBiome', '', [
      'biomeId' => $biomeId,
    ]);
  }

  public static function confirmChoices($turn)
  {
    self::notifyAll('confirmChoices', clienttranslate('All the players made their choice of biome for round ${turn}'), [
      'turn' => $turn,
    ]);
  }

  public static function discard($currentPlayer, $discardCount)
  {
    $data = [
      'player' => $currentPlayer,
      'biomeInDiscard' => $discardCount,
    ];
    $msg = clienttranslate('${player_name} discards their Biome to receive 4 crystals');
    self::notifyAll('discard', $msg, $data);
  }

  public static function placeBiome($player, $x, $y, $biome)
  {
    $data = [
      'player' => $player,
      'x' => $x,
      'y' => $y,
      'biomeId' => $biome->getId(),
      'biomeTypes' => join($biome->getType(), ', '),
    ];
    $msg = clienttranslate('${player_name} plays ${biomeTypes} on their board on place ${x}, ${y}');
    self::notifyAll('placeBiome', $msg, $data);
  }

  public static function newAlignment($player, $god, $type)
  {
    $data = [
      'player' => $player,
      'godId' => $god->getId(),
      'godName' => $god->getName(),
      'type' => $type,
    ];
    $msg = clienttranslate('By aligning 3 Biomes with ${type}, ${player_name} receives ${godName}');
    self::notifyAll('nexAlignment', $msg, $data);
  }

  public static function skip($player)
  {
    $data = [
      'player' => $player,
    ];
    self::message(clienttranslate('${player_name} passes.'), $data);
  }

  public static function actCount($player, $message, $biome, $cost, $crystalIncome, $pointIncome)
  {
    $data = [
      'player' => $player,
      'cost' => $cost,
      'crystalIncome' => $crystalIncome,
      'pointIncome' => $pointIncome,
      'x' => $biome->getX(),
      'y' => $biome->getY(),
    ];
    self::notifyAll($player, $message, $data);
  }

  /*************************
   **** GENERIC METHODS ****
   *************************/
  protected static function notifyAll($name, $msg, $data)
  {
    self::updateArgs($data);
    Game::get()->notifyAllPlayers($name, $msg, $data);
  }

  protected static function notify($player, $name, $msg, $data)
  {
    $pId = is_int($player) ? $player : $player->getId();
    self::updateArgs($data);
    Game::get()->notifyPlayer($pId, $name, $msg, $data);
  }

  public static function message($txt, $args = [])
  {
    self::notifyAll('message', $txt, $args);
  }

  public static function messageTo($player, $txt, $args = [])
  {
    $pId = is_int($player) ? $player : $player->getId();
    self::notify($pId, 'message', $txt, $args);
  }

  /*********************
   **** UPDATE ARGS ****
   *********************/
  /*
   * Automatically adds some standard field about player and/or card
   */
  protected static function updateArgs(&$data)
  {
    if (isset($data['player'])) {
      $data['player_name'] = $data['player']->getName();
      $data['player_id'] = $data['player']->getId();
      unset($data['player']);
    }

    if (isset($data['player2'])) {
      $data['player_name2'] = $data['player2']->getName();
      $data['player_id2'] = $data['player2']->getId();
      unset($data['player2']);
    }
  }
}
