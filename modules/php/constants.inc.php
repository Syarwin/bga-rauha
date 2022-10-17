<?php

/*
 * Game options
 */

/*
 * User preferences
 */
const OPTION_CONFIRM = 103;
const OPTION_CONFIRM_DISABLED = 0;
const OPTION_CONFIRM_TIMER = 1;
const OPTION_CONFIRM_ENABLED = 2;

/*
 * State constants
 */
const ST_GAME_SETUP = 1;

const ST_NEXT_ROUND = 2
const ST_MOVE_AVATARS = 3

const ST_CHOOSE_BIOME = 4
const ST_NEXT_PLAYER = 5
const ST_PLACE_BIOME = 6
const ST_HOST_GOD = 7
const ST_ACT_BIOMES = 8

const ST_COUNT_NEXT_PLAYER = 9
const ST_COUNT_ACTION = 10

const ST_PRE_END_OF_GAME = 98; //TODO but why ?
const ST_END_GAME = 99;

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
    'skip_count' => ST_COUNT_NEXT_PLAYER
]
	
/******************
 ****** STATS ******
 ******************/
