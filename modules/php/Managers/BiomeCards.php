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
  protected static $customFields = ['x', 'y', 'player_id', 'extra_datas', 'data_id'];

  protected static function cast($row)
  {
    $data = self::getBiomes()[$row['data_id']];
    return new \RAUHA\Models\BiomeCard($row, $data);
  }

  public static function getUiData()
  {
    return [];
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
        'location' => 'deck' . $age,
      ];
    }

    // Create the initial boards
    $board = self::getInitialBoard($options[\OPTION_BOARD_SIDE]);
    foreach ($players as $pId => $info) {
      for ($y = 0; $y < 3; $y++) {
        for ($x = 0; $x < 3; $x++) {
          $biomes[] = [
            'data_id' => $board[$y][$x],
            'location' => 'inPlay',
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

      0 => $f([['desert'], [], [], 0, 1, 0, '1', 0, 0, 0]),
      1 => $f([['forest'], [], [], 0, 0, 1, '1', 0, 0, 0]),
      2 => $f([['desert'], [], [], 0, 0, 0, '1', 0, 0, 0]),
      3 => $f([['crystal'], [], [], 0, 1, 0, '1', 0, 0, 0]),
      4 => $f([['desert'], [], [], 0, 1, 0, '1', 0, 0, 0]),
      5 => $f([['mushroom'], [], [], 0, 0, 0, '1', 3, 1, 0]),
      6 => $f([['desert'], [], [], 0, 0, 1, '1', 0, 0, 0]),
      7 => $f([['moutain'], [], [], 0, 0, 0, '1', 0, 0, 1]),
      8 => $f([['desert'], [], [], 0, 0, 1, '1', 0, 0, 0]),

      10 => $f([['crystal'], [], [], 0, 2, 0, '1', 0, 0, 0]),
      11 => $f([['desert'], [], [], 0, 0, 1, '1', 0, 0, 0]),
      12 => $f([['mountain'], [], [], 0, 0, 0, '1', 0, 0, 1]),
      13 => $f([['desert'], [], [], 0, 0, 0, '1', 0, 0, 0]),
      14 => $f([['desert'], [], [], 0, 1, 0, '1', 0, 0, 0]),
      15 => $f([['desert'], [], [], 0, 0, 1, '1', 0, 0, 0]),
      16 => $f([['forest'], [], [], 0, 0, 2, '1', 0, 0, 0]),
      17 => $f([['desert'], [], [], 0, 0, 0, '1', 0, 0, 0]),
      18 => $f([['mushroom'], [], [], 0, 0, 0, '1', 3, 1, 0]),

      ///////////////////////////////
      //      _                ___
      //     / \   __ _  ___  |_ _|
      //    / _ \ / _` |/ _ \  | |
      //   / ___ \ (_| |  __/  | |
      //  /_/   \_\__, |\___| |___|
      //          |___/
      ///////////////////////////////
      100 => $f([['crystal'], [], [], 2, 4, 0, '1', 0, 0, 0]),
      101 => $f([['crystal'], [], [[0, 2], [1, 2], [2, 2]], 0, 0, 5, '1', 3, 0, 0]),
      102 => $f([['crystal'], ['flying'], [], 0, 2, 0, '1', 0, 0, 0]),
      103 => $f([['crystal'], ['flying'], [], 3, 0, 1, 'flying', 0, 0, 0]),
      104 => $f([['crystal'], ['marine'], [], 1, 0, 4, '1', 2, 0, 0]),
      105 => $f([['crystal'], ['marine'], [], 0, 2, 0, '1', 0, 0, 0]),
      106 => $f([['crystal'], ['walking'], [[0, 0], [1, 0], [2, 0]], 0, 3, 0, '1', 0, 0, 0]),
      107 => $f([['crystal'], ['walking'], [], 0, 2, 0, '1', 0, 0, 0]),
      108 => $f([['forest'], ['walking'], [], 0, 0, 1, 'flying', 0, 0, 0]),
      109 => $f([['forest'], ['marine'], [], 2, 0, 1, 'marine', 0, 0, 0]),
      110 => $f([['forest'], ['flying'], [[0, 0], [2, 1], [0, 2]], 0, 0, 1, 'flying', 0, 0, 0]),
      111 => $f([['forest'], [], [[2, 0], [0, 1], [2, 2]], 0, 3, 0, '1', 0, 0, 0]),
      112 => $f([['forest'], ['marine'], [], 0, 0, 1, 'walking', 0, 0, 0]),
      113 => $f([['forest'], ['flying'], [], 0, 0, 1, 'marine', 0, 0, 0]),
      114 => $f([['forest'], [], [], 4, 0, 4, '1', 0, 0, 0]),
      115 => $f([['forest'], ['walking'], [], 2, 0, 1, 'walking', 0, 0, 0]),
      116 => $f([['mushroom'], [], [], 4, 0, 4, '1', 0, 0, 0]),
      117 => $f([['mushroom'], [], [], 0, 0, 0, '1', 2, 1, 0]),
      118 => $f([['mushroom'], ['flying'], [], 2, 0, 0, '1', 2, 1, 0]),
      119 => $f([['mushroom'], ['flying'], [], 0, 0, 0, '1', 3, 1, 0]),
      120 => $f([['mushroom'], ['marine'], [[0, 0], [0, 2], [2, 1]], 0, 0, 1, 'spore', 0, 0, 0]),
      121 => $f([['mushroom'], ['marine'], [], 0, 0, 0, '1', 3, 1, 0]),
      122 => $f([['mushroom'], ['walking'], [], 3, 0, 1, 'walking', 0, 0, 0]),
      123 => $f([['mushroom'], ['walking'], [[1, 0], [0, 2], [2, 2]], 0, 3, 0, '1', 0, 0, 0]),
      124 => $f([['mountain'], [], [], 0, 0, 2, '1', 0, 0, 1]),
      125 => $f([['mountain'], [], [], 0, 0, 0, '1', 0, 0, 2]),
      126 => $f([['mountain'], ['flying'], [[0, 2], [1, 2], [2, 2]], 0, 0, 2, '1', 0, 0, 1]),
      127 => $f([['mountain'], ['flying'], [], 2, 0, 0, '1', 0, 0, 2]),
      128 => $f([['mountain'], ['marine'], [], 4, 0, 1, 'marine', 0, 0, 1]),
      129 => $f([['mountain'], ['marine'], [[0, 0], [1, 0], [2, 0]], 0, 1, 0, '1', 0, 0, 1]),
      130 => $f([['mountain'], ['walking'], [], 6, 0, 1, 'water_source', 0, 0, 1]),
      131 => $f([['mountain'], ['walking'], [], 2, 0, 0, '1', 0, 0, 2]),
      132 => $f([['mountain', ' forest'], [], [], 0, 2, 0, '1', 0, 0, 0]),
      133 => $f([['mountain', ' forest'], ['flying', ' walking'], [], 0, 0, 0, '1', 0, 0, 0]),
      134 => $f([['crystal', ' mushroom'], [], [], 0, 0, 2, '1', 0, 0, 0]),
      135 => $f([['crystal', ' mushroom'], ['flying', ' walking'], [], 0, 0, 0, '1', 0, 0, 0]),
      136 => $f([['mountain', ' mushroom'], ['flying', ' marine'], [], 0, 0, 0, '1', 0, 0, 0]),
      137 => $f([['mushroom', ' forest'], ['flying', ' marine'], [], 0, 0, 0, '1', 0, 0, 0]),
      138 => $f([['mountain', ' crystal'], ['flying', ' marine'], [], 0, 0, 0, '1', 0, 0, 0]),
      139 => $f([['forest', ' cristal'], ['marine', ' walking'], [], 0, 0, 0, '1', 0, 0, 0]),

      ////////////////////////////////////
      //      _                ___ ___
      //     / \   __ _  ___  |_ _|_ _|
      //    / _ \ / _` |/ _ \  | | | |
      //   / ___ \ (_| |  __/  | | | |
      //  /_/   \_\__, |\___| |___|___|
      //          |___/
      ////////////////////////////////////
      140 => $f([['cristal'], [], [], 0, 5, 0, '1', 0, 0, 0]),
      141 => $f([['cristal'], [], [], 5, 0, 7, '1', 2, 0, 0]),
      142 => $f([['cristal'], ['walking'], [[0, 1], [1, 1], [2, 1]], 0, 4, 0, '1', 0, 0, 0]),
      143 => $f([['cristal'], ['walking'], [], 3, 0, 6, '1', 3, 0, 0]),
      144 => $f([['cristal'], ['marine'], [[1, 0], [1, 1], [2, 1]], 0, 4, 0, '1', 0, 0, 0]),
      145 => $f([['cristal'], ['marine'], [], 0, 3, 0, '1', 0, 0, 0]),
      146 => $f([['cristal'], ['flying'], [], 6, 0, 2, 'flying', 0, 0, 0]),
      147 => $f([['cristal'], ['flying', ' flying'], [], 4, 0, 1, 'flying', 0, 0, 0]),
      148 => $f([['forest'], [], [[0, 0], [0, 1], [1, 1]], 0, 0, 5, '1', 0, 0, 0]),
      149 => $f([['forest'], ['walking', ' walking'], [[1, 1], [2, 1], [2, 2]], 0, 0, 1, 'walking', 0, 0, 0]),
      150 => $f([['forest'], ['marine', ' marine'], [], 0, 0, 1, 'flying', 0, 0, 0]),
      151 => $f([['forest'], ['flying', ' flying'], [], 0, 0, 1, 'marine', 0, 0, 0]),
      152 => $f([['forest'], ['walking', ' marine'], [], 4, 0, 2, 'flying', 0, 0, 0]),
      153 => $f([['forest'], ['walking', ' flying'], [], 4, 0, 2, 'marine', 0, 0, 0]),
      154 => $f([['forest'], ['flying', ' marine'], [], 4, 0, 2, 'walking', 0, 0, 0]),
      155 => $f([['forest'], ['walking', ' marine', ' flying'], [], 4, 0, 3, '1', 0, 0, 0]),
      156 => $f([['mushroom'], [], [], 5, 0, 2, 'spore', 0, 0, 0]),
      157 => $f([['mushroom'], [], [], 3, 0, 5, '1', 0, 0, 0]),
      158 => $f([['mushroom'], ['marine'], [], 0, 0, 2, '1', 2, 1, 0]),
      159 => $f([['mushroom'], ['marine'], [[0, 1], [0, 2], [1, 1]], 0, 0, 0, '1', 1, 1, 0]),
      160 => $f([['mushroom'], ['flying'], [], 0, 0, 0, '1', 2, 1, 0]),
      161 => $f([['mushroom'], ['flying'], [[1, 1], [2, 0], [2, 1]], 0, 0, 4, '1', 0, 0, 0]),
      162 => $f([['mushroom'], ['walking'], [], 6, 0, 2, 'walking', 0, 0, 0]),
      163 => $f([['mushroom'], ['walking', ' walking'], [], 4, 0, 1, 'walking', 0, 0, 0]),
      164 => $f([['mountain'], [], [], 6, 0, 1, 'water_source', 0, 0, 2]),
      165 => $f([['mountain'], [], [[0, 0], [1, 1], [2, 2]], 0, 0, 0, '1', 0, 0, 3]),
      166 => $f([['mountain'], ['walking'], [], 5, 0, 1, 'water_source', 0, 0, 1]),
      167 => $f([['mountain'], ['walking'], [], 0, 0, 0, '1', 0, 0, 2]),
      168 => $f([['mountain'], ['marine', ' marine'], [], 4, 0, 1, 'marine', 0, 0, 1]),
      169 => $f([['mountain'], ['marine'], [], 7, 0, 2, 'marine', 0, 0, 1]),
      170 => $f([['mountain'], ['flying'], [], 0, 0, 0, '1', 0, 0, 2]),
      171 => $f([['mountain'], ['flying'], [[2, 0], [1, 1], [0, 2]], 0, 0, 3, '1', 0, 0, 1]),
      172 => $f([['forest', ' crystal'], [], [[0, 0], [2, 0], [0, 2], [2, 2]], 0, 0, 4, '1', 0, 0, 0]),
      173 => $f([['forest', ' crystal'], ['walking', ' marine'], [], 2, 0, 2, '1', 0, 0, 0]),
      174 => $f([['mountain', ' mushroom'], ['walking', ' marine'], [], 2, 0, 2, '1', 0, 0, 0]),
      175 => $f([
        ['mountain', ' mushroom'], ['walking', ' marine', ' flying'],
        [[1, 0], [0, 1], [2, 1], [1, 2]], 0, 0, 0, '1', 0, 0, 0
      ]),
      176 => $f([['forest', ' mushroom'], ['flying', ' marine'], [], 2, 0, 2, '1', 0, 0, 0]),
      177 => $f([['crystal', ' mushroom'], ['walking', ' flying'], [], 2, 0, 2, '1', 0, 0, 0]),
      178 => $f([['forest', ' mountain'], ['walking', ' flying'], [], 2, 0, 2, '1', 0, 0, 0]),
      179 => $f([['mountain', ' crystal'], ['flying', ' marine'], [], 2, 0, 2, '1', 0, 0, 0]),
    ];
  }
}
