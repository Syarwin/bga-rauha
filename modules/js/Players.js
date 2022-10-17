define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const PLAYER_COUNTERS = ['appeal', 'reputation', 'conservation', 'money', 'handCount'];
  // prettier-ignore
  const ENCLOSURES_OFFSETS = {"back-1":{"x":2,"y":1},"back-2":{"x":2,"y":1},"back-3":{"x":2,"y":1},"back-4":{"x":2,"y":3},"back-5":{"x":5,"y":3},"front-1":{"x":2,"y":1},"front-2":{"x":2,"y":1},"front-3":{"x":2,"y":1},"front-4":{"x":2,"y":3},"front-5":{"x":5,"y":3},"front-LBA":{"x":5,"y":2},"front-PettingZoo":{"x":2,"y":2},"front-reptile":{"x":5,"y":2},"kiosk":{"x":2,"y":1},"monkey":{"x":5,"y":2},"meerkat":{"x":5,"y":1},"owl":{"x":2,"y":3},"sea-turtle":{"x":5,"y":3},"okapi":{"x":8,"y":1},"adventure":{"x":2,"y":1},"pavilion":{"x":2,"y":1},"penguin":{"x":5,"y":2},"aquarium":{"x":5,"y":1},"polar-bear":{"x":5,"y":1},"hyena":{"x":2,"y":2},"zoo-school":{"x":5,"y":1},"baboon":{"x":5,"y":3},"water-playground":{"x":2,"y":1},"entrance":{"x":2,"y":1},"cable":{"x":2,"y":3}};

  return declare('arknova.players', null, {
    getPlayers() {
      return Object.values(this.gamedatas.players);
    },

    setupPlayers() {
      // Change No so that it fits the current player order view
      let currentNo = this.getPlayers().reduce((carry, player) => (player.id == this.player_id ? player.no : carry), 0);
      let nPlayers = Object.keys(this.gamedatas.players).length;
      this.forEachPlayer((player) => (player.order = (player.no + nPlayers - currentNo) % nPlayers));
      let orderedPlayers = Object.values(this.gamedatas.players).sort((a, b) => a.order - b.order);

      // Add player board and player panel
      orderedPlayers.forEach((player) => {
        this.place('tplPlayerBoard', player, 'player-boards');
        this.place('tplPlayerPanel', player, `overall_player_board_${player.id}`);

        if (player.id == this.player_id) {
          player.hand.forEach((card) => {
            this.addZooCard(card);
          });
        }
      });
      this.setupPlayersCounters();
      this.setupActionCards();
    },

    setupActionCards() {
      this.getPlayers().forEach((player) => {
        player.actionCards.forEach((card) => {
          let o = this.place('tplActionCard', card, `action-card-slot-${player.id}-${card.strength}`);
          this.addCustomTooltip(o.id, this.tplActionCard(card, true), null, false);
        });
      });
    },

    tplPlayerBoard(player) {
      // Create cells
      let zooMap = `<div class='zoo-map' id='zoo-map-${player.id}' data-map='${player.mapId}'>`;
      let dim = { x: 9, y: 7 };
      for (let x = 0; x < dim.x; x++) {
        let size = dim.y - (x % 2 == 0 ? 1 : 0);
        for (let y = 0; y < size; y++) {
          let row = 2 * y + (x % 2 == 0 ? 1 : 0);
          let style = `grid-row: ${row + 1} / span 2; grid-column: ${3 * x + 1} / span 4`;

          let uid = x + '_' + row;
          let className = '';
          if (player.map.terrains.Rock.includes(uid)) {
            className += ' rock';
          }
          if (player.map.terrains.Water.includes(uid)) {
            className += ' water';
          }
          if (player.map.upgradeNeeded.includes(uid)) {
            className += ' upgradeNeeded';
          }
          zooMap += `<div class='zoo-map-cell${className}' style='${style}' data-x='${x}' data-y='${row}'>${x}_${row}</div>`;
          // <div class='zoo-map-cell-content' id="cell-${player.id}-${x}-${y}"></div>
        }
      }
      zooMap += '</div>';

      return (
        `<div class='ark-player-board' id='player-board-${player.id}'>
        <div class='player-board-name' style='color:#${player.color}'>${player.name}</div>
        <div class='player-board-zoo'>
          ${zooMap}
        </div>
        <div class='player-board-action-cards' id='action-cards-${player.id}'>
          <div class='action-card-slot' id='action-card-slot-${player.id}-1'></div>
          <div class='action-card-slot' id='action-card-slot-${player.id}-2'></div>
          <div class='action-card-slot' id='action-card-slot-${player.id}-3'></div>
          <div class='action-card-slot' id='action-card-slot-${player.id}-4'></div>
          <div class='action-card-slot' id='action-card-slot-${player.id}-5'></div>
        </div>
        ` +
        (player.id == this.player_id ? `<div class='player-board-hand' id='hand-${player.id}'></div>` : '') +
        `
      </div>`
      );
    },

    tplActionCard(card, tooltip = false) {
      let uid = (tooltip ? 'tooltip_action-card-' : 'action-card-') + card.id;
      return `<div id="${uid}" data-id='${card.id}' data-type="${card.type}" class='ark-card action-card'>
      <div class='action-card-perspective'>
        <div class='ark-card-wrapper recto'>
          <div class='ark-card-top'>
            <div class='ark-card-top-left'>
              <div class='arknova-icon icon-action-${card.type.toLowerCase()}'></div>
              I
            </div>
          </div>
          <div class='ark-card-middle'>
            <div class='ark-card-title-wrapper'>
              <div class='ark-card-title'>${_(card.name)}</div>
            </div>
          </div>
          <div class='ark-card-bottom'>
            ${card.desc.map((t) => _(t)).join('<br/>')}
          </div>
        </div>
        <div class='ark-card-wrapper verso'>
          <div class='ark-card-top'>
            <div class='ark-card-top-left'>
              <div class='arknova-icon icon-action-${card.type.toLowerCase()}'></div>
              II
            </div>
          </div>
          <div class='ark-card-middle'>
            <div class='ark-card-title-wrapper'>
              <div class='ark-card-title'>${_(card.name)}</div>
            </div>
          </div>
          <div class='ark-card-bottom'>
            TODO
          </div>
        </div>
      </div>
    </div>`;
    },

    tplPlayerPanel(player) {
      return `<div class='player-info'>
        <div class="arknova-icon icon-money" id="counter-${player.id}-money"></div>
        <div class="arknova-icon icon-reputation" id="counter-${player.id}-reputation"></div>
        <div class="arknova-icon icon-conservation" id="counter-${player.id}-conservation"></div>
        <div class="arknova-icon icon-appeal" id="counter-${player.id}-appeal"></div>

        <div class="player-handCount" id="counter-${player.id}-handCount"></div>
        <div class="player-handCount-icon"></div>
      </div>`;
    },

    notif_actionCardCleanup(n) {
      debug('Action cards cleanup', n);
      // TODO
    },

    ////////////////////////////////////////////////////
    //   ____                  _
    //  / ___|___  _   _ _ __ | |_ ___ _ __ ___
    // | |   / _ \| | | | '_ \| __/ _ \ '__/ __|
    // | |__| (_) | |_| | | | | ||  __/ |  \__ \
    //  \____\___/ \__,_|_| |_|\__\___|_|  |___/
    //
    ////////////////////////////////////////////////////
    /**
     * Create all the counters for player panels
     */
    setupPlayersCounters() {
      this._playerCounters = {};
      this._scoreCounters = {};
      this.forEachPlayer((player) => {
        this._playerCounters[player.id] = {};
        PLAYER_COUNTERS.forEach((res) => {
          this._playerCounters[player.id][res] = this.createCounter(`counter-${player.id}-${res}`);
        });
        this._scoreCounters[player.id] = this.createCounter('player_score_' + player.id);
      });
      this.updatePlayersCounters(false);
    },

    /**
     * Update all the counters in player panels according to gamedatas
     */
    updatePlayersCounters(anim = true) {
      this.forEachPlayer((player) => {
        PLAYER_COUNTERS.forEach((res) => {
          let value = player[res];
          this._playerCounters[player.id][res].goTo(value, anim);
          // dojo.attr(reserve.parentNode, 'data-n', value);
        });
      });
    },

    notif_incAppeal(n) {
      debug('Increasing appeal', n);
      // TODO
    },

    notif_incReputation(n) {
      debug('Increasing reputation', n);
      // TODO
    },
  });
});
