<?php

namespace Drupal\Tests\mask\Kernel;

/**
 * Tests hook_library_info_alter() implementation.
 *
 * @group mask
 */
class LibraryInfoAlterTest extends MaskKernelTest {

  /**
   * Libraries array to be altered.
   *
   * @var array
   */
  protected $libraries = [
    'mask_plugin' => [
      'remote' => 'https://igorescobar.github.io/jQuery-Mask-Plugin/',
      'license' => [
        'name' => 'MIT',
        'url' => 'https://github.com/igorescobar/jQuery-Mask-Plugin/blob/master/LICENSE',
        'gpl-compatible' => TRUE,
      ],
      'dependencies' => [
        'core/jquery',
      ],
    ],
    'mask' => [
      'version' => '1.x',
      'js' => [
        'js/mask.js' => [],
      ],
      'dependencies' => [
        'mask/mask_plugin',
        'core/jquery.once',
        'core/drupalSettings',
      ],
    ],
  ];

  /**
   * Tests implementation when using CDN.
   */
  public function testUseCdn() {
    $this->config->set('use_cdn', TRUE)
                 ->save();

    $libraries = $this->libraries;
    \mask_library_info_alter($libraries, 'mask');

    $this->assertCount(1, $libraries['mask_plugin']['js']);
    $this->assertArrayHasKey(MASK_PLUGIN_CDN_URL, $libraries['mask_plugin']['js']);
    $this->assertEquals('external', $libraries['mask_plugin']['js'][MASK_PLUGIN_CDN_URL]['type']);
    $this->assertTrue($libraries['mask_plugin']['js'][MASK_PLUGIN_CDN_URL]['minified']);
  }

  /**
   * Tests implementation when serving local file.
   */
  public function testLocalFile() {
    $plugin_path = 'public://jquery.mask.min.js';
    $this->config->set('use_cdn', FALSE)
                 ->set('plugin_path', $plugin_path)
                 ->save();

    $libraries = $this->libraries;
    \mask_library_info_alter($libraries, 'mask');

    $this->assertCount(1, $libraries['mask_plugin']['js']);
    $this->assertArrayHasKey($plugin_path, $libraries['mask_plugin']['js']);
    $this->assertEquals('file', $libraries['mask_plugin']['js'][$plugin_path]['type']);
    $this->assertTrue($libraries['mask_plugin']['js'][$plugin_path]['minified']);
  }

  /**
   * Tests that there are no alterations to libraries from other extensions.
   */
  public function testNotChangingOtherLibraries() {
    $libraries = [];
    \mask_library_info_alter($libraries, 'foo');

    $this->assertEmpty($libraries);
  }

}
