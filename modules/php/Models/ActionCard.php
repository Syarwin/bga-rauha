<?php
namespace ARK\Models;
use ARK\Managers\Actions;
use ARK\Core\Engine;
use ARK\Helpers\Utils;

/*
 * Action Card
 */

class ActionCard extends \ARK\Helpers\DB_Model
{
  protected $staticAttributes = ['name', 'desc', 'tooltip'];
  protected $table = 'actioncards';
  protected $primary = 'card_id';

  protected $attributes = [
    'id' => ['card_id', 'int'],
    'strength' => ['card_location', 'int'],
    'pId' => ['player_id', 'int'],
    'extraDatas' => ['extra_datas', 'obj'],
    'type' => ['type', 'str'],
    'status' => ['card_state', 'int'],
    'level' => ['level', 'int'],
  ];

  public function getAction($ctx = null)
  {
    return Actions::get($this->type, $ctx);
  }

  public function getPlayableStrengths($player, $ignoreXTokens = false)
  {
    $maxStrength = $ignoreXTokens ? 10 : $this->getStrength() + $player->countXTokens();
    $strengths = [];
    for ($strength = $this->strength; $strength <= $maxStrength; $strength++) {
      if ($this->canBePlayed($player, $strength)) {
        $strengths[] = $strength;
      }
    }

    return $strengths;
  }

  public function canBePlayed($player, $strength = null)
  {
    $strength = $strength ?? $this->getStrength();
    return $this->getAction(['strength' => $strength, 'lvl' => $this->getLevel()])->isDoable($player);
  }

  public function getFlow($strength = null)
  {
    $strength = $strength ?? $this->getStrength();
    return [
      'action' => $this->type,
      'args' => [
        'strength' => $strength,
        'lvl' => $this->getLevel(),
      ],
    ];
  }

  public function getTaggedFlow($player, $strength = null)
  {
    // Add card context for listeners
    return Utils::tagTree($this->getFlow($strength), [
      'pId' => $player->getId(),
      'cardId' => $this->id,
    ]);
  }
}
