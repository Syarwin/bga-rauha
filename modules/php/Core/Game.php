<?php
namespace RAUHA\Core;
use rauha;

/*
 * Game: a wrapper over table object to allow more generic modules
 */
class Game
{
  public static function get()
  {
    return rauha::get();
  }
}
