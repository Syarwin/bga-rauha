<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Rauha implementation : © Timothée Pecatte <tim.pecatte@gmail.com>, Emmanuel Albisser <emmanuel.albisser@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * stats.inc.php
 *
 * Rauha game statistics description
 *
 */

require_once 'modules/php/constants.inc.php';

$stats_type = [
  'table' => [],

  'value_labels' => [],

  'player' => [
    STAT_NAME_COLLECTED_CRISTAL => [
      'id' => STAT_COLLECTED_CRISTAL,
      'name' => totranslate('Collected cristals'),
      'type' => 'int',
    ],

    STAT_NAME_WATER_SOURCES_POINTS => [
      'id' => STAT_WATER_SOURCES_POINTS,
      'name' => totranslate('Points with water sources'),
      'type' => 'int',
    ],

    STAT_NAME_ANIMALS_POINTS => [
      'id' => STAT_ANIMALS_POINTS,
      'name' => totranslate('Points with animals'),
      'type' => 'int',
    ],

    STAT_NAME_BIOMES_POINTS => [
      'id' => STAT_BIOMES_POINTS,
      'name' => totranslate('Points direct with biomes'),
      'type' => 'int',
    ],

    STAT_NAME_SPORES_POINTS => [
      'id' => STAT_SPORES_POINTS,
      'name' => totranslate('Points with spores'),
      'type' => 'int',
    ],

    STAT_NAME_ALIGNMENTS => [
      'id' => STAT_ALIGNMENTS,
      'name' => totranslate('Alignments done'),
      'type' => 'int',
    ],

    STAT_NAME_END_STEP_ACTIVATIONS => [
      'id' => STAT_END_STEP_ACTIVATIONS,
      'name' => totranslate('God/Biome activations at step ends'),
      'type' => 'int',
    ],

    STAT_NAME_END_ROUND_ACTIVATIONS => [
      'id' => STAT_END_ROUND_ACTIVATIONS,
      'name' => totranslate('God/Biome activations at round ends'),
      'type' => 'int',
    ],


  ],
];
