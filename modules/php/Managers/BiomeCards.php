<?php

namespace RAUHA\Managers;

use RAUHA\Helpers\Utils;
use RAUHA\Helpers\Collection;
use RAUHA\Core\Notifications;

/* Class to manage all the biome cards for Rauha */

class BiomeCards extends \RAUHA\Helpers\Pieces
{
  protected static $table = 'biomes';
  protected static $prefix = 'biome_';
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;
  protected static $customFields = ['x', 'y', 'used', 'player_id', 'extra_datas', 'data_id'];

  protected static function cast($row)
  {
    $data = self::getBiomes()[$row['data_id']];
    return new \RAUHA\Models\BiomeCard($row, $data);
  }

  public static function getUiData()
  {
    return [];
  }

  /**
   * Check if with the new Biome played, one or several alignements have been created,
   * returns $alignedType (array)
   */
  public static function checkAlignment($player, $x, $y, $biome)
  {
    $alignedTypes = [];
    //check column
    $biome1 = self::getBiomeOnPlayerBoard($player, $x, 0);
    $biome2 = self::getBiomeOnPlayerBoard($player, $x, 1);
    $biome3 = self::getBiomeOnPlayerBoard($player, $x, 2);

    foreach ($biome->getTypes() as $type) {
      if (
        in_array($type, $biome1->getTypes()) &&
        in_array($type, $biome2->getTypes()) &&
        in_array($type, $biome3->getTypes())
      ) {
        $alignedTypes[] = $type;
      }
    }
    foreach ($biome->getAnimals() as $type) {
      if (
        in_array($type, $biome1->getAnimals()) &&
        in_array($type, $biome2->getAnimals()) &&
        in_array($type, $biome3->getAnimals())
      ) {
        $alignedTypes[] = $type;
      }
    }

    //check row
    $biome1 = self::getBiomeOnPlayerBoard($player, 0, $y);
    $biome2 = self::getBiomeOnPlayerBoard($player, 1, $y);
    $biome3 = self::getBiomeOnPlayerBoard($player, 2, $y);

    foreach ($biome->getTypes() as $type) {
      if (
        in_array($type, $biome1->getTypes()) &&
        in_array($type, $biome2->getTypes()) &&
        in_array($type, $biome3->getTypes())
      ) {
        $alignedTypes[] = $type;
      }
    }
    foreach ($biome->getAnimals() as $type) {
      if (
        in_array($type, $biome1->getAnimals()) &&
        in_array($type, $biome2->getAnimals()) &&
        in_array($type, $biome3->getAnimals())
      ) {
        $alignedTypes[] = $type;
      }
    }

    return array_unique($alignedTypes);
  }

  /**
   * Retuns activable Places in the row or column of the avatar if a Turn has been furnished,
   * all activable Biomes with spore in other cases
   */
  public static function getActivablePlaces($player, $turn = null)
  {
    if ($turn != null) {
      $activableBiomes = [];

      foreach (BOARD_ACTIVATION[$turn] as $place) {
        $x = $place[0];
        $y = $place[1];
        if (BiomeCards::getBiomeOnPlayerBoard($player, $x, $y)->isActivable()) {
          $activableBiomes[] = $place;
        }
      }
      return $activableBiomes;
    }
    //TODO if $turn=null
  }

  public static function activate($biomeId)
  {
    $message = '';
    $biome = self::get($biomeId);
    $playerId = $biome->getPId();
    $player = Players::get($playerId);

    $multiplier = $biome->getMultiplier() == 1 ? 1 : self::countOnAPlayerBoard($player, $biome->getMultiplier());

    $cost = $biome->getUsageCost();

    if ($cost > 0) {
      $message .= clienttranslate('By paying ${cost} crystal(s), ');
    }

    $crystalIncome = $biome->getCrystalIncome() * $multiplier;
    if ($crystalIncome > 0) {
      $message .= clienttranslate(
        '${player_name} activate their Biome on place ${x}, ${y} and receives ${crystalIncome} crystal(s)'
      );
    }

    $pointIncome = $biome->getPointIncome() * $multiplier;
    if ($pointIncome > 0) {
      $message .= clienttranslate(
        '${player_name} activate their Biome on place ${x}, ${y} and receives ${pointIncome} point(s)'
      );
    }

    $player->incCrystal($crystalIncome - $cost);
    $player->movePointsToken($pointIncome);
    $biome->setUsed(USED);

    // Notifications
    Notifications::actCount($player, $message, $biome, $cost, $crystalIncome, $pointIncome);
  }

  /**
   * return the number of Biome with $criteria on the $player board
   */
  public static function countOnAPlayerBoard($player, $criteria)
  {
    switch ($criteria) {
      case 'marine':
      case 'walking':
      case 'flying':
        $board = self::getAllAnimalsOnPlayerBoard($player);
        return array_count_values($board)[$criteria];
        break;

      case 'animals':
        $board = self::getAllAnimalsOnPlayerBoard($player);
        return count($board);
        break;

      case FOREST:
      case MUSHROOM:
      case CRYSTAL:
      case DESERT:
      case MOUNTAIN:
        $board = self::getAllTypesOnPlayerBoard($player);
        echo var_dump($board);
        return array_count_values($board)[$criteria];
        break;

      case 'spore':
        return $player->countSpores();
        break;

      case 'waterSource':
        return self::countAllWaterSourceOnPlayerBoard($player);
        break;

      default:
        return -1;
    }
  }

  public static function getBiomeOnPlayerBoard($player, $x, $y)
  {
    return self::getInLocationQ('board')
      ->where('player_id', $player->getId())
      ->where('x', $x)
      ->where('y', $y)
      ->get()
      ->first();
  }

  public static function getAllBiomesOnPlayerBoard($player)
  {
    $result = [];
    for ($y = 0; $y < 3; $y++) {
      for ($x = 0; $x < 3; $x++) {
        $result[] = self::getBiomeOnPlayerBoard($player, $x, $y);
      }
    }
    return $result;
  }

  public static function getAllAnimalsOnPlayerBoard($player)
  {
    $result = [];
    foreach (self::getAllBiomesOnPlayerBoard($player) as $biome) {
      array_merge($result, $biome->getAnimals());
    }
    return $result;
  }

  public static function getAllTypesOnPlayerBoard($player)
  {
    $result = [];
    foreach (self::getAllBiomesOnPlayerBoard($player) as $biome) {
      array_merge($result, $biome->getTypes());
    }
    return $result;
  }

  public static function countAllWaterSourceOnPlayerBoard($player)
  {
    $result = 0;
    foreach (self::getAllBiomesOnPlayerBoard($player) as $biome) {
      $result += $biome->getWaterSource;
    }
    //TODO add 2 waterSources if VUORI is on player board

    return $result;
  }

  /* Creation of the biomes */
  public static function setupNewGame($players, $options)
  {
    $biomes = [];
    // Create the deck
    foreach (self::getBiomes() as $id => $biome) {
      if ($id < 100) {
        continue;
      }
      $age = $id < 140 ? 1 : 2;

      $biomes[] = [
        'data_id' => $id,
        'location' => 'deckAge' . $age,
      ];
    }

    // Create the initial boards
    $board = self::getInitialBoard($options[\OPTION_BOARD_SIDE]);
    foreach ($players as $pId => $info) {
      for ($y = 0; $y < 3; $y++) {
        for ($x = 0; $x < 3; $x++) {
          $biomes[] = [
            'data_id' => $board[$y][$x],
            'location' => 'board',
            'player_id' => $pId,
            'x' => $x,
            'y' => $y,
          ];
        }
      }
    }

    self::create($biomes);
  }

  public function getInitialBoard($face)
  {
    $boards = [
      OPTION_A_SIDE => [[0, 1, 2], [3, 4, 5], [6, 7, 8]],
      OPTION_B_SIDE => [[10, 11, 12], [13, 14, 15], [16, 17, 18]],
    ];

    return $boards[$face];
  }

  public function getBiomes()
  {
    $f = function ($t) {
      return [
        'types' => $t[0],
        'animals' => $t[1],
        'layingConstraints' => $t[2],
        'layingCost' => $t[3],
        'crystalIncome' => $t[4],
        'pointIncome' => $t[5],
        'multiplier' => $t[6],
        'usageCost' => $t[7],
        'sporeIncome' => $t[8],
        'waterSource' => $t[9],
      ];
    };

    return [
      ////////////////////////////////////////////
      //  ____  _             _   _
      // / ___|| |_ __ _ _ __| |_(_)_ __   __ _
      // \___ \| __/ _` | '__| __| | '_ \ / _` |
      //  ___) | || (_| | |  | |_| | | | | (_| |
      // |____/ \__\__,_|_|   \__|_|_| |_|\__, |
      //                                  |___/
      ////////////////////////////////////////////

      0 => $f([[DESERT], [], [], 0, 1, 0, '1', 0, 0, 0]),
      1 => $f([[FOREST], [], [], 0, 0, 1, '1', 0, 0, 0]),
      2 => $f([[DESERT], [], [], 0, 0, 0, '1', 0, 0, 0]),
      3 => $f([[CRYSTAL], [], [], 0, 1, 0, '1', 0, 0, 0]),
      4 => $f([[DESERT], [], [], 0, 1, 0, '1', 0, 0, 0]),
      5 => $f([[MUSHROOM], [], [], 0, 0, 0, '1', 3, 1, 0]),
      6 => $f([[DESERT], [], [], 0, 0, 1, '1', 0, 0, 0]),
      7 => $f([[MOUNTAIN], [], [], 0, 0, 0, '1', 0, 0, 1]),
      8 => $f([[DESERT], [], [], 0, 0, 1, '1', 0, 0, 0]),

      10 => $f([[CRYSTAL], [], [], 0, 2, 0, '1', 0, 0, 0]),
      11 => $f([[DESERT], [], [], 0, 0, 1, '1', 0, 0, 0]),
      12 => $f([[MOUNTAIN], [], [], 0, 0, 0, '1', 0, 0, 1]),
      13 => $f([[DESERT], [], [], 0, 0, 0, '1', 0, 0, 0]),
      14 => $f([[DESERT], [], [], 0, 1, 0, '1', 0, 0, 0]),
      15 => $f([[DESERT], [], [], 0, 0, 1, '1', 0, 0, 0]),
      16 => $f([[FOREST], [], [], 0, 0, 2, '1', 0, 0, 0]),
      17 => $f([[DESERT], [], [], 0, 0, 0, '1', 0, 0, 0]),
      18 => $f([[MUSHROOM], [], [], 0, 0, 0, '1', 3, 1, 0]),

      ///////////////////////////////
      //      _                ___
      //     / \   __ _  ___  |_ _|
      //    / _ \ / _` |/ _ \  | |
      //   / ___ \ (_| |  __/  | |
      //  /_/   \_\__, |\___| |___|
      //          |___/
      ///////////////////////////////
      100 => $f([[CRYSTAL], [], [], 2, 4, 0, '1', 0, 0, 0]),
      101 => $f([[CRYSTAL], [], [[0, 2], [1, 2], [2, 2]], 0, 0, 5, '1', 3, 0, 0]),
      102 => $f([[CRYSTAL], ['flying'], [], 0, 2, 0, '1', 0, 0, 0]),
      103 => $f([[CRYSTAL], ['flying'], [], 3, 0, 1, 'flying', 0, 0, 0]),
      104 => $f([[CRYSTAL], ['marine'], [], 1, 0, 4, '1', 2, 0, 0]),
      105 => $f([[CRYSTAL], ['marine'], [], 0, 2, 0, '1', 0, 0, 0]),
      106 => $f([[CRYSTAL], ['walking'], [[0, 0], [1, 0], [2, 0]], 0, 3, 0, '1', 0, 0, 0]),
      107 => $f([[CRYSTAL], ['walking'], [], 0, 2, 0, '1', 0, 0, 0]),
      108 => $f([[FOREST], ['walking'], [], 0, 0, 1, 'flying', 0, 0, 0]),
      109 => $f([[FOREST], ['marine'], [], 2, 0, 1, 'marine', 0, 0, 0]),
      110 => $f([[FOREST], ['flying'], [[0, 0], [2, 1], [0, 2]], 0, 0, 1, 'flying', 0, 0, 0]),
      111 => $f([[FOREST], [], [[2, 0], [0, 1], [2, 2]], 0, 3, 0, '1', 0, 0, 0]),
      112 => $f([[FOREST], ['marine'], [], 0, 0, 1, 'walking', 0, 0, 0]),
      113 => $f([[FOREST], ['flying'], [], 0, 0, 1, 'marine', 0, 0, 0]),
      114 => $f([[FOREST], [], [], 4, 0, 4, '1', 0, 0, 0]),
      115 => $f([[FOREST], ['walking'], [], 2, 0, 1, 'walking', 0, 0, 0]),
      116 => $f([[MUSHROOM], [], [], 4, 0, 4, '1', 0, 0, 0]),
      117 => $f([[MUSHROOM], [], [], 0, 0, 0, '1', 2, 1, 0]),
      118 => $f([[MUSHROOM], ['flying'], [], 2, 0, 0, '1', 2, 1, 0]),
      119 => $f([[MUSHROOM], ['flying'], [], 0, 0, 0, '1', 3, 1, 0]),
      120 => $f([[MUSHROOM], ['marine'], [[0, 0], [0, 2], [2, 1]], 0, 0, 1, 'spore', 0, 0, 0]),
      121 => $f([[MUSHROOM], ['marine'], [], 0, 0, 0, '1', 3, 1, 0]),
      122 => $f([[MUSHROOM], ['walking'], [], 3, 0, 1, 'walking', 0, 0, 0]),
      123 => $f([[MUSHROOM], ['walking'], [[1, 0], [0, 2], [2, 2]], 0, 3, 0, '1', 0, 0, 0]),
      124 => $f([[MOUNTAIN], [], [], 0, 0, 2, '1', 0, 0, 1]),
      125 => $f([[MOUNTAIN], [], [], 0, 0, 0, '1', 0, 0, 2]),
      126 => $f([[MOUNTAIN], ['flying'], [[0, 2], [1, 2], [2, 2]], 0, 0, 2, '1', 0, 0, 1]),
      127 => $f([[MOUNTAIN], ['flying'], [], 2, 0, 0, '1', 0, 0, 2]),
      128 => $f([[MOUNTAIN], ['marine'], [], 4, 0, 1, 'marine', 0, 0, 1]),
      129 => $f([[MOUNTAIN], ['marine'], [[0, 0], [1, 0], [2, 0]], 0, 1, 0, '1', 0, 0, 1]),
      130 => $f([[MOUNTAIN], ['walking'], [], 6, 0, 1, 'waterSource', 0, 0, 1]),
      131 => $f([[MOUNTAIN], ['walking'], [], 2, 0, 0, '1', 0, 0, 2]),
      132 => $f([[MOUNTAIN, FOREST], [], [], 0, 2, 0, '1', 0, 0, 0]),
      133 => $f([[MOUNTAIN, FOREST], ['flying', ' walking'], [], 0, 0, 0, '1', 0, 0, 0]),
      134 => $f([[CRYSTAL, MUSHROOM], [], [], 0, 0, 2, '1', 0, 0, 0]),
      135 => $f([[CRYSTAL, MUSHROOM], ['flying', ' walking'], [], 0, 0, 0, '1', 0, 0, 0]),
      136 => $f([[MOUNTAIN, MUSHROOM], ['flying', ' marine'], [], 0, 0, 0, '1', 0, 0, 0]),
      137 => $f([[MUSHROOM, FOREST], ['flying', ' marine'], [], 0, 0, 0, '1', 0, 0, 0]),
      138 => $f([[MOUNTAIN, CRYSTAL], ['flying', ' marine'], [], 0, 0, 0, '1', 0, 0, 0]),
      139 => $f([[FOREST, CRYSTAL], ['marine', ' walking'], [], 0, 0, 0, '1', 0, 0, 0]),

      ////////////////////////////////////
      //      _                ___ ___
      //     / \   __ _  ___  |_ _|_ _|
      //    / _ \ / _` |/ _ \  | | | |
      //   / ___ \ (_| |  __/  | | | |
      //  /_/   \_\__, |\___| |___|___|
      //          |___/
      ////////////////////////////////////
      140 => $f([[CRYSTAL], [], [], 0, 5, 0, '1', 0, 0, 0]),
      141 => $f([[CRYSTAL], [], [], 5, 0, 7, '1', 2, 0, 0]),
      142 => $f([[CRYSTAL], ['walking'], [[0, 1], [1, 1], [2, 1]], 0, 4, 0, '1', 0, 0, 0]),
      143 => $f([[CRYSTAL], ['walking'], [], 3, 0, 6, '1', 3, 0, 0]),
      144 => $f([[CRYSTAL], ['marine'], [[1, 0], [1, 1], [2, 1]], 0, 4, 0, '1', 0, 0, 0]),
      145 => $f([[CRYSTAL], ['marine'], [], 0, 3, 0, '1', 0, 0, 0]),
      146 => $f([[CRYSTAL], ['flying'], [], 6, 0, 2, 'flying', 0, 0, 0]),
      147 => $f([[CRYSTAL], ['flying', ' flying'], [], 4, 0, 1, 'flying', 0, 0, 0]),
      148 => $f([[FOREST], [], [[0, 0], [0, 1], [1, 1]], 0, 0, 5, '1', 0, 0, 0]),
      149 => $f([[FOREST], ['walking', ' walking'], [[1, 1], [2, 1], [2, 2]], 0, 0, 1, 'walking', 0, 0, 0]),
      150 => $f([[FOREST], ['marine', ' marine'], [], 0, 0, 1, 'flying', 0, 0, 0]),
      151 => $f([[FOREST], ['flying', ' flying'], [], 0, 0, 1, 'marine', 0, 0, 0]),
      152 => $f([[FOREST], ['walking', ' marine'], [], 4, 0, 2, 'flying', 0, 0, 0]),
      153 => $f([[FOREST], ['walking', ' flying'], [], 4, 0, 2, 'marine', 0, 0, 0]),
      154 => $f([[FOREST], ['flying', ' marine'], [], 4, 0, 2, 'walking', 0, 0, 0]),
      155 => $f([[FOREST], ['walking', ' marine', ' flying'], [], 4, 0, 3, '1', 0, 0, 0]),
      156 => $f([[MUSHROOM], [], [], 5, 0, 2, 'spore', 0, 0, 0]),
      157 => $f([[MUSHROOM], [], [], 3, 0, 5, '1', 0, 0, 0]),
      158 => $f([[MUSHROOM], ['marine'], [], 0, 0, 2, '1', 2, 1, 0]),
      159 => $f([[MUSHROOM], ['marine'], [[0, 1], [0, 2], [1, 1]], 0, 0, 0, '1', 1, 1, 0]),
      160 => $f([[MUSHROOM], ['flying'], [], 0, 0, 0, '1', 2, 1, 0]),
      161 => $f([[MUSHROOM], ['flying'], [[1, 1], [2, 0], [2, 1]], 0, 0, 4, '1', 0, 0, 0]),
      162 => $f([[MUSHROOM], ['walking'], [], 6, 0, 2, 'walking', 0, 0, 0]),
      163 => $f([[MUSHROOM], ['walking', ' walking'], [], 4, 0, 1, 'walking', 0, 0, 0]),
      164 => $f([[MOUNTAIN], [], [], 6, 0, 1, 'waterSource', 0, 0, 2]),
      165 => $f([[MOUNTAIN], [], [[0, 0], [1, 1], [2, 2]], 0, 0, 0, '1', 0, 0, 3]),
      166 => $f([[MOUNTAIN], ['walking'], [], 5, 0, 1, 'waterSource', 0, 0, 1]),
      167 => $f([[MOUNTAIN], ['walking'], [], 0, 0, 0, '1', 0, 0, 2]),
      168 => $f([[MOUNTAIN], ['marine', ' marine'], [], 4, 0, 1, 'marine', 0, 0, 1]),
      169 => $f([[MOUNTAIN], ['marine'], [], 7, 0, 2, 'marine', 0, 0, 1]),
      170 => $f([[MOUNTAIN], ['flying'], [], 0, 0, 0, '1', 0, 0, 2]),
      171 => $f([[MOUNTAIN], ['flying'], [[2, 0], [1, 1], [0, 2]], 0, 0, 3, '1', 0, 0, 1]),
      172 => $f([[FOREST, CRYSTAL], [], [[0, 0], [2, 0], [0, 2], [2, 2]], 0, 0, 4, '1', 0, 0, 0]),
      173 => $f([[FOREST, CRYSTAL], ['walking', ' marine'], [], 2, 0, 2, '1', 0, 0, 0]),
      174 => $f([[MOUNTAIN, MUSHROOM], ['walking', ' marine'], [], 2, 0, 2, '1', 0, 0, 0]),
      175 => $f([
        [MOUNTAIN, MUSHROOM],
        ['walking', ' marine', ' flying'],
        [[1, 0], [0, 1], [2, 1], [1, 2]],
        0,
        0,
        0,
        '1',
        0,
        0,
        0,
      ]),
      176 => $f([[FOREST, MUSHROOM], ['flying', ' marine'], [], 2, 0, 2, '1', 0, 0, 0]),
      177 => $f([[CRYSTAL, MUSHROOM], ['walking', ' flying'], [], 2, 0, 2, '1', 0, 0, 0]),
      178 => $f([[FOREST, MOUNTAIN], ['walking', ' flying'], [], 2, 0, 2, '1', 0, 0, 0]),
      179 => $f([[MOUNTAIN, CRYSTAL], ['flying', ' marine'], [], 2, 0, 2, '1', 0, 0, 0]),
    ];
  }
}
