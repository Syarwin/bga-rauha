<?php
namespace ARK\Models;
use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Helpers\UserException;
use ARK\Helpers\Utils;
use ARK\Helpers\Collection;

/*
 * ZooMap: all utility functions concerning a Zoo Map
 */
const DIRECTIONS = [
  ['x' => -2, 'y' => 0],
  ['x' => -1, 'y' => -1],
  ['x' => 1, 'y' => -1],
  ['x' => 2, 'y' => 0],
  ['x' => 1, 'y' => 1],
  ['x' => -1, 'y' => 1],
];

class ZooMap
{
  // STATIC DATA
  protected $name = '';
  protected $terrains = [];
  protected $bonuses = [];
  protected $upgradeNeeded = [];

  // CONSTRUCT
  protected $player = null;
  protected $pId = null;
  public function __construct($player)
  {
    $this->player = $player;
    $this->pId = $player->getId();
    $this->fetchDatas();
  }

  public function getUiData()
  {
    return [
      'terrains' => $this->terrains,
      'upgradeNeeded' => $this->upgradeNeeded,
    ];
  }

  public function refresh()
  {
    $this->fetchDatas();
  }

  /**
   * Fetch DB for tiles and fill the grid
   */
  protected $grid = [];
  protected $buildings = [];
  protected function fetchDatas()
  {
    $this->grid = self::createGrid();
    foreach ($this->grid as $x => $col) {
      foreach ($col as $y => $cell) {
        $this->grid[$x][$y] = [
          'building' => null,
        ];
      }
    }

    $this->buildings = $this->player->getBuildings();
    foreach ($this->buildings as $building) {
      // TODO
    }
  }

  public function getFreeEnclosures($size, $requirements)
  {
    // TODO
    return new Collection([1]);
  }

  public function fillEnclosure($enclosureId)
  {
    // TODO
    // manage special enclosures
  }

  ///////////////////////////////////////////////
  //  ____        _ _     _ _
  // | __ ) _   _(_) | __| (_)_ __   __ _ ___
  // |  _ \| | | | | |/ _` | | '_ \ / _` / __|
  // | |_) | |_| | | | (_| | | | | | (_| \__ \
  // |____/ \__,_|_|_|\__,_|_|_| |_|\__, |___/
  //                                |___/
  ///////////////////////////////////////////////
  public function getBuildingAtPos($hex)
  {
    return $this->grid[$hex['x']][$hex['y']]['building'];
  }

  protected function getBuildingsNeighbourCells()
  {
    $cells = [];
    foreach (self::getListOfCells() as $cell) {
      if (!is_null($this->getBuildingAtPos($cell))) {
        $cells = array_merge($cells, $this->getNeighbours($cell));
      }
    }
    return Utils::uniqueZones($cells);
  }

  public function getPlacementOptions($buildingType)
  {
    $checkingCells = $this->buildings->empty() ? self::getBorderCells() : self::getBuildingsNeighbourCells();
    $result = [];
    // For each possible cell to place the reference hex of the building
    foreach (self::getListOfCells() as $pos) {
      $rotations = [];
      // Compute which rotations are valid
      for ($rotation = 0; $rotation < 6; $rotation++) {
        $hexes = self::getCoveredHexes($buildingType, $pos, $rotation);
        // Are all the hexes valid to build upon ?
        if ($hexes !== false) {
          // Adjacency check: either adjacent to existing buildings, or on the border otherwise
          if ($this->isIntersectionNonEmpty($hexes, $checkingCells)) {
            $rotations[] = $rotation;
          }
        }
      }
      if (!empty($rotations)) {
        $result[] = [
          'pos' => $pos,
          'rotations' => $rotations,
        ];
      }
    }
    var_dump($result);
  }

  /**
   * getCoveredHexes: given a building type, a position and rotation, return the list of hexes that would be covered by the building placed that way
   */
  public function getCoveredHexes($buildingType, $pos, $rotation)
  {
    $hexes = [];
    foreach (ENCLOSURES[$buildingType] as $delta) {
      $hexOffset = self::getRotatedHex(['x' => $delta[0], 'y' => $delta[1]], $rotation);
      $hex = [
        'x' => $pos['x'] + $hexOffset['x'],
        'y' => $pos['y'] + $hexOffset['y'],
      ];
      if (!$this->isCellAvailableToBuild($hex)) {
        return false;
      } else {
        $hexes[] = $hex;
      }
    }
    return $hexes;
  }

  /**
   * isCellAvailableToBuild: given an hex, can we build here ?
   */

  public function isCellAvailableToBuild($hex, $ignore = [])
  {
    $uid = $hex['x'] . '_' . $hex['y'];
    // Can't build on an invalid cell or already built cell
    if (!$this->isCellValid($hex) || !is_null($this->getBuildingAtPos($hex))) {
      return false;
    }
    // Can't build on water
    if (!($ignore[WATER] ?? false) && in_array($uid, $this->terrains[WATER])) {
      return false;
    }
    // Can't build on rock
    if (!($ignore[ROCK] ?? false) && in_array($uid, $this->terrains[ROCK])) {
      return false;
    }
    // Can't build on upgraded spaces
    if (
      !($ignore[UPGRADED_BUILD_CARD] ?? $this->player->isCardUpgraded(BUILD)) &&
      in_array($uid, $this->upgradeNeeded)
    ) {
      return false;
    }

    return true;
  }

  /////////////////////////////////////////////
  //   ____      _     _   _   _ _   _ _
  //  / ___|_ __(_) __| | | | | | |_(_) |___
  // | |  _| '__| |/ _` | | | | | __| | / __|
  // | |_| | |  | | (_| | | |_| | |_| | \__ \
  //  \____|_|  |_|\__,_|  \___/ \__|_|_|___/
  ////////////////////////////////////////////

  public static function createGrid($defaultValue = null)
  {
    $dim = ['x' => 9, 'y' => 7];
    $g = [];
    for ($x = 0; $x < $dim['x']; $x++) {
      $size = $dim['y'] - ($x % 2 == 0 ? 1 : 0);
      for ($y = 0; $y < $size; $y++) {
        $row = 2 * $y + ($x % 2 == 0 ? 1 : 0);
        $g[$x][$row] = $defaultValue;
      }
    }
    return $g;
  }

  public static function getListOfCells()
  {
    $grid = self::createGrid(0);
    $cells = [];
    foreach ($grid as $x => $col) {
      foreach ($col as $y => $t) {
        $cells[] = ['x' => $x, 'y' => $y];
      }
    }
    return $cells;
  }

  public static function getBorderCells()
  {
    $grid = self::createGrid(0);
    $cells = [];
    foreach ($grid as $x => $col) {
      foreach ($col as $y => $t) {
        if ($y <= 1 || $x <= 0 || $y >= 11 || $x >= 8) {
          $cells[] = ['x' => $x, 'y' => $y];
        }
      }
    }
    return $cells;
  }

  protected function isCellValid($cell)
  {
    return isset($this->grid[$cell['x']][$cell['y']]);
  }

  protected function areSameCell($cell1, $cell2)
  {
    return $cell1['x'] == $cell2['x'] && $cell1['y'] == $cell2['y'];
  }

  protected function getNeighbours($cell)
  {
    $cells = [];
    foreach (DIRECTIONS as $dir) {
      $newCell = [
        'x' => $cell['x'] + $dir['x'],
        'y' => $cell['y'] + $dir['y'],
      ];
      if ($this->isCellValid($newCell)) {
        $cells[] = $newCell;
      }
    }
    return $cells;
  }

  protected function isIntersectionNonEmpty($cells1, $cells2)
  {
    foreach ($cells1 as $cell1) {
      foreach ($cells2 as $cell2) {
        if (self::areSameCell($cell1, $cell2)) {
          return true;
        }
      }
    }
    return false;
  }

  protected function getRotatedHex($hex, $rotation)
  {
    if ($rotation == 0 || ($hex['x'] == 0 && $hex['y'] == 0)) {
      return $hex;
    }

    $q = $hex['x'];
    $r = ($hex['y'] - $hex['x']) / 2;
    $cube = [$q, $r, -$q - $r];
    for ($i = 0; $i < $rotation; $i++) {
      $cube = [-$cube[1], -$cube[2], -$cube[0]];
    }
    return [
      'x' => $cube[0],
      'y' => 2 * $cube[1] + $cube[0],
    ];
  }
}
