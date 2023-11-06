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
 * gameoptions.inc.php
 *
 * Rauha game options description
 *
 */

namespace RAUHA;

require_once 'modules/php/constants.inc.php';

$game_options = [
  OPTION_BOARD_SIDE => [
    'name' => totranslate('Board Side'),
    'values' => [
      OPTION_A_SIDE => [
        'name' => totranslate('A-side'),
        'description' => totranslate('Play with A board side'),
        'tmdisplay' => totranslate('A-side'),
      ],
      OPTION_B_SIDE => [
        'name' => totranslate('B-side'),
        'description' => totranslate('Play with B board side'),
        'tmdisplay' => totranslate('B-side'),
      ],
    ],
    'default' => OPTION_A_SIDE,
  ],
  OPTION_SYNTYMA_SHAMANS => [
    'name' => totranslate('Syntyma Shamans'),
    'values' => [
      OPTION_SYNTYMA_SHAMANS_ON => [
        'name' => totranslate('With'),
        'description' => totranslate('Play with Syntyma Shamans'),
        'tmdisplay' => totranslate('Syntyma On'),
      ],
      OPTION_SYNTYMA_SHAMANS_OFF => [
        'name' => totranslate('Without'),
        'description' => totranslate('Play without Syntyma Shamans'),
        'tmdisplay' => totranslate('Syntyma Off'),
      ],
    ],
    'default' => OPTION_SYNTYMA_SHAMANS_OFF,
  ],
  OPTION_SYNTYMA_NEW_GODS => [
    'name' => totranslate('Syntyma New Gods'),
    'values' => [
      OPTION_SYNTYMA_NEW_GODS_ON => [
        'name' => totranslate('With'),
        'description' => totranslate('Play with new gods'),
        'tmdisplay' => totranslate('New gods'),
      ],
      OPTION_SYNTYMA_NEW_GODS_ONLY => [
        'name' => totranslate('With and only with new gods'),
        'description' => totranslate('Play with ONLY new gods'),
        'tmdisplay' => totranslate('Only new gods'),
      ],
      OPTION_SYNTYMA_NEW_GODS_OFF => [
        'name' => totranslate('Without'),
        'description' => totranslate('Play without new gods'),
        'tmdisplay' => totranslate('Basic gods'),
      ],
    ],
    'default' => OPTION_SYNTYMA_NEW_GODS_OFF,
  ],
];

$game_preferences = [
  OPTION_CONFIRM => [
    'name' => totranslate('Turn confirmation'),
    'needReload' => false,
    'values' => [
      OPTION_CONFIRM_TIMER => [
        'name' => totranslate('Enabled with timer'),
      ],
      OPTION_CONFIRM_ENABLED => ['name' => totranslate('Enabled')],
      OPTION_CONFIRM_DISABLED => ['name' => totranslate('Disabled')],
    ],
  ],
  OPTION_ACTIVATION => [
    'name' => 'Automatic Biomes activation',
    'needReload' => false,
    'values' => [
      OPTION_MANUAL_ACTIVATION => [
        'name' => 'Manual Activation',
      ],
      OPTION_AUTOMATIC_ACTIVATION => [
        'name' => 'Automatic Activation',
      ],
    ],
  ],
  OPTION_ROUND_MARKER => [
    'name' => 'Round marker',
    'needReload' => false,
    'values' => [
      OPTION_ROUND_MARKER_OFFICIAL => [
        'name' => 'Round number + turn number in that round',
      ],
      OPTION_ROUND_MARKER_CUSTOM => [
        'name' => 'Just the turn number ranging from 1 to 16',
      ],
    ],
  ],
];
