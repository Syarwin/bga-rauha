/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Rauha implementation : © Timothée Pecatte <tim.pecatte@gmail.com>, Emmanuel Albisser <emmanuel.albisser@gmail.com>
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
  g_gamethemeurl + 'modules/js/biomes.js',
], function (dojo, declare) {
  return declare('bgagame.rauha', [customgame.game], {
    constructor() {
      this._activeStates = ['placeBiome', 'chooseBiome', 'activate'];
      this._notifications = [
        ['newTurn', 1000],
        ['newTurnScoring', 1000],
        ['chooseBiome', 100],
        ['confirmChoices', 1000],
        ['placeBiome', null],
        ['discardBiomeCrystals', 1000],
        ['discardBiomeSpore', null],
        ['activateBiome', null],
        ['activateGod', null],
        ['placeSpore', 1300],
        ['newAlignment', 1000],
        ['endActivation', 500],
        ['refreshGods', 500],
        ['refreshBiomes', 500],
        ['waterSourceCount', 1300],
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

      this.setupPlayers();
      this.setupInfoPanel();
      this.setupGods();

      // Create round counter
      this._roundCounter = this.createCounter('round-counter');
      this.updateTurn();

      this.inherited(arguments);
    },

    setupPlayers() {
      let currentPlayerNo = 1;
      let nPlayers = 0;
      this._crystalCounters = {};
      this._waterCounters = {};
      this.forEachPlayer((player) => {
        let isCurrent = player.id == this.player_id;
        this.place('tplPlayerPanel', player, `player_panel_content_${player.color}`);
        this._crystalCounters[player.id] = this.createCounter(`crystal-counter-${player.id}`, player.crystal);
        this._waterCounters[player.id] = this.createCounter(`water-counter-${player.id}`, player.water);

        this.place('tplPlayerBoard', player, 'rauha-boards-container');
        player.biomes.forEach((biome) => {
          this.addBiome(biome);
        });

        if (player.hand !== null) {
          this.place('tplBiome', player.hand, 'pending-biomes');
        }

        // Change default point icon
        dojo.place(`<svg><use href="#points-svg" /></svg>`, `icon_point_${player.id}`, 'after');

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
      let content = '';
      for (let i = 0; i < 5; i++) {
        for (let j = 0; j < 5; j++) {
          if (i == 0 || j == 0 || i == 4 || j == 4) {
            content += '<div></div>';
          } else {
            let y = i - 1,
              x = j - 1;
            let spore = player.board[y][x] == 1 ? `<div class='spore'></div>` : '';
            content += `<div class='board-cell cell-node' data-x='${x}' data-y='${y}'><div class='spore-holder'>${spore}</div></div>`;
          }
        }
      }
      return `<div class='rauha-board' id='board-${player.id}' data-color='${player.color}'>
        <div class='player-name' style='color:#${player.color}'>${player.name}</div>
        <div class='board-grid'>
          <div class="rauha-avatar"></div>
          ${content}
        </div>
      </div>`;
    },

    getCell(pId, x, y) {
      return $(`board-${pId}`).querySelector(`[data-x='${x}'][data-y='${y}']`);
    },

    /**
     * Player panel : display crystal
     */

    tplPlayerPanel(player) {
      return `<div class='rauha-panel'>
        <div class='rauha-player-infos'>
          <div class='crystal-counter' id='crystal-counter-${player.id}'></div>
          <div class='water-counter' id='water-counter-${player.id}'></div>
        </div>
        <div class='rauha-gods-container' id='gods-${player.id}'></div>
      </div>`;
    },

    gainPayCrystal(pId, n, targetSource = null) {
      if (this.isFastMode()) {
        this._crystalCounters[pId].incValue(n);
        return Promise.resolve();
      }

      let elem = `<div id='crystal-animation' class='crystal-icon'>${Math.abs(n)}</div>`;
      $('page-content').insertAdjacentHTML('beforeend', elem);
      if (n > 0) {
        return this.slide('crystal-animation', `crystal-counter-${pId}`, {
          from: targetSource || 'page-title',
          destroy: true,
          phantom: false,
          duration: 1200,
        }).then(() => this._crystalCounters[pId].incValue(n));
      } else {
        this._crystalCounters[pId].incValue(n);
        return this.slide('crystal-animation', targetSource || 'page-title', {
          from: `crystal-counter-${pId}`,
          destroy: true,
          phantom: false,
          duration: 1200,
        });
      }
    },

    gainPoints(pId, n, targetSource = null) {
      if (this.isFastMode()) {
        this.scoreCtrl[pId].incValue(n);
        return Promise.resolve();
      }

      let elem = `<div id='points-animation' class='points-icon'>${Math.abs(n)}</div>`;
      $('page-content').insertAdjacentHTML('beforeend', elem);
      return this.slide('points-animation', `player_score_${pId}`, {
        from: targetSource || 'page-title',
        destroy: true,
        phantom: false,
        duration: 1200,
      }).then(() => this.scoreCtrl[pId].incValue(n));
    },

    notif_waterSourceCount(n) {
      debug('Notif: scoring phase, count water sources', n);
      this.gainPoints(n.args.player_id, n.args.points);
    },

    ////////////////////////////////////////////
    //  ____  _
    // | __ )(_) ___  _ __ ___   ___  ___
    // |  _ \| |/ _ \| '_ ` _ \ / _ \/ __|
    // | |_) | | (_) | | | | | |  __/\__ \
    // |____/|_|\___/|_| |_| |_|\___||___/
    ////////////////////////////////////////////

    addBiome(biome, container = null) {
      this.loadBiomeData(biome);
      container = container || this.getBiomeContainer(biome);
      let elem = this.place('tplBiome', biome, container);
      this.addCustomTooltip(`biome-${biome.id}`, this.tplBiomeTooltip(biome));
      return elem;
    },

    getBiomeContainer(biome) {
      if (biome.location == 'board') {
        return this.getCell(biome.pId, biome.x, biome.y);
      }

      console.error('Trying to place a biome card', biome);
      return $('game_play_area');
    },

    tplBiome(biome) {
      let biomeClass = 'starting';
      if (biome.dataId >= 100) biomeClass = `age${biome.dataId < 140 ? 1 : 2}`;

      return `<div class='biome-card ${biomeClass}' id='biome-${biome.id}' data-id='${biome.dataId}'>
        <div class='biome-spore-container'></div>
      </div>`;
    },

    tplBiomeTooltip(biome) {
      let biomeClass = 'starting';
      let costMessage = ''
      let income = ''
      let typeIncome = ''
      let conditionMessage = ''
      if (biome.dataId >= 100) biomeClass = `age${biome.dataId < 140 ? 1 : 2}`;

      if (biome.usageCost){
        costMessage = _("if you pay ${usageCost} crystal(s), ", { usageCost: biome.usageCost});
      }

      if (biome.crystalIncome){
        typeIncome = _('crystal(s) ');
        income = biome.crystalIncome;
      }

      if (biome.pointIncome){
        typeIncome = _('point(s) ');
        income = biome.pointIncome;
      }

      if (biome.sporeIncome){
        typeIncome = _('spore ')
        income = biome.sporeIncome
      }

      if (biome.multiplier != '1'){
        conditionMessage = _('per ${multiplier} on your board', {multipier:biome.multiplier});
      }

      let message = format_string_recursive('When activated, ${costMessage}this biome provides you ${income} ${typeIncome} ${conditionMessage}',
      {
        costMessage : costMessage,
        income : income,
        typeIncome : typeIncome,
        conditionMessage : conditionMessage
      });

      return `<div class='biome-tooltip'>
        <div class='biome-card ${biomeClass}' data-id='${biome.dataId}'>
          <div class='biome-spore-container'></div>
        </div>
        <div class='biome-help'>${message}</div>
        <div class='biome-types'>${_('Symbol(s):')} ${biome.types.join(',')}</div>
        <div class='biome-animals'>${_('Symbol(s):')} ${biome.animals.join(',')}</div>
      </div>`;
    },

    loadBiomeData(biome) {
      Object.assign(biome, BIOMES_DATA[biome.dataId]);
    },

    /////////////////////////////
    //    ____           _
    //   / ___| ___   __| |___
    //  | |  _ / _ \ / _` / __|
    //  | |_| | (_) | (_| \__ \
    //   \____|\___/ \__,_|___/
    /////////////////////////////
    setupGods() {
      Object.keys(this.gamedatas.gods).forEach((godId) => {
        let god = this.gamedatas.gods[godId];
        this.place('tplGod', god, this.getGodContainer(god));
        this.addCustomTooltip(`god-${god.id}`, this.tplGodTooltip(god));
      });
    },

    getGodContainer(god) {
      if (god.location == 'table') {
        return $('pending-gods');
      } else if (god.location == 'board') {
        return $(`gods-${god.pId}`);
      }
    },

    tplGod(god) {
      return `<div class='rauha-god' id='god-${god.id}' data-id='${god.id}' data-used='${god.used}'>
        <div class="rauha-god-inner">
          <div class='god-front'></div>
          <div class='god-back'></div>
        </div>
      </div>`;
    },

    tplGodTooltip(god) {
      let infos = this.getGodInformation(god);
      return `<div class='god-tooltip'>      
      <div class='rauha-god' data-id='${god.id}' data-used='0'>
        <div class="rauha-god-inner">
          <div class='god-front'></div>
          <div class='god-back'></div>
        </div>
      </div>
      <div class='god-tooltip-desc'>
        ${infos.name} <br />
        ${infos.title} <br />
        ${infos.desc.join('<br />')}
      </div>
    </div>`;
    },

    getGodInformation(god) {
      let infos = {
        1: {
          name: _('Taivas'),
          type: 'flying',
          title: _('Elder of Skies'),
          desc: [
            _('When you welcome this Divine Entity and at each scoring if she is still with you:'),
            _('spend 4 crystals to score 7 Life Energy Points.'),
          ],
        },
        2: {
          name: _('Sienet'),
          type: 'mushroom',
          title: _('Disciple of the Mushrooms'),
          desc: [
            _('When you welcome this Divine Entity and at each scoring if she is still with you:'),
            _('take 3 Crystals from the supply.'),
          ],
        },
        3: {
          name: _('Meri'),
          type: 'marine',
          title: _('Elder of Seas'),
          desc: [
            _('When you welcome this Divine Entity and at each scoring if she is still with you:'),
            _('score 1 Life Energy Point for each Water Source on your board.'),
          ],
        },
        4: {
          name: _('Metsat'),
          type: 'forest',
          title: _('Disciple of the Forest'),
          desc: [
            _('When you welcome this Divine Entity and at each scoring if she is still with you:'),
            _(
              'choose a type of animals (Flying, Land or Marine) and score 1 Life Energy Point per matching symbolon your board.',
            ),
          ],
        },
        5: {
          name: _('Kiteet'),
          type: 'mushroom',
          title: _('Disciple of the Crystals'),
          desc: [
            _('When you welcome this Divine Entity and at each scoring if she is still with you:'),
            _('score 3 Life Energy Points.'),
          ],
        },
        6: {
          name: _('Vuori'),
          type: 'water',
          title: _('Disciple of the Hills and Water'),
          desc: [
            _(
              'This Divine Entity has no immediate effect but continuously adds 2 Water Sources to your number of Water Sources as long as she is with you.',
            ),
          ],
        },
        7: {
          name: _('Maa'),
          type: 'land',
          title: _('Elder of Earths'),
          desc: [
            _('When you welcome this Divine Entity and at each scoring if she is still with you:'),
            _('score 1 Life Energy Point for each Spore on your board.'),
          ],
        },
      };

      return infos[god.id];
    },

    notif_newAlignment(n) {
      debug('Notif: new alignement, moving god', n);
      let god = $(`god-${n.args.godId}`);
      god.dataset.used = 0;
      this.slide(god, `gods-${n.args.player_id}`);

      this._waterCounters[n.args.player_id].toValue(n.args.waterSourceCount);
      if (n.args.playerIdLoosingGod !== null) {
        this._waterCounters[n.args.playerIdLoosingGod].toValue(n.args.waterSourceCountPlayerLoosingGod);
      }
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
      this.addDangerActionButton('btnDiscardCrystal', _('Discard and get 4 Crystals'), () => {
        this.confirmationDialog(_('Are you sure you want to discard the biome card to get 4 Crystals?'), () => {
          this.takeAction('actDiscardCrystals', {});
        });
      });
      this.addDangerActionButton('btnDiscardSpore', _('Discard and get 1 Spore'), () =>
        this.clientState('discardBiomeSpore', _('Select the place where you want to place the spore'), args),
      );

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

    onEnteringStateDiscardBiomeSpore(args) {
      this.addCancelStateBtn();

      let selectedPlace = null;
      let selectedCell = null;
      args.possibleSporePlaces.forEach((place) => {
        let cell = this.getCell(this.player_id, place[0], place[1]);
        this.onClick(cell, () => {
          if (selectedCell !== null) {
            selectedCell.classList.remove('selected');
          }

          selectedCell = cell;
          selectedCell.classList.add('selected');
          selectedPlace = place;
          this.addDangerActionButton('btnConfirmPlace', _('Confirm and discard biome'), () =>
            this.takeAction('actDiscardSpore', { x: selectedPlace[0], y: selectedPlace[1] }),
          );
        });
      });
    },

    notif_placeBiome: async function (n) {
      debug('Notif: placing biome', n);
      let biome = n.args.biome;
      if (n.args.cost > 0) {
        await this.gainPayCrystal(n.args.player_id, -n.args.cost);
      }

      if (!$(`biome-${biome.id}`)) {
        this.place('tplBiome', biome, 'page-title');
      }
      $(`biome-${biome.id}`).classList.remove('choice');
      await this.slide(`biome-${biome.id}`, this.getCell(n.args.player_id, n.args.x, n.args.y));

      this._waterCounters[n.args.player_id].toValue(n.args.waterSourceCount);
      this.notifqueue.setSynchronousDuration(100);
    },

    notif_discardBiomeCrystals(n) {
      debug('Notif: discard a biome for 4 crystals', n);
      if (this.player_id == n.args.player_id) {
        let biome = $('pending-biomes').querySelector('.biome-card');
        this.slide(biome, 'page-title', {
          phantom: false,
          destroy: true,
        });
      }

      this.gainPayCrystal(n.args.player_id, 4);
    },

    notif_discardBiomeSpore: async function (n) {
      debug('Notif: discard a biome for a spore crystals', n);
      if (this.player_id == n.args.player_id) {
        let biome = $('pending-biomes').querySelector('.biome-card');
        await this.slide(biome, 'page-title', {
          phantom: false,
          destroy: true,
        });
      }

      let elem = dojo.place(`<div class='spore'></div>`, 'page-title');
      await this.slide(elem, this.getCell(n.args.player_id, n.args.x, n.args.y).querySelector('.spore-holder'), {
        phantom: false,
      });

      this.notifqueue.setSynchronousDuration(200);
    },

    ////////////////////////////////////////////////
    //     _        _   _            _
    //    / \   ___| |_(_)_   ____ _| |_ ___
    //   / _ \ / __| __| \ \ / / _` | __/ _ \
    //  / ___ \ (__| |_| |\ V / (_| | ||  __/
    // /_/   \_\___|\__|_| \_/ \__,_|\__\___|
    ////////////////////////////////////////////////
    onEnteringStateActivate(args) {
      args.activableBiomes.forEach((biome) => {
        this.loadBiomeData(biome);
        this.onClick(`biome-${biome.id}`, () => {
          if (biome.sporeIncome == 0) {
            this.takeAction('actActivateBiome', { biomeId: biome.id });
          } else {
            this.clientState('activateSpore', _('Select the place where you want to place the spore'), {
              biomeId: biome.id,
              possibleSporePlaces: args.possibleSporePlaces,
            });
          }
        });
      });

      args.activableGods.forEach((god) => {
        let infos = this.getGodInformation(god);
        this.addPrimaryActionButton(
          `btnActivateGod${god.id}`,
          this.fsr(_('Activate ${godName}'), { godName: infos.name }),
          () => {
            this.takeAction('actActivateGod', { godId: god.id });
          },
        );
      });

      this.addDangerActionButton('btnPass', _('Pass'), () => {
        this.confirmationDialog(_("Are you sure you don't want to activate remaining god/biome card(s)?"), () => {
          this.takeAction('actSkip', {});
        });
      });
    },

    onEnteringStateActivateSpore(args) {
      this.addCancelStateBtn();
      $(`biome-${args.biomeId}`).classList.add('selected');

      let selectedPlace = null;
      let selectedCell = null;
      args.possibleSporePlaces.forEach((place) => {
        let cell = this.getCell(this.player_id, place[0], place[1]);
        this.onClick(cell, () => {
          if (selectedCell !== null) {
            selectedCell.classList.remove('selected');
          }

          selectedCell = cell;
          selectedCell.classList.add('selected');
          selectedPlace = place;
          this.addPrimaryActionButton('btnConfirmPlace', _('Confirm and activate biome'), () =>
            this.takeAction('actActivateBiome', { biomeId: args.biomeId, x: selectedPlace[0], y: selectedPlace[1] }),
          );
        });
      });
    },

    activateElement: async function (args, elem) {
      // Flag the element as used
      elem.classList.remove('selected');
      elem.dataset.used = '1';

      // Pay crystal cost if any
      if (args.cost > 0) {
        await this.gainPayCrystal(args.player_id, -args.cost, elem);
      }

      // Gain crystal
      if (args.crystalIncome > 0) {
        await this.gainPayCrystal(args.player_id, args.crystalIncome, elem);
      }

      // Gain points
      if (args.pointIncome > 0) {
        await this.gainPoints(args.player_id, +args.pointIncome);
      }

      // Place spore
      if (args.sporeIncome > 0) {
        let spore = dojo.place(`<div class='spore'></div>`, 'page-title');
        this.slide(spore, this.getCell(args.player_id, args.sporeX, args.sporeY).querySelector('.spore-holder'), {
          phantom: false,
        });
      }

      this.notifqueue.setSynchronousDuration(200);
    },

    notif_activateBiome: async function (n) {
      debug('Notif: activating biome', n);
      let oBiome = $(`biome-${n.args.biomeId}`);
      await this.activateElement(n.args, oBiome);
    },

    notif_activateGod: async function (n) {
      debug('Notif: activating god', n);
      let oGod = $(`god-${n.args.godId}`);
      await this.activateElement(n.args, oGod);
    },

    notif_endActivation(n) {
      debug('Notif: someone finished activation thing', n);
      [...$(`gods-${n.args.player_id}`).querySelectorAll('.rauha-god')].forEach((oGod) => (oGod.dataset.used = 1));
      [...$(`board-${n.args.player_id}`).querySelectorAll('.biome-card[data-used="1"]')].forEach(
        (oBiome) => (oBiome.dataset.used = 0),
      );
    },

    notif_refreshGods(n) {
      [...document.querySelectorAll('.rauha-god')].forEach((oGod) => (oGod.dataset.used = 0));
    },

    notif_refreshBiomes(n) {
      debug('Notif: refreshing all the biomes', n);
      [...document.querySelectorAll('.biome-card[data-used="1"]')].forEach((oBiome) => (oBiome.dataset.used = 0));
    },

    ////////////////////////////////////////////////////////
    //  ___        __         ____                  _
    // |_ _|_ __  / _| ___   |  _ \ __ _ _ __   ___| |
    //  | || '_ \| |_ / _ \  | |_) / _` | '_ \ / _ \ |
    //  | || | | |  _| (_) | |  __/ (_| | | | |  __/ |
    // |___|_| |_|_|  \___/  |_|   \__,_|_| |_|\___|_|
    ////////////////////////////////////////////////////////

    setupInfoPanel() {
      dojo.place(this.tplConfigPlayerBoard(), 'player_boards', 'first');

      let chk = $('help-mode-chk');
      dojo.connect(chk, 'onchange', () => this.toggleHelpMode(chk.checked));
      this.addTooltip('help-mode-switch', '', _('Toggle help/safe mode.'));

      // this.onClick('show-ruby', () => this._rubyDialog.show(), false);
      // this.addTooltip('show-ruby', '', _('Show Ruby help sheet'));

      this._settingsModal = new customgame.modal('showSettings', {
        class: 'barrage_popin',
        closeIcon: 'fa-times',
        title: _('Settings'),
        closeAction: 'hide',
        verticalAlign: 'flex-start',
        contentsTpl: `<div id='barrage-settings'>
           <div id='barrage-settings-header'></div>
           <div id="settings-controls-container"></div>
         </div>`,
      });
    },

    tplConfigPlayerBoard() {
      return `
 <div class='player-board' id="player_board_config">
   <div id="player_config" class="player_board_content">

     <div class="player_config_row" id="round-counter-wrapper">
       ${_('Round')} <span id='round-counter'></span> / 4
     </div>
     <div class="player_config_row" id="round-phase"></div>
     <div class="player_config_row">
       <div id="help-mode-switch">
         <input type="checkbox" class="checkbox" id="help-mode-chk" />
         <label class="label" for="help-mode-chk">
           <div class="ball"></div>
         </label>

         <svg aria-hidden="true" focusable="false" data-prefix="fad" data-icon="question-circle" class="svg-inline--fa fa-question-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><g class="fa-group"><path class="fa-secondary" fill="currentColor" d="M256 8C119 8 8 119.08 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 422a46 46 0 1 1 46-46 46.05 46.05 0 0 1-46 46zm40-131.33V300a12 12 0 0 1-12 12h-56a12 12 0 0 1-12-12v-4c0-41.06 31.13-57.47 54.65-70.66 20.17-11.31 32.54-19 32.54-34 0-19.82-25.27-33-45.7-33-27.19 0-39.44 13.14-57.3 35.79a12 12 0 0 1-16.67 2.13L148.82 170a12 12 0 0 1-2.71-16.26C173.4 113 208.16 90 262.66 90c56.34 0 116.53 44 116.53 102 0 77-83.19 78.21-83.19 106.67z" opacity="0.4"></path><path class="fa-primary" fill="currentColor" d="M256 338a46 46 0 1 0 46 46 46 46 0 0 0-46-46zm6.66-248c-54.5 0-89.26 23-116.55 63.76a12 12 0 0 0 2.71 16.24l34.7 26.31a12 12 0 0 0 16.67-2.13c17.86-22.65 30.11-35.79 57.3-35.79 20.43 0 45.7 13.14 45.7 33 0 15-12.37 22.66-32.54 34C247.13 238.53 216 254.94 216 296v4a12 12 0 0 0 12 12h56a12 12 0 0 0 12-12v-1.33c0-28.46 83.19-29.67 83.19-106.67 0-58-60.19-102-116.53-102z"></path></g></svg>
       </div>

       <div id="show-settings">
         <svg  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
           <g>
             <path class="fa-secondary" fill="currentColor" d="M638.41 387a12.34 12.34 0 0 0-12.2-10.3h-16.5a86.33 86.33 0 0 0-15.9-27.4L602 335a12.42 12.42 0 0 0-2.8-15.7 110.5 110.5 0 0 0-32.1-18.6 12.36 12.36 0 0 0-15.1 5.4l-8.2 14.3a88.86 88.86 0 0 0-31.7 0l-8.2-14.3a12.36 12.36 0 0 0-15.1-5.4 111.83 111.83 0 0 0-32.1 18.6 12.3 12.3 0 0 0-2.8 15.7l8.2 14.3a86.33 86.33 0 0 0-15.9 27.4h-16.5a12.43 12.43 0 0 0-12.2 10.4 112.66 112.66 0 0 0 0 37.1 12.34 12.34 0 0 0 12.2 10.3h16.5a86.33 86.33 0 0 0 15.9 27.4l-8.2 14.3a12.42 12.42 0 0 0 2.8 15.7 110.5 110.5 0 0 0 32.1 18.6 12.36 12.36 0 0 0 15.1-5.4l8.2-14.3a88.86 88.86 0 0 0 31.7 0l8.2 14.3a12.36 12.36 0 0 0 15.1 5.4 111.83 111.83 0 0 0 32.1-18.6 12.3 12.3 0 0 0 2.8-15.7l-8.2-14.3a86.33 86.33 0 0 0 15.9-27.4h16.5a12.43 12.43 0 0 0 12.2-10.4 112.66 112.66 0 0 0 .01-37.1zm-136.8 44.9c-29.6-38.5 14.3-82.4 52.8-52.8 29.59 38.49-14.3 82.39-52.8 52.79zm136.8-343.8a12.34 12.34 0 0 0-12.2-10.3h-16.5a86.33 86.33 0 0 0-15.9-27.4l8.2-14.3a12.42 12.42 0 0 0-2.8-15.7 110.5 110.5 0 0 0-32.1-18.6A12.36 12.36 0 0 0 552 7.19l-8.2 14.3a88.86 88.86 0 0 0-31.7 0l-8.2-14.3a12.36 12.36 0 0 0-15.1-5.4 111.83 111.83 0 0 0-32.1 18.6 12.3 12.3 0 0 0-2.8 15.7l8.2 14.3a86.33 86.33 0 0 0-15.9 27.4h-16.5a12.43 12.43 0 0 0-12.2 10.4 112.66 112.66 0 0 0 0 37.1 12.34 12.34 0 0 0 12.2 10.3h16.5a86.33 86.33 0 0 0 15.9 27.4l-8.2 14.3a12.42 12.42 0 0 0 2.8 15.7 110.5 110.5 0 0 0 32.1 18.6 12.36 12.36 0 0 0 15.1-5.4l8.2-14.3a88.86 88.86 0 0 0 31.7 0l8.2 14.3a12.36 12.36 0 0 0 15.1 5.4 111.83 111.83 0 0 0 32.1-18.6 12.3 12.3 0 0 0 2.8-15.7l-8.2-14.3a86.33 86.33 0 0 0 15.9-27.4h16.5a12.43 12.43 0 0 0 12.2-10.4 112.66 112.66 0 0 0 .01-37.1zm-136.8 45c-29.6-38.5 14.3-82.5 52.8-52.8 29.59 38.49-14.3 82.39-52.8 52.79z" opacity="0.4"></path>
             <path class="fa-primary" fill="currentColor" d="M420 303.79L386.31 287a173.78 173.78 0 0 0 0-63.5l33.7-16.8c10.1-5.9 14-18.2 10-29.1-8.9-24.2-25.9-46.4-42.1-65.8a23.93 23.93 0 0 0-30.3-5.3l-29.1 16.8a173.66 173.66 0 0 0-54.9-31.7V58a24 24 0 0 0-20-23.6 228.06 228.06 0 0 0-76 .1A23.82 23.82 0 0 0 158 58v33.7a171.78 171.78 0 0 0-54.9 31.7L74 106.59a23.91 23.91 0 0 0-30.3 5.3c-16.2 19.4-33.3 41.6-42.2 65.8a23.84 23.84 0 0 0 10.5 29l33.3 16.9a173.24 173.24 0 0 0 0 63.4L12 303.79a24.13 24.13 0 0 0-10.5 29.1c8.9 24.1 26 46.3 42.2 65.7a23.93 23.93 0 0 0 30.3 5.3l29.1-16.7a173.66 173.66 0 0 0 54.9 31.7v33.6a24 24 0 0 0 20 23.6 224.88 224.88 0 0 0 75.9 0 23.93 23.93 0 0 0 19.7-23.6v-33.6a171.78 171.78 0 0 0 54.9-31.7l29.1 16.8a23.91 23.91 0 0 0 30.3-5.3c16.2-19.4 33.7-41.6 42.6-65.8a24 24 0 0 0-10.5-29.1zm-151.3 4.3c-77 59.2-164.9-28.7-105.7-105.7 77-59.2 164.91 28.7 105.71 105.7z"></path>
           </g>
         </svg>
       </div>
     </div>
     <div class="player_config_row" id="pending-gods"></div>
   </div>
 </div>
 `;
    },

    updatePlayerOrdering() {
      this.inherited(arguments);
      dojo.place('player_board_config', 'player_boards', 'first');
    },

    updateTurn() {
      $('game_play_area').dataset.step = this.gamedatas.turn;
      let round = parseInt(this.gamedatas.turn / 4) + 1;
      this._roundCounter.toValue(round);

      let turn = this.gamedatas.turn % 4;
      let msgs = {
        0: _('Scoring phase'),
        1: _('First turn'),
        2: _('Second turn'),
        3: _('Third turn'),
      };
      $('round-phase').innerHTML = msgs[turn];
    },

    notif_newTurn(n) {
      debug('Notif: starting a new turn', n);
      this.gamedatas.turn = n.args.step;
      this.updateTurn();
    },

    notif_newTurnScoring(n) {
      debug('Notif: starting a new turn', n);
      this.notif_newTurn(n);
    },
  });
});
