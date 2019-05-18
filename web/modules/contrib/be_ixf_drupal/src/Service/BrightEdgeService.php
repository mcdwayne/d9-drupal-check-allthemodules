<?php

namespace Drupal\brightedge\Service;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;

class BrightedgeService implements BrightedgeServiceInterface {

  protected $config;
  protected $database;
  protected $languageManager;

  public function __construct($config, Connection $database, LanguageManagerInterface $languageManager) {
    $this->config = $config->get('be_ixf_drupal.settings');
    $this->languageConfig = $config->get('be_ixf_drupal.locales');
    $this->database = $database;
    $this->languageManager = $languageManager;
  }

}
?>

