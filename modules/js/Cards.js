define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  return declare('arknova.cards', null, {
    setupCards() {
      this.gamedatas.cards.forEach((card) => {
        this.addZooCard(card);
      });
    },

    addZooCard(card, container = null) {
      if (container === null) {
        container = this.getCardContainer(card);
      }

      let o = this.place('tplZooCard', card, this.getCardContainer(card));
      if (o !== undefined) {
        this.addCustomTooltip(o.id, this.tplZooCard(card, true));
      }
    },

    getCardContainer(card) {
      if (card.location == 'hand') {
        return `hand-${card.pId}`;
      }
      return card.location;
    },

    tplZooCard(card, tooltip = false) {
      let uid = (tooltip ? 'tooltip_card-' : 'card-') + card.id;
      let req = card.enclosureRequirements;
      let enclosureCostClass = Object.keys(req).length > 0 ? 'wide' : '';
      let enclosures = [];
      if (card.enclosureSize > 0) {
        let types = ['', ...Array(req.Rock || 0).fill('rock'), ...Array(req.Water || 0).fill('water')];
        enclosures.push(
          `<div class="arknova-icon icon-enclosure-regular${types.join('-')}">${card.enclosureSize}</div>`,
        );
      }
      if (card.specialEnclosure.type) {
        enclosures.push(
          `<div class="arknova-icon icon-enclosure-special-${card.specialEnclosure.type}">${card.specialEnclosure.cubes}</div>`,
        );
        enclosureCostClass = 'wide';
      }

      let badges = [];
      card.categories.forEach((cat) =>
        badges.push(`<div class='zoo-card-badge'><div class='badge-icon' data-type='${cat}'></div></div>`),
      );
      card.continents.forEach((cat) =>
        badges.push(`<div class='zoo-card-badge'><div class='badge-icon' data-type='${cat}'></div></div>`),
      );

      let prerequisites = [];
      Object.keys(card.prerequisites).forEach((cat) => {
        // TODO : handle weird cases here
        for (let i = 0; i < card.prerequisites[cat]; i++) {
          prerequisites.push(`<div class='zoo-card-badge'><div class='badge-icon' data-type='${cat}'></div></div>`);
        }
      });

      let bonuses = [];
      ['reputation', 'conservation', 'appeal'].forEach((bonus) => {
        if (card[bonus] > 0) {
          bonuses.push(`<div class='zoo-card-bonus ${bonus}'>${card[bonus]}</div>`);
        }
      });

      return (
        `<div id="${uid}" data-id='${card.id}' class='ark-card zoo-card animal-card'>
      <div class='ark-card-wrapper'>
        <div class='ark-card-top'>
          <div class='ark-card-top-left'>
            <div class='animal-card-enclosure-cost ${enclosureCostClass}'>
              <div class='animal-card-enclosure'>${enclosures.join('')}</div>
              <div class='animal-card-cost'><div class='arknova-icon icon-money'>${card.cost}</div></div>
            </div>
            <div class='zoo-card-prerequisites'>${prerequisites.join('')}</div>
          </div>
          <div class='ark-card-top-right'>${badges.join('')}</div>
        </div>
        <div class='ark-card-middle'>
          <div class='ark-card-number'>${card.number}</div>
          <div class='ark-card-title-wrapper'>
            <div class='ark-card-title'>${_(card.name)}</div>
            <div class='ark-card-subtitle'>${_(card.latin)}</div>
          </div>
        </div>
        <div class='ark-card-bottom'>

          ` +
        (bonuses.length == 0
          ? ''
          : `<div class='zoo-card-bonuses' data-size='${bonuses.length}'>${bonuses.join('')}</div>`) +
        `
        </div>
      </div>
    </div>`
      );
    },

    notif_drawCards(n) {
      debug('drawing cards', n);
      // TODO
    },

    notif_snapCard(n) {
      debug('Snapping a card', n);
      // TODO
    },

    notif_discardCard(n) {
      debug('discarding cards', n);
      // TODO
    },

    notif_discardInfo(n) {
      debug('discarding card info', n);
      // TODO
    },

    notif_playAnimal(n) {
      debug('Playing a card', n);
      // TODO
    },
  });
});
