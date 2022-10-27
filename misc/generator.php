<?php

// Parse without sections
//$ini_array = parse_ini_file('biomes.ini', true);
$ini_array = parse_ini_file('starting_biomes.ini', true);

/*
['type', 'obj'], // array of string
['animal', 'obj'], //array of string
['laying_constraint', 'obj'], //array of coords
['laying_cost', 'int'], //in crystal
['crystal_income', 'int'],
['point_income', 'int'],
'multiplier', //string like "marine", "spore", "water_source", or "1"
['usage_cost', 'int'], //in crystal
['spore_income', 'int'],
['water_source', 'int'],
*/

$fp = fopen('biomes.php', 'w');
fwrite(
  $fp,
  '<?php
$f = function ($t) {
    return $t;
};


//prettier-ignore
$biomes = ['
);

foreach ($ini_array as $id => $data) {
  $bId = (int) $id; // + 99;
  $types = explode(',', $data['type']);
  $types_str = json_encode($types);

  $animals = ($data['animal'] ?? null) == '' ? [] : explode(',', $data['animal']);
  $animals_str = json_encode($animals);

  //   $constraints = ($data['laying_constraint'] ?? null) == '' ? [] : explode(',', $data['laying_constraint']);
  //   $constraints_str = json_encode($constraints);
  $constraints_str = ($data['laying_constraint'] ?? '') == '' ? '[]' : $data['laying_constraint'];

  $cost = (int) ($data['laying_cost'] ?? 0);
  $crystal_income = (int) $data['crystal_income'];
  $point_income = (int) $data['point_income'];
  $multiplier = $data['multiplier'];
  $usage_cost = (int) $data['usage_cost'];
  $spore_income = (int) $data['spore_income'];
  $water_source = (int) $data['water_source'];

  fwrite(
    $fp,
    "\n $bId => \$f([$types_str, $animals_str, $constraints_str, $cost, $crystal_income, $point_income, \"$multiplier\", $usage_cost, $spore_income, $water_source]),"
  );
}

fwrite($fp, "\n];");
fclose($fp);
?>
