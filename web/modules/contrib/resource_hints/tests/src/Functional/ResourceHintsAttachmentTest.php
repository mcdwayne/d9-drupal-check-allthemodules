<?php

namespace Drupal\Tests\resource_hints\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests resource hint output based on config.
 *
 * @group resource_hints
 */
class ResourceHintsAttachmentTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public $profile = 'minimal';

  /**
   * User.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'resource_hints',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'administer resource hints',
    ]);
  }

  /**
   * Test resource hints.
   */
  public function testResourceHints() {
    $this->drupalLogin($this->user);
    $assert = $this->assertSession();
    $this->drupalGet('admin/config/development/performance/resources-hints');

    // DNS Prefetch Header.
    $this->submitForm([
      'dns_prefetch_resources' => '//dns-prefetch.com',
    ], t('Save configuration'));
    $assert->responseHeaderContains('X-DNS-Prefetch-Control', 'on');
    $assert->responseHeaderContains('Link', '<//dns-prefetch.com>; rel="dns-prefetch"');
    $assert->elementNotExists('css', 'link[rel="dns-prefetch"][href="//dns-prefetch.com"]');

    // DNS Prefetch Element.
    $this->submitForm([
      'dns_prefetch_output' => '1',
    ], t('Save configuration'));
    $assert->elementExists('css', 'meta[http-equiv="x-dns-prefetch-control"][content="on"]');
    $assert->responseHeaderNotContains('Link', '<//dns-prefetch.com>; rel="dns-prefetch"');
    $assert->elementExists('css', 'link[rel="dns-prefetch"][href="//dns-prefetch.com"]');

    // X-DNS-Prefetch-Control off.
    $this->submitForm([
      'dns_prefetch_output' => '0',
      'dns_prefetch_control' => 'off',
    ], t('Save configuration'));
    $assert->responseHeaderContains('X-DNS-Prefetch-Control', 'off');
    $assert->responseHeaderNotContains('Link', '<//dns-prefetch.com>; rel="dns-prefetch"');
    $assert->elementNotExists('css', 'link[rel="dns-prefetch"][href="//dns-prefetch.com"]');

    // X-DNS-Prefetch-Control off.
    $this->submitForm([
      'dns_prefetch_output' => '1',
    ], t('Save configuration'));
    $assert->elementExists('css', 'meta[http-equiv="x-dns-prefetch-control"][content="off"]');
    $assert->responseHeaderNotContains('Link', '<//dns-prefetch.com>; rel="dns-prefetch"');
    $assert->elementNotExists('css', 'link[rel="dns-prefetch"][href="//dns-prefetch.com"]');

    // Preconnect Header.
    $this->submitForm([
      'preconnect_resources' => '//preconnect.com',
    ], t('Save configuration'));
    $assert->responseHeaderContains('Link', '<//preconnect.com>; rel="preconnect"');
    $assert->elementNotExists('css', 'link[rel="preconnect"][href="//preconnect.com"]');

    // Preconnect Element.
    $this->submitForm([
      'preconnect_output' => '1',
    ], t('Save configuration'));
    $assert->responseHeaderNotContains('Link', '<//preconnect.com>; rel="preconnect"');
    $assert->elementExists('css', 'link[rel="preconnect"][href="//preconnect.com"]');

    // Prefetch Header.
    $this->submitForm([
      'prefetch_resources' => '//prefetch.com',
    ], t('Save configuration'));
    $assert->responseHeaderContains('Link', '<//prefetch.com>; rel="prefetch"');
    $assert->elementNotExists('css', 'link[rel="prefetch"][href="//prefetch.com"]');

    // Prefetch Element.
    $this->submitForm([
      'prefetch_output' => '1',
    ], t('Save configuration'));
    $assert->responseHeaderNotContains('Link', '<//prefetch.com>; rel="prefetch"');
    $assert->elementExists('css', 'link[rel="prefetch"][href="//prefetch.com"]');

    // Prerender Header.
    $this->submitForm([
      'prerender_resources' => '//prerender.com',
    ], t('Save configuration'));
    $assert->responseHeaderContains('Link', '<//prerender.com>; rel="prerender"');
    $assert->elementNotExists('css', 'link[rel="prerender"][href="//prerender.com"]');

    // Prerender Element.
    $this->submitForm([
      'prerender_output' => '1',
    ], t('Save configuration'));
    $assert->responseHeaderNotContains('Link', '<//prerender.com>; rel="prerender"');
    $assert->elementExists('css', 'link[rel="prerender"][href="//prerender.com"]');
  }

}
