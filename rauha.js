/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Rauha implementation : © Timothée Pecatte <tim.pecatte@gmail.com>, Emmanuel ??
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * rauha.js
 *
 * Rauha user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () {};

define([
  'dojo',
  'dojo/_base/declare',
  'ebg/core/gamegui',
  'ebg/counter',
  g_gamethemeurl + 'modules/js/Core/game.js',
  g_gamethemeurl + 'modules/js/Core/modal.js',
], function (dojo, declare) {
  return declare('bgagame.rauha', [customgame.game], {
    constructor() {
      this._activeStates = [];
      this._notifications = [
        // ["chooseCard", 100],
        // ["actionCardCleanup", 500],
      ];

      // Fix mobile viewport (remove CSS zoom)
      this.default_viewport = 'width=800';

      // this._settingsSections = [];
      this._settingsConfig = {};
    },

    /**
     * Setup:
     *	This method set up the game user interface according to current game situation specified in parameters
     *	The method is called each time the game interface is displayed to a player, ie: when the game starts and when a player refreshes the game page (F5)
     *
     * Params :
     *	- mixed gamedatas : contains all datas retrieved by the getAllDatas PHP method.
     */
    setup(gamedatas) {
      debug('SETUP', gamedatas);

      //        this.setupInfoPanel();
      this.setupPlayers();
      //   this.setupInfoPanel();
      $('game_play_area').dataset.step = 0;
      this.inherited(arguments);
    },

    setupPlayers() {
      this.forEachPlayer((player) => {
        this.place('tplPlayerBoard', player, 'rauha-boards-container');
      });
      // [...document.querySelectorAll('.avatar-slot')].forEach((elt) => {
      //   dojo.place('<div class="rauha-avatar"></div>', elt);
      // });
    },

    tplPlayerBoard(player) {
      return `<div class='rauha-board' id='board-${player.id}' data-color='${player.color}'>
        <div class='player-name' style='color:#${player.color}'>${player.name}</div>
        <div class='board-grid'>
          <div class="rauha-avatar"></div>
          <div class='board-cell cell-corner' data-x='0' data-y='0'><div class='avatar-slot' data-step='15'></div></div>
          <div class='board-cell cell-hedge'  data-x='1' data-y='0'><div class='avatar-slot' data-step='0'></div></div>
          <div class='board-cell cell-hedge'  data-x='2' data-y='0'><div class='avatar-slot' data-step='1'></div></div>
          <div class='board-cell cell-hedge'  data-x='3' data-y='0'><div class='avatar-slot' data-step='2'></div></div>
          <div class='board-cell cell-corner' data-x='4' data-y='0'><div class='avatar-slot' data-step='3'></div></div>

          <div class='board-cell cell-vedge' data-x='0' data-y='1'><div class='avatar-slot' data-step='14'></div></div>
          <div class='board-cell cell-node'  data-x='1' data-y='1'></div>
          <div class='board-cell cell-node'  data-x='2' data-y='1'></div>
          <div class='board-cell cell-node'  data-x='3' data-y='1'></div>
          <div class='board-cell cell-vedge' data-x='4' data-y='1'><div class='avatar-slot' data-step='4'></div></div>

          <div class='board-cell cell-vedge' data-x='0' data-y='2'><div class='avatar-slot' data-step='13'></div></div>
          <div class='board-cell cell-node'  data-x='1' data-y='2'></div>
          <div class='board-cell cell-node'  data-x='2' data-y='2'></div>
          <div class='board-cell cell-node'  data-x='3' data-y='2'></div>
          <div class='board-cell cell-vedge' data-x='4' data-y='2'><div class='avatar-slot' data-step='5'></div></div>

          <div class='board-cell cell-vedge' data-x='0' data-y='3'><div class='avatar-slot' data-step='12'></div></div>
          <div class='board-cell cell-node'  data-x='1' data-y='3'></div>
          <div class='board-cell cell-node'  data-x='2' data-y='3'></div>
          <div class='board-cell cell-node'  data-x='3' data-y='3'></div>
          <div class='board-cell cell-vedge' data-x='4' data-y='3'><div class='avatar-slot' data-step='6'></div></div>

          <div class='board-cell cell-corner' data-x='0' data-y='4'><div class='avatar-slot' data-step='11'></div></div>
          <div class='board-cell cell-hedge'  data-x='1' data-y='4'><div class='avatar-slot' data-step='10'></div></div>
          <div class='board-cell cell-hedge'  data-x='2' data-y='4'><div class='avatar-slot' data-step='9'></div></div>
          <div class='board-cell cell-hedge'  data-x='3' data-y='4'><div class='avatar-slot' data-step='8'></div></div>
          <div class='board-cell cell-corner' data-x='4' data-y='4'><div class='avatar-slot' data-step='7'></div></div>
        </div>
      </div>`;
    },
  });
});
