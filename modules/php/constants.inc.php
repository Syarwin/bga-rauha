<?php

/*
 * Game Constants
 */

const MOUNTAIN = 'mountain';
const FOREST = 'forest';
const MUSHROOM = 'mushroom';
const CRYSTAL = 'crystal';
const DESERT = 'desert';

const FLYING = 'flying animals';
const WALKING = 'terrestrial animal';
const MARINE = 'marine animal';
const ANIMALS = 'animals';
const WATER_SOURCE = 'waterSource';
const SPORE = 'spore';

const CARDS_PER_DECK = 4;
const MOON = 1;
const STAR = 0;
const DECK_TO_CHOOSE = [
  //give the symbol of the deck to choose each turn
  null,
  MOON,
  STAR,
  MOON,
  null,
  STAR,
  MOON,
  STAR,
  null,
  MOON,
  STAR,
  MOON,
  null,
  STAR,
  MOON,
  STAR,
  null,
];
const ALL_BIOME_PLACES = [[0, 0], [1, 0], [2, 0], [0, 1], [1, 1], [2, 1], [0, 2], [1, 2], [2, 2]];
const BOARD_ACTIVATION = [
  //give the coords of Biome to activate each turn
  1 => [[0, 0], [0, 1], [0, 2]],
  2 => [[1, 0], [1, 1], [1, 2]],
  3 => [[2, 0], [2, 1], [2, 2]],

  5 => [[0, 0], [1, 0], [2, 0]],
  6 => [[0, 1], [1, 1], [2, 1]],
  7 => [[0, 2], [1, 2], [2, 2]],

  9 => [[2, 0], [2, 1], [2, 2]],
  10 => [[1, 0], [1, 1], [1, 2]],
  11 => [[0, 0], [0, 1], [0, 2]],

  13 => [[0, 2], [1, 2], [2, 2]],
  14 => [[0, 1], [1, 1], [2, 1]],
  15 => [[0, 0], [1, 0], [2, 0]],
];
const NOT_USED = 0;
const USED = 1;

const POINTS_FOR_WATER_SOURCE = [0, 1, 3, 6, 10, 15];

/*
 * Game options
 */

const OPTION_BOARD_SIDE = 102;
const OPTION_A_SIDE = 0;
const OPTION_B_SIDE = 1;

/*
 * User preferences
 */
const OPTION_CONFIRM = 103;
const OPTION_CONFIRM_DISABLED = 0;
const OPTION_CONFIRM_TIMER = 1;
const OPTION_CONFIRM_ENABLED = 2;

const OPTION_ACTIVATION = 104;
const OPTION_MANUAL_ACTIVATION = 0;
const OPTION_AUTOMATIC_ACTIVATION = 1;

const OPTION_ROUND_MARKER = 105;
const OPTION_ROUND_MARKER_OFFICIAL = 0;
const OPTION_ROUND_MARKER_CUSTOM = 1;

/*
 * State constants
 */
const ST_GAME_SETUP = 1;

const ST_NEXT_ROUND = 2;
const ST_MOVE_AVATARS = 3;

const ST_CHOOSE_BIOME = 4;
const ST_CONFIRM_CHOICES = 12;
const ST_NEXT_PLAYER = 5;
const ST_PLACE_BIOME = 6;
const ST_HOST_GOD = 7;
const ST_ACT_BIOMES = 8;

// const ST_DISCARD_LAST_CARDS = 9;
const ST_COUNT_NEXT_PLAYER = 10;
const ST_COUNT_ACTION = 11;
// const ST_COUNT_WATER_SOURCE = 13;

const ST_PRE_END_OF_GAME = 98;
const ST_END_GAME = 99;

//not used
const TRANSITIONS = [
  'round_start' => ST_MOVE_AVATARS,
  'game_end' => ST_PRE_END_OF_GAME,
  'action_turn' => ST_CHOOSE_BIOME,
  'count_turn' => ST_COUNT_NEXT_PLAYER,
  'next_player_action' => ST_PLACE_BIOME,
  'end_turn' => ST_MOVE_AVATARS,
  'place' => ST_HOST_GOD,
  'discard' => ST_ACT_BIOMES,
  'act' => ST_ACT_BIOMES,
  'skip_act' => ST_NEXT_PLAYER,
  'next_player_count' => ST_COUNT_ACTION,
  'end_turn' => ST_NEXT_ROUND,
  'count' => ST_COUNT_ACTION,
  'skip_count' => ST_COUNT_NEXT_PLAYER,
];

/******************
 ****** STATS ******
 ******************/

const STAT_COLLECTED_CRISTAL = 11;
const STAT_WATER_SOURCES_POINTS = 12;
const STAT_ANIMALS_POINTS = 13;
const STAT_BIOMES_POINTS = 14;
const STAT_SPORES_POINTS = 15;
const STAT_ALIGNMENTS = 16;
const STAT_END_STEP_ACTIVATIONS = 17;
const STAT_END_ROUND_ACTIVATIONS = 18;

const STAT_NAME_COLLECTED_CRISTAL = 'collectedCristal';
const STAT_NAME_WATER_SOURCES_POINTS = 'waterSourcePoints';
const STAT_NAME_ANIMALS_POINTS = 'animalsPoints';
const STAT_NAME_BIOMES_POINTS = 'biomesPoints';
const STAT_NAME_SPORES_POINTS = 'sporePoints';
const STAT_NAME_ALIGNMENTS = 'alignments';
const STAT_NAME_END_STEP_ACTIVATIONS = 'endStepActivations';
const STAT_NAME_END_ROUND_ACTIVATIONS = 'endRoundActivations';
