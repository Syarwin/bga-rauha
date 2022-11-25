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
 * rauha.view.php
 *
 */

require_once APP_BASE_PATH . 'view/common/game.view.php';

class view_rauha_rauha extends game_view
{
  protected function getGameName()
  {
    // Used for translations and stuff. Please do not modify.
    return 'rauha';
  }

  function build_page($viewArgs)
  {
  }
}
