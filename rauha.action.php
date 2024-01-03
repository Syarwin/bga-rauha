<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Rauha implementation : Timothée Pecatte <tim.pecatte@gmail.com>, Emmanuel Albisser <emmanuel.albisser@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 *
 * rauha.action.php
 *
 * Rauha main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/rauha/rauha/myAction.html", ...)
 *
 */

class action_rauha extends APP_GameAction
{
  // Constructor: please do not modify
  public function __default()
  {
    if (self::isArg('notifwindow')) {
      $this->view = 'common_notifwindow';
      $this->viewArgs['table'] = self::getArg('table', AT_posint, true);
    } else {
      $this->view = 'rauha_rauha';
      self::trace('Complete reinitialization of board game');
    }
  }

  public function actChooseShaman()
  {
    self::setAjaxMode();
    $sideId = self::getArg('sideId', AT_posint, true);
    $this->game->actChooseShaman($sideId);
    self::ajaxResponse();
  }


  public function actChooseBiome()
  {
    self::setAjaxMode();
    $biomeId = self::getArg('biomeId', AT_posint, true);
    $this->game->actChooseBiome($biomeId);
    self::ajaxResponse();
  }

  public function actDiscardCrystals()
  {
    self::setAjaxMode();
    $this->game->actDiscardCrystals();
    self::ajaxResponse();
  }

  public function actDiscardSpore()
  {
    self::setAjaxMode();
    $x = (int) self::getArg('x', AT_posint, true);
    $y = (int) self::getArg('y', AT_posint, true);
    $this->game->actDiscardSpore($x, $y);
    self::ajaxResponse();
  }

  public function actPlaceBiome()
  {
    self::setAjaxMode();
    $x = (int) self::getArg('x', AT_posint, true);
    $y = (int) self::getArg('y', AT_posint, true);
    $this->game->actPlaceBiome($x, $y);
    self::ajaxResponse();
  }

  public function actSkip()
  {
    self::setAjaxMode();
    $this->game->actSkip();
    self::ajaxResponse();
  }

  public function actActivateBiome()
  {
    self::setAjaxMode();
    $biomeId = self::getArg('biomeId', AT_posint, true);
    $x = (int) self::getArg('x', AT_posint, false, -1); //$x and $y for spore placing
    $y = (int) self::getArg('y', AT_posint, false, -1);
    $this->game->actActivateElement($biomeId, false, null, $x, $y);
    self::ajaxResponse();
  }

  public function actActivateGod()
  {
    self::setAjaxMode();
    $godId = self::getArg('godId', AT_posint, true);
    $x = (int) self::getArg('x', AT_posint, false, -1); //$x and $y for spore placing
    $y = (int) self::getArg('y', AT_posint, false, -1);
    $this->game->actActivateElement($godId, true, null, $x, $y);
    self::ajaxResponse();
  }

  public function actActivateShaman()
  {
    self::setAjaxMode();
    $playerId = self::getArg('playerId', AT_posint, true);
    $this->game->actActivateElement($playerId, false);
    self::ajaxResponse();
  }

  ///////////////////
  /////  PREFS  /////
  ///////////////////

  public function actChangePref()
  {
    self::setAjaxMode();
    $pref = self::getArg('pref', AT_posint, false);
    $value = self::getArg('value', AT_posint, false);
    $this->game->actChangePreference($pref, $value);
    self::ajaxResponse();
  }

  //////////////////
  ///// UTILS  /////
  //////////////////
  public function validateJSonAlphaNum($value, $argName = 'unknown')
  {
    if (is_array($value)) {
      foreach ($value as $key => $v) {
        $this->validateJSonAlphaNum($key, $argName);
        $this->validateJSonAlphaNum($v, $argName);
      }
      return true;
    }
    if (is_int($value)) {
      return true;
    }
    $bValid = preg_match('/^[_0-9a-zA-Z- ]*$/', $value) === 1;
    if (!$bValid) {
      throw new feException("Bad value for: $argName", true, true, FEX_bad_input_argument);
    }
    return true;
  }

  public function loadBugSQL()
  {
    self::setAjaxMode();
    $reportId = (int) self::getArg('report_id', AT_int, true);
    $this->game->loadBugSQL($reportId);
    self::ajaxResponse();
  }
}
