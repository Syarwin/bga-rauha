<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Rauha implementation : © Timothée Pecatte <tim.pecatte@gmail.com>, Emmanuel ??
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
    'transitions' => ['' => ST_BEFORE_START_OF_TURN],
  ],

  ST_BEFORE_START_OF_TURN => [
    'name' => 'beforeStartOfTurn',
    'description' => '',
    'type' => 'game',
    'action' => 'stBeforeStartOfTurn',
    'updateGameProgression' => true,
    'transitions' => [
      '' => ST_FOO,
    ],
  ],

  ST_FOO => [
    'name' => 'foo',
    'description' => '',
    'type' => 'activeplayer',
    'description' => clienttranslate('${active} must play'),
    'descriptionmyturn' => clienttranslate('${you} must play'),
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
