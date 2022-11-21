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
 * states.inc.php
 *
 * Rauha game states description
 *
 */

$machinestates = [
  // The initial state. Please do not modify.
  ST_GAME_SETUP => [
    'name' => 'gameSetup',
    'description' => '',
    'type' => 'manager',
    'action' => 'stGameSetup',
    'transitions' => ['' => ST_NEXT_ROUND],
  ],

  ST_NEXT_ROUND => [
    'name' => 'nextRound',
    'description' => clienttranslate('Decks are prepared'),
    'type' => 'game',
    'action' => 'stNextRound',
    'updateGameProgression' => false,
    'transitions' => [
      'round_start' => ST_MOVE_AVATARS,
      'game_end' => ST_PRE_END_OF_GAME,
    ],
  ],

  ST_MOVE_AVATARS => [
    'name' => 'moveAvatars',
    'description' => clienttranslate('Avatars moves one space'),
    'type' => 'game',
    'action' => 'stMoveAvatars',
    'updateGameProgression' => true,
    'transitions' => [
      'action_turn' => ST_CHOOSE_BIOME,
      'count_turn' => ST_COUNT_NEXT_PLAYER,
    ],
  ],

  ST_CHOOSE_BIOME => [
    'name' => 'chooseBiome',
    'description' => clienttranslate('All players must choose a Biome'),
    'descriptionmyturn' => clienttranslate('${you} must choose a Biome'),
    'args' => 'argChooseBiome',
    'type' => 'multipleactiveplayer',
    'possibleactions' => ['actChooseBiome'],
    'transitions' => [
      '' => ST_CONFIRM_CHOICES,
    ],
  ],

  ST_CONFIRM_CHOICES => [
    'name' => 'confirmChoices',
    'description' => '',
    'type' => 'game',
    'action' => 'stConfirmChoices',
    'transitions' => [
      '' => ST_NEXT_PLAYER,
    ],
  ],

  ST_NEXT_PLAYER => [
    'name' => 'nextPlayer',
    'description' => '',
    'type' => 'game',
    'action' => 'stNextPlayer',
    'updateGameProgression' => false,
    'transitions' => [
      'next_player_action' => ST_PLACE_BIOME,
      'end_turn' => ST_MOVE_AVATARS,
    ],
  ],

  ST_PLACE_BIOME => [
    'name' => 'placeBiome',
    'description' => clienttranslate('${actplayer} must place or discard their Biome'),
    'descriptionmyturn' => clienttranslate('${you} must place or discard your Biome'),
    'args' => 'argPlaceBiome',
    'type' => 'activeplayer',
    'possibleactions' => ['actPlaceBiome', 'actDiscardSpore', 'actDiscardCrystals'],
    'transitions' => [
      '' => ST_ACT_BIOMES,
    ],
  ],

  // SKIPPED
  // ST_HOST_GOD => [
  //   'name' => 'hostGod',
  //   'description' => 'God can be hosted',
  //   'type' => 'game',
  //   'action' => 'stHostGod',
  //   'updateGameProgression' => false,
  //   'transitions' => [
  //     '' => ST_ACT_BIOMES,
  //   ],
  // ],

  ST_ACT_BIOMES => [
    'name' => 'activate',
    'description' => clienttranslate('${actplayer} may activate their Biomes and new god(s)'),
    'descriptionmyturn' => clienttranslate('${you} may activate your Biomes and new god(s)'),
    'args' => 'argActBiomes',
    'action' => 'stActBiomes',
    'type' => 'activeplayer',
    'possibleactions' => ['actActivateBiome', 'actActivateGod', 'actSkip'],
    'transitions' => [
      'actActivate' => ST_ACT_BIOMES,
      'actSkip' => ST_NEXT_PLAYER,
    ],
  ],

  //SKIPPED
  // ST_DISCARD_LAST_CARDS => [
  //   'name' => 'discardLastCards',
  //   'description' => '',
  //   'type' => 'game',
  //   'action' => 'stDiscardLastCards',
  //   'updateGameProgression' => false,
  //   'transitions' => [
  //     '' => ST_COUNT_NEXT_PLAYER,
  //   ],
  // ],

  ST_COUNT_NEXT_PLAYER => [
    'name' => 'countNextPlayer',
    'description' => '',
    'type' => 'game',
    'action' => 'stCountNextPlayer',
    'updateGameProgression' => false,
    'transitions' => [
      'next_player_count' => ST_COUNT_ACTION,
      'end_turn' => ST_NEXT_ROUND,
    ],
  ],

  ST_COUNT_ACTION => [
    'name' => 'countAction',
    'description' => clienttranslate('${actplayer} must activate their Biome(s) and god(s)'),
    'descriptionmyturn' => clienttranslate('${you} must activate your Biome(s) and god(s)'),
    'type' => 'activeplayer',
    'args' => 'argCountAction',
    'possibleactions' => ['actActivateBiome', 'actActivateGod', 'actSkip'],
    'transitions' => [
      'actActivate' => ST_COUNT_ACTION,
      'actSkip' => ST_COUNT_NEXT_PLAYER,
    ],
  ],

  ST_COUNT_WATER_SOURCE => [
    'name' => 'countWaterSource',
    'description' => '',
    'type' => 'game',
    'action' => 'stCountWaterSource',
    'updateGameProgression' => false,
    'transitions' => [
      '' => ST_COUNT_NEXT_PLAYER,
    ],
  ],

  ST_PRE_END_OF_GAME => [
    'name' => 'preEndOfGame',
    'type' => 'game',
    'action' => 'stPreEndOfGame',
    'transitions' => ['' => ST_END_GAME],
  ],

  // Final state.
  // Please do not modify (and do not overload action/args methods).
  ST_END_GAME => [
    'name' => 'gameEnd',
    'description' => clienttranslate('End of game'),
    'type' => 'manager',
    'action' => 'stGameEnd',
    'args' => 'argGameEnd',
  ],
];
