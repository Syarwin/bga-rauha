<?php

namespace RAUHA\Core;

use RAUHA\Managers\Players;
use RAUHA\Helpers\Utils;
use RAUHA\Core\Globals;

class Notifications
{
  public static function newTurn($step)
  {
    $round = (int) (($step - 1) / 4 + 1);
    $turn = $step % 4;
    $data = [
      'step' => $step,
      'turn' => $turn,
      'round' => $round,
    ];

    if ($turn == 0) {
      $msg = clienttranslate('Round ${round}: scoring phase');
      self::notifyAll('newTurnScoring', $msg, $data);
    } else {
      $msgs = [
        1 => clienttranslate('Round ${round}: first turn'),
        2 => clienttranslate('Round ${round}: second turn'),
        3 => clienttranslate('Round ${round}: third turn'),
      ];
      self::notifyAll('newTurn', $msgs[$turn], $data);
    }
  }

  public static function updateFirstPlayer($pId)
  {
    self::notifyAll('updateFirstPlayer', '', [
      'pId' => $pId,
    ]);
  }

  public static function chooseBiome($currentPlayer, $biomeId)
  {
    self::notify($currentPlayer, 'chooseBiome', '', [
      'biomeId' => $biomeId,
    ]);
  }

  public static function confirmChoices($turn, $isMoon)
  {
    self::notifyAll('confirmChoices', clienttranslate('All the players made their choice of biome for round ${turn}'), [
      'turn' => $turn,
      'isMoon' => $isMoon,
    ]);
  }

  public static function discardBiomeCrystals($currentPlayer, $discardCount)
  {
    $data = [
      'player' => $currentPlayer,
      'biomesInDiscard' => $discardCount,
    ];
    $msg = clienttranslate('${player_name} discards their Biome to receive 4 crystals');
    self::notifyAll('discardBiomeCrystals', $msg, $data);
  }

  public static function discardBiomeSpore($currentPlayer, $discardCount, $x, $y)
  {
    $data = [
      'player' => $currentPlayer,
      'biomeInDiscard' => $discardCount,
    ];
    self::addDataCoord($data, $x, $y);
    $msg = clienttranslate(
      '${player_name} discards their Biome and place a new spore on their board at position (${displayX}, ${displayY})'
    );
    self::notifyAll('discardBiomeSpore', $msg, $data);
  }

  //TODO REMOVE
  public static function placeSpore($player, $x, $y, $silent = false)
  {
    $data = [
      'player' => $player,
    ];
    self::addDataCoord($data, $x, $y);
    $msg = $silent
      ? ''
      : clienttranslate('${player_name} puts a new spore on their board at position (${displayX}, ${displayY})');
    self::notifyAll('placeSpore', $msg, $data);
  }

  public static function placeBiome($player, $x, $y, $biome, $cost)
  {
    $types = [
      MOUNTAIN => clienttranslate('mountain'),
      CRYSTAL => clienttranslate('crystal'),
      FOREST => clienttranslate('forest'),
      MUSHROOM => clienttranslate('mushroom'),
    ];
    $biomeTypes = ['log' => [], 'args' => []];
    foreach ($biome->getTypes() as $i => $type) {
      $key = 'biome_type' . $i;
      $biomeTypes['log'][] = '${' . $key . '}';
      $biomeTypes['args']['i18n'][] = $key;
      $biomeTypes['args'][$key] = $types[$type];
    }
    $biomeTypes['log'] = join('/', $biomeTypes['log']);

    $data = [
      'player' => $player,
      'cost' => $cost,
      'biome' => $biome,
      'biomeTypes' => $biomeTypes,
      'waterSourceCount' => $player->getWaterSource(),
    ];
    self::addDataCoord($data, $x, $y);

    $msg =
      $cost == 0
        ? clienttranslate(
          '${player_name} plays a ${biomeTypes} biome on their board at position (${displayX}, ${displayY})'
        )
        : clienttranslate(
          '${player_name} pays ${cost} crystal(s) to play a ${biomeTypes} biome on their board at position (${displayX}, ${displayY})'
        );
    self::notifyAll('placeBiome', $msg, $data);
  }

  public static function newAlignment($player, $god, $type, $playerLoosingGod)
  {
    $types = [
      MOUNTAIN => clienttranslate('mountain'),
      CRYSTAL => clienttranslate('crystal'),
      FOREST => clienttranslate('forest'),
      MUSHROOM => clienttranslate('mushroom'),
      FLYING => clienttranslate('flying animals'),
      MARINE => clienttranslate('marine animal'),
      WALKING => clienttranslate('terrestrial animal'),
    ];

    $data = [
      'i18n' => ['godName', 'type'],
      'player' => $player,
      'godId' => $god->getId(),
      'godName' => $god->getName(),
      'type' => $types[$type],
      'waterSourceCount' => $player->getWaterSource(),
      'playerIdLoosingGod' => is_null($playerLoosingGod) ? null : $playerLoosingGod->getId(),
      'waterSourceCountPlayerLoosingGod' => is_null($playerLoosingGod) ? 0 : $playerLoosingGod->getWaterSource(),
    ];
    $msg = clienttranslate('By aligning 3 Biomes with ${type}, ${player_name} receives ${godName}');
    self::notifyAll('newAlignment', $msg, $data);
  }

  public static function skip($player, $silent = false)
  {
    $data = [
      'player' => $player,
    ];
    self::notifyAll('endActivation', $silent ? '' : clienttranslate('${player_name} passes.'), $data);
  }

  public static function activateBiome($player, $biome, $cost, $crystalIncome, $pointIncome, $sporeIncome, $x, $y)
  {
    $message = '';
    if ($cost > 0) {
      if ($pointIncome > 0) {
        $message = clienttranslate(
          'By paying ${cost} crystal(s), ${player_name} activate their Biome at position (${displayX}, ${displayY}) and receives ${pointIncome} point(s)'
        );
      } elseif ($sporeIncome > 0) {
        $message = clienttranslate(
          'By paying ${cost} crystal(s), ${player_name} activate their Biome at position (${displayX}, ${displayY}) and places a new spore at (${displaySporeX}, ${displaySporeY})'
        );
      }
    } elseif ($crystalIncome > 0) {
      $message = clienttranslate(
        '${player_name} activate their Biome at position (${displayX}, ${displayY}) and receives ${crystalIncome} crystal(s)'
      );
    } elseif ($pointIncome > 0) {
      $message = clienttranslate(
        '${player_name} activate their Biome at position (${displayX}, ${displayY}) and receives ${pointIncome} point(s)'
      );
    }

    $data = [
      'player' => $player,
      'biomeId' => $biome->getId(),
      'cost' => $cost,
      'crystalIncome' => $crystalIncome,
      'pointIncome' => $pointIncome,
      'sporeIncome' => $sporeIncome,
      'sporeX' => $x,
      'sporeY' => $y,
      'displaySporeX' => is_null($x) ? $x : $x + 1,
      'displaySporeY' => is_null($y) ? $y : $y + 1,
    ];

    self::addDataCoord($data, $biome->getX(), $biome->getY());
    self::notifyAll('activateBiome', $message, $data);
  }

  public static function activateGod($player, $god, $cost, $crystalIncome, $pointIncome)
  {
    $message = '';
    if ($cost > 0) {
      $message = clienttranslate(
        'By paying ${cost} crystal(s), ${player_name} activate ${godName} and receives ${pointIncome} point(s)'
      );
    } elseif ($crystalIncome > 0) {
      $message = clienttranslate('${player_name} activate ${godName} and receives ${crystalIncome} crystal(s)');
    } elseif ($pointIncome > 0) {
      $message = clienttranslate('${player_name} activate ${godName} and receives ${pointIncome} point(s)');
    }

    $data = [
      'player' => $player,
      'godId' => $god->getId(),
      'godName' => $god->getName(),
      'cost' => $cost,
      'crystalIncome' => $crystalIncome,
      'pointIncome' => $pointIncome,
    ];
    self::notifyAll('activateGod', $message, $data);
  }

  public static function waterSourceCount($player, $waterSourceDelta, $points)
  {
    $data = [
      'player' => $player,
      'points' => $points,
      'waterSource' => $player->getWaterSource(),
      'waterSourceDelta' => $waterSourceDelta,
    ];
    $message = clienttranslate(
      'With ${waterSource} water source(s) (${waterSourceDelta} more than the minimum), ${player_name} receives ${points} points.'
    );

    self::notifyAll('waterSourceCount', $message, $data);
  }

  public static function refreshBiomes()
  {
    self::notifyAll('refreshBiomes', '', []);
  }

  public static function refreshGods()
  {
    self::notifyAll('refreshGods', '', []);
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

  private static function addDataCoord(&$data, $x, $y)
  {
    $data['x'] = $x;
    $data['y'] = $y;
    $data['displayX'] = $x + 1;
    $data['displayY'] = $y + 1;
  }

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
