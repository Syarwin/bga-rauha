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
      this._activeStates = ['placeBiome'];
      this._notifications = [
        ['chooseBiome', 100],
        ['confirmChoices', 1000],
        ['placeBiome', 1200],
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
      let currentPlayerNo = 1;
      let nPlayers = 0;
      this.forEachPlayer((player) => {
        let isCurrent = player.id == this.player_id;
        this.place('tplPlayerBoard', player, 'rauha-boards-container');
        player.board.forEach((biome) => {
          if (biome.dataId < 100) return;

          let cell = this.getCell(player.id, biome.x, biome.y);
          this.place('tplBiome', biome, cell);
        });

        if (player.hand !== null) {
          this.place('tplBiome', player.hand, 'pending-biomes');
        }

        // Useful to order boards
        nPlayers++;
        if (isCurrent) currentPlayerNo = player.no;
      });

      // Order them
      this.forEachPlayer((player) => {
        $(`board-${player.id}`).style.order = ((player.no - currentPlayerNo + nPlayers) % nPlayers) + 1;
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

          <div></div>
          <div></div>
          <div></div>
          <div></div>
          <div></div>

          <div></div>
          <div class='board-cell cell-node'  data-x='0' data-y='0'></div>
          <div class='board-cell cell-node'  data-x='1' data-y='0'></div>
          <div class='board-cell cell-node'  data-x='2' data-y='0'></div>
          <div></div>

          <div></div>
          <div class='board-cell cell-node'  data-x='0' data-y='1'></div>
          <div class='board-cell cell-node'  data-x='1' data-y='1'></div>
          <div class='board-cell cell-node'  data-x='2' data-y='1'></div>
          <div></div>

          <div></div>
          <div class='board-cell cell-node'  data-x='0' data-y='2'></div>
          <div class='board-cell cell-node'  data-x='1' data-y='2'></div>
          <div class='board-cell cell-node'  data-x='2' data-y='2'></div>
          <div></div>

          <div></div>
          <div></div>
          <div></div>
          <div></div>
          <div></div>
        </div>
      </div>`;
    },

    getCell(pId, x, y) {
      return $(`board-${pId}`).querySelector(`[data-x='${x}'][data-y='${y}']`);
    },

    tplBiome(biome) {
      return `<div class='biome-card age${biome.dataId < 140 ? 1 : 2}' id='biome-${biome.id}' data-id='${biome.dataId}'>
        <div class='biome-spore-container'></div>
      </div>`;
    },

    //////////////////////////////////////////////////////////////////////
    //    ____ _                            ____  _
    //   / ___| |__   ___   ___  ___  ___  | __ )(_) ___  _ __ ___   ___
    //  | |   | '_ \ / _ \ / _ \/ __|/ _ \ |  _ \| |/ _ \| '_ ` _ \ / _ \
    //  | |___| | | | (_) | (_) \__ \  __/ | |_) | | (_) | | | | | |  __/
    //   \____|_| |_|\___/ \___/|___/\___| |____/|_|\___/|_| |_| |_|\___|
    //////////////////////////////////////////////////////////////////////

    onEnteringStateChooseBiome(args) {
      let elements = {};
      if (args._private) {
        let biomes = args._private.biomes;
        Object.keys(biomes).forEach((biomeId) => {
          elements[biomeId] = this.place('tplBiome', biomes[biomeId], 'pending-biomes');
        });

        if (args._private.choice !== null && $(`biome-${args._private.choice}`)) {
          $(`biome-${args._private.choice}`).classList.add('choice');
        }
      }

      this.onSelectN(elements, 1, (elementIds) => {
        this.takeAction('actChooseBiome', { biomeId: elementIds[0] }, false);
        return true;
      });
    },

    notif_chooseBiome(n) {
      debug('Notif: choosing biome', n);
      this.clearActionButtons();
      dojo.query('#pending-biomes .biome-card').removeClass('selected choice');
      $(`biome-${n.args.biomeId}`).classList.add('choice');
    },

    notif_confirmChoices(n) {
      [...$('pending-biomes').querySelectorAll('.biome-card')].forEach((biome, i) => {
        if (!biome.classList.contains('choice')) {
          this.slide(biome, 'page-title', {
            delay: i * 50,
            destroy: true,
          });
        }
      });
    },

    //////////////////////////////////////////////////////////////
    //  ____  _                  ____  _
    // |  _ \| | __ _  ___ ___  | __ )(_) ___  _ __ ___   ___
    // | |_) | |/ _` |/ __/ _ \ |  _ \| |/ _ \| '_ ` _ \ / _ \
    // |  __/| | (_| | (_|  __/ | |_) | | (_) | | | | | |  __/
    // |_|   |_|\__,_|\___\___| |____/|_|\___/|_| |_| |_|\___|
    //////////////////////////////////////////////////////////////
    onEnteringStatePlaceBiome(args) {
      this.addDangerActionButton('btnDiscardCrystal', _('Discard and get 4 Crystals'), () => debug('TODO'));
      this.addDangerActionButton('btnDiscardSpore', _('Discard and get 1 Spore'), () => debug('TODO'));

      let selectedPlace = null;
      let selectedCell = null;
      args.possiblePlaces.forEach((place) => {
        let cell = this.getCell(this.player_id, place[0], place[1]);
        this.onClick(cell, () => {
          if (selectedCell !== null) {
            selectedCell.classList.remove('selected');
          }

          if (selectedPlace == place) {
            selectedCell = null;
            selectedPlace = null;
            $('btnConfirmPlace').remove();
          } else {
            selectedCell = cell;
            selectedCell.classList.add('selected');
            selectedPlace = place;
            this.addPrimaryActionButton('btnConfirmPlace', _('Confirm'), () =>
              this.takeAction('actPlaceBiome', { x: selectedPlace[0], y: selectedPlace[1] }),
            );
          }
        });
      });
    },

    notif_placeBiome(n) {
      debug('Notif: placing biome', n);
      let biome = n.args.biome;
      if (!$(`biome-${biome.id}`)) {
        this.place('tplBiome', biome, 'page-title');
      }
      this.slide(`biome-${biome.id}`, this.getCell(n.args.player_id, n.args.x, n.args.y));
    },
  });
});
