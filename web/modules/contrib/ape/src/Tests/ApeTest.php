<?php
/**
 * @file
 *
 * Contains \Drupal\ape\Tests\ApeTest.
 */

namespace Drupal\ape\Tests;

use Drupal\simpletest\WebTestBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test cache-control header is set correctly after configuration.
 *
 * @group Advanced Page Expiration
 */
class ApeTest extends WebTestBase {

  protected $dumpHeaders = TRUE;

  /**
   * Modules to install
   */
  public static $modules = ['ape', 'ape_test', 'system'];

  /**
   * Exempt from strict schema checking.
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  public function initConfig(ContainerInterface $container) {
    parent::initConfig($container);

    $config = $container->get('config.factory');

    $config->getEditable('system.performance')
      ->set('cache.page.max_age', 2592000)
      ->save();
    $config->getEditable('ape.settings')
      ->set('alternatives', '/ape_alternative')
      ->set('exclusions', '/ape_exclude')
      ->set('lifetime.alternatives', 60)
      ->set('lifetime.301', 1800)
      ->set('lifetime.302', 600)
      ->set('lifetime.404', 3600)
      ->save();
  }

  public function testApeHeaders() {
    // Check user registration page has global age.
    $this->drupalGet('user/register');
    $this->assertEqual($this->drupalGetHeader('Cache-Control'), 'max-age=2592000, public', 'Global Cache-Control header set.');

    // Check homepage has alternative age.
    $this->drupalGet('/ape_alternative');
    $this->assertEqual($this->drupalGetHeader('Cache-Control'), 'max-age=60, public', 'Alternative Cache-Control header set.');

    // Check login page is excluded from caching.
    $this->drupalGet('/ape_exclude');
    $this->assertEqual($this->drupalGetHeader('Cache-Control'), 'must-revalidate, no-cache, private', 'Page successfully excluded from caching.');

    // Check that authenticated users bypass the cache.
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    $this->drupalGet('user');
    $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'), 'Caching was bypassed.');
    $this->assertEqual($this->drupalGetHeader('Cache-Control'), 'must-revalidate, no-cache, private', 'Cache-Control header was sent.');
    $this->drupalLogout();

    // Check that 403 responses have configured age.
    $this->drupalGet('admin/structure');
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertEqual($headers[0]['cache-control'], 'must-revalidate, no-cache, private', 'Forbidden page was not cached.');

    // Check that 404 responses have configured age.
    $this->drupalGet('notfindingthat');
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertEqual($headers[0]['cache-control'], 'max-age=3600, public', '404 Page Not Found Cache-Control header set.');

    // Check that 301 redirects work correctly.
    $this->drupalGet('ape_redirect_301');
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertEqual($headers[0]['cache-control'], 'max-age=1800, public', '301 redirect Cache-Control header set.');

    // Check that 302 redirects work correctly.
    $this->drupalGet('ape_redirect_302');
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertEqual($headers[0]['cache-control'], 'max-age=600, public', '302 redirect Cache-Control header set.');
  }
}
