<?php

namespace Drupal\Tests\inmail\Kernel;

use Drupal\inmail\Entity\DelivererConfig;
use Drupal\inmail\Plugin\inmail\Deliverer\FetcherInterface;
use Drupal\inmail\Tests\DelivererTestTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests mail deliverers.
 *
 * @group inmail
 */
class DelivererTest extends KernelTestBase {

  use DelivererTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['inmail', 'inmail_test', 'user'];

  /**
   * Test that Cron runs trigger fetchers.
   *
   * Also tests processed tracking and disabling.
   *
   * @see inmail_cron()
   * @see \Drupal\inmail_test\Plugin\inmail\Deliverer\TestDeliverer
   */
  public function testFetcherCronInvocation() {
    // Setup fetcher.
    $id = $this->randomMachineName();
    $deliverer_config = DelivererConfig::create(
      array(
        'id' => $id,
        'plugin' => 'test_fetcher',
      ));
    $deliverer_config->setConfiguration(['config_id' => $id]);
    $deliverer_config->save();

    /** @var FetcherInterface $plugin */
    $plugin = $deliverer_config->getPluginInstance();

    // Check numbers after update.
    $plugin->update();
    $plugin = DelivererConfig::load($deliverer_config->id())->getPluginInstance();
    $this->assertEquals(250, $plugin->getTotalCount());
    $this->assertEquals(100, $plugin->getUnprocessedCount());
    $this->assertEquals(null, $plugin->getProcessedCount());

    // Cron should trigger the fetcher.
    /** @var \Drupal\Core\CronInterface $cron */
    $cron = \Drupal::service('cron');
    $cron->run();
    $plugin = DelivererConfig::load($deliverer_config->id())->getPluginInstance();
    $this->assertEquals(200, $plugin->getTotalCount());
    $this->assertEquals(99, $plugin->getUnprocessedCount());
    $this->assertEquals(1, $plugin->getProcessedCount());

    // Rerun and see it update.
    $cron->run();
    $plugin = DelivererConfig::load($deliverer_config->id())->getPluginInstance();
    $this->assertEquals(200, $plugin->getTotalCount());
    $this->assertEquals(98, $plugin->getUnprocessedCount());
    $this->assertEquals(2, $plugin->getProcessedCount());

    // Disable deliverer and assert that it is not triggered.
    $deliverer_config->disable()->save();
    $cron->run();
    $plugin = DelivererConfig::load($deliverer_config->id())->getPluginInstance();
    $this->assertEquals(200, $plugin->getTotalCount());
    $this->assertEquals(98, $plugin->getUnprocessedCount());
    $this->assertEquals(2, $plugin->getProcessedCount());

    $plugin->update();
    $plugin = DelivererConfig::load($deliverer_config->id())->getPluginInstance();
    $this->assertEquals(250, $plugin->getTotalCount());
    $this->assertEquals(98, $plugin->getUnprocessedCount());
    $this->assertEquals(2, $plugin->getProcessedCount());
  }

}
