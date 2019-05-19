<?php

namespace Drupal\tableau_dashboard;

use Drupal\Core\Config\ConfigFactoryInterface;
use Qstraza\TableauPHP\TableauPHP;

class TableauService implements TableauServiceInterface {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  public function getTableauObject() {
    $config = $this->configFactory->get('tableau_dashboard.settings');
    $tableau = new TableauPHP(
      $config->get('url'),
      $config->get('admin_user'),
      $config->get('admin_user_password'),
      $config->get('site_name')
    );
    $tableau->signIn();
    return $tableau;
  }

}
