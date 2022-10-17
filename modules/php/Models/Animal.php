<?php
namespace ARK\Models;

class Animal extends ZooCard
{
  protected $type = \CARD_ANIMAL;
  protected $staticAttributes = [
    'type',
    'name',
    'latin',
    ['number', 'int'],
    ['cost', 'int'],
    ['appeal', 'int'],
    ['conservation', 'int'],
    ['reputation', 'int'],
    ['enclosureSize', 'int'],
    ['enclosureRequirements', 'obj'],
    ['specialEnclosure', 'obj'],
    ['categories', 'obj'],
    ['prerequisites', 'obj'],
    ['continents', 'obj'],
    ['ability', 'obj'],
  ];
}
