<?php

namespace Drupal\Tests\domain_simple_sitemap\Functional;

use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests entity urls in domain sitemaps.
 *
 * @group domain_simple_sitemap
 */
class EntityUrlTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'domain_simple_sitemap',
    'simple_sitemap',
    'domain',
    'domain_access',
    'field',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create 3 domains.
    $this->domainCreateTestDomains(3);
  }

  /**
   * Verify entity links in sitemap.xml after generation.
   */
  public function testDomainSitemapEntityUrl() {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    $domains = \Drupal::service('entity_type.manager')
      ->getStorage('domain')
      ->loadMultiple();

    $nodes = [];

    foreach ($domains as $domain) {
      // Create an article node with access set only to current domain.
      $single_domain_node = $this->drupalCreateNode([
        'type' => 'article',
        DOMAIN_ACCESS_FIELD => [$domain->id()],
      ]);
      $this->assertTrue($node_storage->load($single_domain_node->id()),
        'Article node created.');
      $nodes[$domain->id()][] = $single_domain_node;
    }

    // Create an article node with access set to all domains.
    $all_domains_node = $this->drupalCreateNode([
      'type' => 'article',
      DOMAIN_ACCESS_FIELD => [$domain->id()],
      DOMAIN_ACCESS_ALL_FIELD => 1,
    ]);
    $this->assertTrue($node_storage->load($all_domains_node->id()),
      'Article node created.');
    foreach ($domains as $domain) {
      $nodes[$domain->id()][] = $all_domains_node;
    }

    // Set request domain for batch processing.
    \Drupal::service('domain.negotiator')
      ->setRequestDomain($domain->getHostname());

    // Sitemap generation.
    $generator = \Drupal::service('simple_sitemap.generator');
    $generator->setBundleSettings('node', 'article', [
      'index' => TRUE,
      'priority' => 0.5,
      'changefreq' => '',
      'include_images' => 0,
    ]);
    $generator->generateSitemap('nobatch');

    // Check links on sitemap for each domain.
    foreach ($domains as $domain) {
      $this->drupalGet($domain->getPath() . 'sitemap.xml');
      foreach ($nodes[$domain->id()] as $node) {
        $this->assertText($node->toUrl()
          ->setOption('base_url', $domain->getRawPath())
          ->toString());
      }
    }
  }

}
