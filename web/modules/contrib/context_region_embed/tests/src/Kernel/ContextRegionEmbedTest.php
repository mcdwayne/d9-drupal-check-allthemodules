<?php

namespace Drupal\Tests\context_region_embed\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the context region embed module.
 *
 * @group context_region_embed
 */
class ContextRegionEmbedTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'context',
    'context_region_embed',
    'user',
    'context_region_embed_test',
    'system',
  ];

  /**
   * {@inheritdoc}
   *
   * @todo Context doens't have schema defined yet.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('context_region_embed_test');
    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    $theme_installer = \Drupal::service('theme_installer');
    $theme_installer->install(['stark']);
    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('default', 'stark')
      ->save();
  }

  /**
   * Tests the render element.
   */
  public function testRender() {
    $result = \Drupal::service('context_region_embed.context_region_renderer')->render(['sidebar_first']);
    $this->assertEquals(['sidebar_first'], array_keys($result));
    $this->assertEquals(['system_powered_by_block'], array_keys($result['sidebar_first']));

    $this->render($result);
    $this->assertText('Powered by');
    $this->assertNoText("Who's new");
  }

}
