<?php
namespace RAUHA;
use RAUHA\Core\Globals;
use RAUHA\Core\Game;
use RAUHA\Core\Notifications;
use RAUHA\Managers\Players;
use RAUHA\Managers\BiomeCards;
use RAUHA\Helpers\Utils;

trait DebugTrait
{
  function test()
  {
    $players = self::loadPlayersBasicInfos();
    $options = [
      \OPTION_BOARD_SIDE => \OPTION_A_SIDE,
    ];

    BiomeCards::setupNewGame($players, $options);
  }
}
