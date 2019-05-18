<?php

namespace Drupal\Tests\cleaner\Kernel;

use Drupal\cleaner\Event\CleanerRunEvent;
use Drupal\cleaner\EventSubscriber\CleanerCacheClearEventSubscriber;
use Drupal\cleaner\EventSubscriber\CleanerMysqlOptimizeEventSubscriber;
use Drupal\cleaner\EventSubscriber\CleanerSessionClearEventSubscriber;
use Drupal\cleaner\EventSubscriber\CleanerTablesClearEventSubscriber;
use Drupal\cleaner\EventSubscriber\CleanerWatchdogClearEventSubscriber;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class CleanerKernelTests.
 *
 * @package Drupal\Test\cleaner\Kernel
 *
 * @group CleanerTests
 */
class CleanerKernelTests extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['cleaner'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig(['cleaner']);
  }

  /**
   * Test the initial config values right after module installation.
   */
  public function testInitialModuleConfig() {
    $config = $this->config('cleaner.settings');
    $this->assertEquals(0, $config->get('cleaner_cron'));
    $this->assertEquals(0, $config->get('cleaner_last_cron'));
    $this->assertEquals(FALSE, $config->get('cleaner_clear_cache'));
    $this->assertEquals('', $config->get('cleaner_additional_tables'));
    $this->assertEquals(FALSE, $config->get('cleaner_empty_watchdog'));
    $this->assertEquals(FALSE, $config->get('cleaner_clean_sessions'));
    $this->assertEquals(0, $config->get('cleaner_optimize_db'));
  }

  /**
   * Test that cleaner run event has all our subscribers.
   */
  public function testEventSubscribers() {
    try {
      $dispatcher = $this->container->get('event_dispatcher');
      $this->assertTrue($dispatcher->hasListeners(CleanerRunEvent::CLEANER_RUN));
      $classes = array_map(function ($subscriber) {
        return get_class($subscriber[0]);
      }, $dispatcher->getListeners(CleanerRunEvent::CLEANER_RUN));
      $this->assertContains(CleanerCacheClearEventSubscriber::class, $classes);
      $this->assertContains(CleanerMysqlOptimizeEventSubscriber::class, $classes);
      $this->assertContains(CleanerSessionClearEventSubscriber::class, $classes);
      $this->assertContains(CleanerTablesClearEventSubscriber::class, $classes);
      $this->assertContains(CleanerWatchdogClearEventSubscriber::class, $classes);
    }
    catch (\Exception $e) {
      $this->fail($e->getMessage());
    }
  }

  /**
   * Test clearing caches.
   */
  public function testClearingCaches() {
    try {
      $this->config('cleaner.settings')
        ->set('cleaner_clear_cache', TRUE)
        ->save();

      // Prepare cache backend service.
      $cache_backend = $this->container->get('cache.default');
      // Create cache.
      $cid = $this->randomString();
      $cache_backend->set($cid, $this->randomString());
      // Run subscriber's method.
      CleanerCacheClearEventSubscriber::create($this->container)
        ->clearCaches();
      // Check if cache entry has been removed.
      $this->assertFalse($cache_backend->get($cid));
    }
    catch (\Exception $e) {
      $this->fail($e->getMessage());
    }
  }

}
