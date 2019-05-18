<?php

namespace Drupal\ape\Tests;

use Drupal\simpletest\WebTestBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test cache-control header is set correctly after minimal configuration.
 *
 * @group Advanced Page Expiration
 */
class ApeMinTest extends WebTestBase {

  protected $dumpHeaders = TRUE;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ape', 'ape_test', 'system'];

  /**
   * Exempt from strict schema checking.
   *
   * @var bool
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public function initConfig(ContainerInterface $container) {
    parent::initConfig($container);

    $config = $container->get('config.factory');

    $config->getEditable('system.performance')
      ->set('cache.page.max_age', 2592000)
      ->save();
    $config->getEditable('ape.settings')
      ->set('alternatives', '')
      ->set('exclusions', '')
      ->set('lifetime.alternatives', 60)
      ->set('lifetime.301', 1800)
      ->set('lifetime.302', 600)
      ->set('lifetime.404', 3600)
      ->save();
  }

  /**
   * Test that correct caching is applied.
   */
  public function testApeHeaders() {
    // Check user registration page has global age.
    $this->drupalGet('user/register');
    $this->assertEqual($this->drupalGetHeader('Cache-Control'), 'max-age=2592000, public', 'Global Cache-Control header set.');

  }

}
