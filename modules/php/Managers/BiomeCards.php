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

    foreach ($biome->getElements() as $type) {
      if (
        in_array($type, $biome1->getTypes()) &&
        in_array($type, $biome2->getTypes()) &&
        in_array($type, $biome3->getTypes())
      ) {
        $alignedTypes[] = $type;
      }
    }

    //check row
    $biome1 = self::getBiomeOnPlayerBoard($player, 0, $y);
    $biome2 = self::getBiomeOnPlayerBoard($player, 1, $y);
    $biome3 = self::getBiomeOnPlayerBoard($player, 2, $y);

    foreach ($biome->getElements() as $type) {
      if (
        in_array($type, $biome1->getTypes()) &&
        in_array($type, $biome2->getTypes()) &&
        in_array($type, $biome3->getTypes())
      ) {
        $alignedTypes[] = $type;
      }
    }

    return array_unique($alignedTypes);
  }

  /**
   * Retuns activable Biomes in the row or column of the avatar if a Turn has been furnished,
   * all activable Biomes with spore in other cases
   */
  public static function getActivableBiomes($player, $turn = null)
  {
    //if a turn has been given, possibleplaces are limited to board activation of the turn
    //if no turn has been given, possibles places depends on spores places
    $possiblePlaces = $turn == null ? $player->getSporesPlaces(true) : BOARD_ACTIVATION[$turn];

    $activableBiomes = [];

    foreach ($possiblePlaces as $place) {
      $x = $place[0];
      $y = $place[1];
      $biome = BiomeCards::getBiomeOnPlayerBoard($player, $x, $y);
      if ($biome->isActivable()) {
        $activableBiomes[] = $biome;
      }
    }
    return $activableBiomes;
  }

  public static function activate($biomeId)
  {
    $message = '';
    $biome = self::get($biomeId);
    $playerId = $biome->getPId();
    $player = Players::get($playerId);

    $multiplier = $biome->getMultiplier() == 1 ? 1 : self::countOnAPlayerBoard($player, $biome->getMultiplier());

    $cost = $biome->getUsageCost();
    $crystalIncome = $biome->getCrystalIncome() * $multiplier;
    $pointIncome = $biome->getPointIncome() * $multiplier;
    $sporeIncome = $biome->getSporeIncome();

    $player->incCrystal($crystalIncome - $cost);
    $player->movePointsToken($pointIncome);
    $biome->setUsed(USED);

    // Notifications
    Notifications::activateBiome($player, $biome, $cost, $crystalIncome, $pointIncome, $sporeIncome);

    return ($sporeIncome > 0);
  }

  /**
   * return the number of Biome with $criteria on the $player board
   */
  public static function countOnAPlayerBoard($player, $criteria)
  {
    switch ($criteria) {
      case MARINE:
      case WALKING:
      case FLYING:
        $board = self::getAllAnimalsOnPlayerBoard($player);
        return array_count_values($board)[$criteria];
        break;

      case ANIMALS:
        $board = self::getAllAnimalsOnPlayerBoard($player);
        return max(array_count_values($board));
        break;

      case FOREST:
      case MUSHROOM:
      case CRYSTAL:
      case DESERT:
      case MOUNTAIN:
        $board = self::getAllTypesOnPlayerBoard($player);
        return array_count_values($board)[$criteria];
        break;

      case 'spore':
        return $player->countSpores();
        break;

      case WATER_SOURCE:
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
      $result = array_merge($result, $biome->getAnimals());
    }
    return $result;
  }

  public static function getAllTypesOnPlayerBoard($player)
  {
    $result = [];
    foreach (self::getAllBiomesOnPlayerBoard($player) as $biome) {
      $result = array_merge($result, $biome->getTypes());
    }
    return $result;
  }

  public static function countAllWaterSourceOnPlayerBoard($player)
  {
    $result = 0;
    foreach (self::getAllBiomesOnPlayerBoard($player) as $biome) {
      $result += $biome->getWaterSource();
    }

    return $result;
  }

  public static function refreshAll()
  {
    self::DB()->update(['used' => NOT_USED]);
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
      102 => $f([[CRYSTAL], [FLYING], [], 0, 2, 0, '1', 0, 0, 0]),
      103 => $f([[CRYSTAL], [FLYING], [], 3, 0, 1, FLYING, 0, 0, 0]),
      104 => $f([[CRYSTAL], [MARINE], [], 1, 0, 4, '1', 2, 0, 0]),
      105 => $f([[CRYSTAL], [MARINE], [], 0, 2, 0, '1', 0, 0, 0]),
      106 => $f([[CRYSTAL], [WALKING], [[0, 0], [1, 0], [2, 0]], 0, 3, 0, '1', 0, 0, 0]),
      107 => $f([[CRYSTAL], [WALKING], [], 0, 2, 0, '1', 0, 0, 0]),
      108 => $f([[FOREST], [WALKING], [], 0, 0, 1, FLYING, 0, 0, 0]),
      109 => $f([[FOREST], [MARINE], [], 2, 0, 1, MARINE, 0, 0, 0]),
      110 => $f([[FOREST], [FLYING], [[0, 0], [2, 1], [0, 2]], 0, 0, 1, FLYING, 0, 0, 0]),
      111 => $f([[FOREST], [], [[2, 0], [0, 1], [2, 2]], 0, 3, 0, '1', 0, 0, 0]),
      112 => $f([[FOREST], [MARINE], [], 0, 0, 1, WALKING, 0, 0, 0]),
      113 => $f([[FOREST], [FLYING], [], 0, 0, 1, MARINE, 0, 0, 0]),
      114 => $f([[FOREST], [], [], 4, 0, 4, '1', 0, 0, 0]),
      115 => $f([[FOREST], [WALKING], [], 2, 0, 1, WALKING, 0, 0, 0]),
      116 => $f([[MUSHROOM], [], [], 4, 0, 4, '1', 0, 0, 0]),
      117 => $f([[MUSHROOM], [], [], 0, 0, 0, '1', 2, 1, 0]),
      118 => $f([[MUSHROOM], [FLYING], [], 2, 0, 0, '1', 2, 1, 0]),
      119 => $f([[MUSHROOM], [FLYING], [], 0, 0, 0, '1', 3, 1, 0]),
      120 => $f([[MUSHROOM], [MARINE], [[0, 0], [2, 0], [1, 2]], 0, 0, 1, 'spore', 0, 0, 0]),
      121 => $f([[MUSHROOM], [MARINE], [], 0, 0, 0, '1', 3, 1, 0]),
      122 => $f([[MUSHROOM], [WALKING], [], 3, 0, 1, WALKING, 0, 0, 0]),
      123 => $f([[MUSHROOM], [WALKING], [[1, 0], [0, 2], [2, 2]], 0, 3, 0, '1', 0, 0, 0]),
      124 => $f([[MOUNTAIN], [], [], 0, 0, 2, '1', 0, 0, 1]),
      125 => $f([[MOUNTAIN], [], [], 0, 0, 0, '1', 0, 0, 2]),
      126 => $f([[MOUNTAIN], [FLYING], [[2, 0], [2, 1], [2, 2]], 0, 0, 2, '1', 0, 0, 1]),
      127 => $f([[MOUNTAIN], [FLYING], [], 2, 0, 0, '1', 0, 0, 2]),
      128 => $f([[MOUNTAIN], [MARINE], [], 4, 0, 1, MARINE, 0, 0, 1]),
      129 => $f([[MOUNTAIN], [MARINE], [[0, 0], [0, 1], [0, 2]], 0, 1, 0, '1', 0, 0, 1]),
      130 => $f([[MOUNTAIN], [WALKING], [], 6, 0, 1, WATER_SOURCE, 0, 0, 1]),
      131 => $f([[MOUNTAIN], [WALKING], [], 2, 0, 0, '1', 0, 0, 2]),
      132 => $f([[MOUNTAIN, FOREST], [], [], 0, 2, 0, '1', 0, 0, 0]),
      133 => $f([[MOUNTAIN, FOREST], [FLYING, WALKING], [], 0, 0, 0, '1', 0, 0, 0]),
      134 => $f([[CRYSTAL, MUSHROOM], [], [], 0, 0, 2, '1', 0, 0, 0]),
      135 => $f([[CRYSTAL, MUSHROOM], [FLYING, WALKING], [], 0, 0, 0, '1', 0, 0, 0]),
      136 => $f([[MOUNTAIN, MUSHROOM], [FLYING, MARINE], [], 0, 0, 0, '1', 0, 0, 0]),
      137 => $f([[MUSHROOM, FOREST], [FLYING, MARINE], [], 0, 0, 0, '1', 0, 0, 0]),
      138 => $f([[MOUNTAIN, CRYSTAL], [FLYING, MARINE], [], 0, 0, 0, '1', 0, 0, 0]),
      139 => $f([[FOREST, CRYSTAL], [MARINE, WALKING], [], 0, 0, 0, '1', 0, 0, 0]),

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
      142 => $f([[CRYSTAL], [WALKING], [[0, 1], [1, 1], [2, 1]], 0, 4, 0, '1', 0, 0, 0]),
      143 => $f([[CRYSTAL], [WALKING], [], 3, 0, 6, '1', 3, 0, 0]),
      144 => $f([[CRYSTAL], [MARINE], [[1, 0], [1, 1], [1, 2]], 0, 4, 0, '1', 0, 0, 0]),
      145 => $f([[CRYSTAL], [MARINE], [], 0, 3, 0, '1', 0, 0, 0]),
      146 => $f([[CRYSTAL], [FLYING], [], 6, 0, 2, FLYING, 0, 0, 0]),
      147 => $f([[CRYSTAL], [FLYING, FLYING], [], 4, 0, 1, FLYING, 0, 0, 0]),
      148 => $f([[FOREST], [], [[0, 0], [0, 1], [1, 1]], 0, 0, 5, '1', 0, 0, 0]),
      149 => $f([[FOREST], [WALKING, WALKING], [[1, 1], [2, 1], [2, 2]], 0, 0, 1, WALKING, 0, 0, 0]),
      150 => $f([[FOREST], [MARINE, MARINE], [], 0, 0, 1, FLYING, 0, 0, 0]),
      151 => $f([[FOREST], [FLYING, FLYING], [], 0, 0, 1, MARINE, 0, 0, 0]),
      152 => $f([[FOREST], [WALKING, MARINE], [], 4, 0, 2, FLYING, 0, 0, 0]),
      153 => $f([[FOREST], [WALKING, FLYING], [], 4, 0, 2, MARINE, 0, 0, 0]),
      154 => $f([[FOREST], [FLYING, MARINE], [], 4, 0, 2, WALKING, 0, 0, 0]),
      155 => $f([[FOREST], [WALKING, MARINE, FLYING], [], 4, 0, 3, '1', 0, 0, 0]),
      156 => $f([[MUSHROOM], [], [], 5, 0, 2, 'spore', 0, 0, 0]),
      157 => $f([[MUSHROOM], [], [], 3, 0, 5, '1', 0, 0, 0]),
      158 => $f([[MUSHROOM], [MARINE], [], 0, 0, 2, '1', 2, 1, 0]),
      159 => $f([[MUSHROOM], [MARINE], [[0, 1], [0, 2], [1, 1]], 0, 0, 0, '1', 1, 1, 0]),
      160 => $f([[MUSHROOM], [FLYING], [], 0, 0, 0, '1', 2, 1, 0]),
      161 => $f([[MUSHROOM], [FLYING], [[1, 1], [0, 2], [1, 2]], 0, 0, 4, '1', 0, 0, 0]),
      162 => $f([[MUSHROOM], [WALKING], [], 6, 0, 2, WALKING, 0, 0, 0]),
      163 => $f([[MUSHROOM], [WALKING, WALKING], [], 4, 0, 1, WALKING, 0, 0, 0]),
      164 => $f([[MOUNTAIN], [], [], 6, 0, 1, WATER_SOURCE, 0, 0, 2]),
      165 => $f([[MOUNTAIN], [], [[0, 0], [1, 1], [2, 2]], 0, 0, 0, '1', 0, 0, 3]),
      166 => $f([[MOUNTAIN], [WALKING], [], 5, 0, 1, WATER_SOURCE, 0, 0, 1]),
      167 => $f([[MOUNTAIN], [WALKING], [], 0, 0, 0, '1', 0, 0, 2]),
      168 => $f([[MOUNTAIN], [MARINE, MARINE], [], 4, 0, 1, MARINE, 0, 0, 1]),
      169 => $f([[MOUNTAIN], [MARINE], [], 7, 0, 2, MARINE, 0, 0, 1]),
      170 => $f([[MOUNTAIN], [FLYING], [], 0, 0, 0, '1', 0, 0, 2]),
      171 => $f([[MOUNTAIN], [FLYING], [[2, 0], [1, 1], [0, 2]], 0, 0, 3, '1', 0, 0, 1]),
      172 => $f([[FOREST, CRYSTAL], [], [[0, 0], [2, 0], [0, 2], [2, 2]], 0, 0, 4, '1', 0, 0, 0]),
      173 => $f([[FOREST, CRYSTAL], [WALKING, MARINE], [], 2, 0, 2, '1', 0, 0, 0]),
      174 => $f([[MOUNTAIN, MUSHROOM], [WALKING, MARINE], [], 2, 0, 2, '1', 0, 0, 0]),
      175 => $f([
        [MOUNTAIN, MUSHROOM],
        [WALKING, MARINE, FLYING],
        [[1, 0], [0, 1], [2, 1], [1, 2]],
        0,
        0,
        0,
        '1',
        0,
        0,
        0,
      ]),
      176 => $f([[FOREST, MUSHROOM], [FLYING, MARINE], [], 2, 0, 2, '1', 0, 0, 0]),
      177 => $f([[CRYSTAL, MUSHROOM], [WALKING, FLYING], [], 2, 0, 2, '1', 0, 0, 0]),
      178 => $f([[FOREST, MOUNTAIN], [WALKING, FLYING], [], 2, 0, 2, '1', 0, 0, 0]),
      179 => $f([[MOUNTAIN, CRYSTAL], [FLYING, MARINE], [], 2, 0, 2, '1', 0, 0, 0]),
    ];
  }
}
