<?php

namespace Drupal\Tests\fac\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Class SearchServiceTest.
 *
 * @group fac
 */
class SearchServiceTest extends UnitTestCase {

  protected $container;
  public $searchService;

  /**
   * Sets up the Test class.
   */
  public function setup() {
    $this->container = new ContainerBuilder();
    // TODO: fill in.
  }

  /**
   * Tests the getResults() method of the SearchService.
   */
  public function testGetResults() {
    // TODO: fill in.
  }

  /**
   * Tests the renderResults() method of the SearchService.
   */
  public function testRenderResults() {
    // TODO: fill in.
  }

}
