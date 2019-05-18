<?php
// This file is for Bitbucket pipelines.

$file = 'composer.json';
$data = json_decode(file_get_contents($file), true);
$data["autoload-dev"]["psr-4"] = [
  "Drupal\\client_config_care\\Behat\\Context\\" => "web/modules/client_config_care/tests/src/Behat/context",
];
file_put_contents('composer.json', json_encode($data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
