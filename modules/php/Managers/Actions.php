<?php
namespace ARK\Managers;
use ARK\Core\Game;

/* Class to manage all the cards for Agricola */

class Actions
{
  static $classes = [
    GAIN => 'Gain',
    // PAY => 'Pay',
    ACTIVATE_CARD => 'ActivateCard',
    SPECIAL_EFFECT => 'SpecialEffect',
    CHOOSE_ACTION_CARD => 'ChooseActionCard',

    // Action cards
    ANIMALS => 'Animals',
    ASSOCIATION => 'Association',
    PAY => 'Pay',
    BUILD => 'Build',
    CARDS => 'Cards',
    SPONSORS => 'Sponsor',

    // Animals powers

    // Other
    ADVANCE_BREAK => 'AdvanceBreak',
    CLEANUP => 'Cleanup',
    DISCARD => 'Discard',
  ];

  public static function get($actionId, $ctx = null)
  {
    if (!\array_key_exists($actionId, self::$classes)) {
      throw new \BgaVisibleSystemException('Trying to get an atomic action not defined in Actions.php : ' . $actionId);
    }
    $name = '\ARK\Actions\\' . self::$classes[$actionId];
    return new $name($ctx);
  }

  public static function getActionOfState($stateId, $throwErrorIfNone = true)
  {
    foreach (array_keys(self::$classes) as $actionId) {
      if (self::getState($actionId, null) == $stateId) {
        return $actionId;
      }
    }

    if ($throwErrorIfNone) {
      throw new \BgaVisibleSystemException('Trying to fetch args of a non-declared atomic action in state ' . $stateId);
    } else {
      return null;
    }
  }

  public static function isDoable($actionId, $ctx, $player, $ignoreResources = false)
  {
    $res = self::get($actionId, $ctx)->isDoable($player, $ignoreResources);
    // // Cards that bypass isDoable (eg Paper Maker)
    // $args = [
    //   'action' => $actionId,
    //   'ignoreResources' => $ignoreResources,
    //   'isDoable' => $res,
    //   'ctx' => $ctx,
    // ];
    // ActionCards::applyEffects($player, 'isDoable', $args);
    return $res;
  }

  public static function getErrorMessage($actionId)
  {
    $actionId = ucfirst(strtolower($actionId));
    $msg = sprintf(
      Game::get()::translate(
        'Attempting to take an action (%s) that is not possible. Either another card erroneously flagged this action as possible, or this action was possible until another card interfered.'
      ),
      $actionId
    );
    return $msg;
  }

  public static function getState($actionId, $ctx)
  {
    return self::get($actionId, $ctx)->getState();
  }

  public static function getArgs($actionId, $ctx)
  {
    $action = self::get($actionId, $ctx);
    $methodName = 'args' . self::$classes[$actionId];
    return array_merge($action->$methodName(), ['optionalAction' => $ctx->isOptional()]);
  }

  public static function takeAction($actionId, $actionName, $args, $ctx)
  {
    $player = Players::getActive();
    if (!self::isDoable($actionId, $ctx, $player)) {
      throw new \BgaUserException(self::getErrorMessage($actionId));
    }

    $action = self::get($actionId, $ctx);
    $methodName = $actionName; //'act' . self::$classes[$actionId];
    $action->$methodName(...$args);
  }

  public static function stAction($actionId, $ctx)
  {
    $player = Players::getActive();
    if (!self::isDoable($actionId, $ctx, $player)) {
      if (!$ctx->isOptional()) {
        if (self::isDoable($actionId, $ctx, $player, true)) {
          Game::get()->gamestate->jumpToState(ST_IMPOSSIBLE_MANDATORY_ACTION);
          return;
        } else {
          throw new \BgaUserException(self::getErrorMessage($actionId));
        }
      } else {
        // Auto pass if optional and not doable
        Game::get()->actPassOptionalAction(true);
        return;
      }
    }

    $action = self::get($actionId, $ctx);
    $methodName = 'st' . self::$classes[$actionId];
    if (\method_exists($action, $methodName)) {
      $action->$methodName();
    }
  }
}
