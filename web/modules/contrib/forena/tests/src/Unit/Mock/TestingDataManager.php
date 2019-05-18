<?php
/**
 * Implements TestingDataManager
 */

namespace Drupal\Tests\forena\Unit\Mock;

use Drupal\forena\Context\DataContext;
use Drupal\forena\DataManager;


class TestingDataManager extends DataManager  {

  /**
   * Create object for testing
   */
  public function __construct() {
    $path = dirname(dirname(dirname(dirname(__FILE__))));
    $this->repositories = [
      'test' => [
        'source' => "$path/data",
        'driver' => 'FrxFiles',
        'title' => 'Test Data',
        'access callback' => array($this, 'checkAccess'),
      ]
    ];

    $this->app = TestingAppService::instance();
    $this->app->data_directory = "$path/data_overriden";
    $this->dataSvc = new DataContext();
    $this->dataSvc->setContext('site', $this->app->siteContext);
  }


  /**
   * Allows access comments
   *
   * @param string $right
   *   The security object to test
   * @return bool
   *   Whether users should have access to the block.
   */
  public function checkaccess($right = '') {
    return boolval($right);
  }

}