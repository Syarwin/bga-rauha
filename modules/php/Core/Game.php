<?php
namespace RAUHA\Core;
use Rauha;

/*
 * Game: a wrapper over table object to allow more generic modules
 */
class Game
{
  public static function get()
  {
    return Rauha::get();
  }
}
