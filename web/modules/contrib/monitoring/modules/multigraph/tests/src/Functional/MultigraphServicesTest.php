<?php
/**
 * @file
 * Contains \Drupal\monitoring_multigraph\Tests\MultigraphServicesTest.
 */

namespace Drupal\Tests\monitoring_multigraph\Functional;

use Drupal\Core\Url;
use Drupal\Tests\monitoring\Functional\MonitoringTestBase;

/**
 * Tests for REST services provided by Monitoring Multigraph.
 *
 * @group monitoring
 */
class MultigraphServicesTest extends MonitoringTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  public static $modules = [
    'dblog',
    'hal',
    'rest',
    'node',
    'basic_auth',
    'monitoring_multigraph',
  ];

  /**
   * User account to make REST requests.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $servicesAccount;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->servicesAccount = $this->drupalCreateUser([
      'restful get monitoring-multigraph',
    ]);
  }

  /**
   * Test multigraph API calls.
   */
  public function testMultigraph() {
    $this->drupalLogin($this->servicesAccount);

    $response_data = $this->doJsonRequest('monitoring-multigraph');
    $this->assertResponse(200);

    /** @var \Drupal\monitoring_multigraph\MultigraphInterface[] $multigraphs */
    $multigraphs = \Drupal::entityTypeManager()
      ->getStorage('monitoring_multigraph')
      ->loadMultiple();

    // Test the list of multigraphs.
    foreach ($multigraphs as $name => $multigraph) {
      $this->assertEqual($response_data[$name]['id'], $multigraph->id());
      $this->assertEqual($response_data[$name]['label'], $multigraph->label());
      $this->assertEqual($response_data[$name]['description'], $multigraph->getDescription());
      $this->assertEqual($response_data[$name]['sensors'], $multigraph->getSensorsRaw());
      $this->assertEqual($response_data[$name]['uri'], Url::fromRoute('rest.monitoring-multigraph.GET.json' , ['id' => $multigraph->id(), '_format' => 'json'])->setAbsolute()->toString());
    }

    // Test response for non-existing multigraph.
    $name = 'multigraph_that_does_not_exist';
    $this->doJsonRequest('monitoring-multigraph/' . $name);
    $this->assertResponse(404);

    // Test the predefined multigraph.
    $name = 'watchdog_severe_entries';
    $response_data = $this->doJsonRequest('monitoring-multigraph/' . $name);
    $this->assertResponse(200);
    $multigraph = $multigraphs[$name];
    $this->assertEqual($response_data['id'], $multigraph->id());
    $this->assertEqual($response_data['label'], $multigraph->label());
    $this->assertEqual($response_data['description'], $multigraph->getDescription());
    $this->assertEqual($response_data['sensors'], $multigraph->getSensorsRaw());
    $this->assertEqual($response_data['uri'], Url::fromRoute('rest.monitoring-multigraph.GET.json' , ['id' => $multigraph->id(), '_format' => 'json'])->setAbsolute()->toString());
  }

}
