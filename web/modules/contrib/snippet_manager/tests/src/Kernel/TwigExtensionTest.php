<?php

namespace Drupal\Tests\snippet_manager\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for the hook_requirements().
 *
 * @group snippet_manager
 */
class TwigExtensionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'snippet_manager',
    'snippet_manager_test',
    'filter',
    'file',
  ];

  /**
   * Test callback.
   */
  public function testTwigExtension() {

    $this->installConfig(['filter']);
    $this->installConfig(['snippet_manager_test']);

    $expected_output = "<article><h3>Hello world!</h3>\n<div>3 + 5 = <b>8</b></div></article>";

    $build = [
      '#type' => 'inline_template',
      '#template' => '<article>{{ snippet("alpha") }}</article>',
      '#context' => [],
    ];
    $actual_output = \Drupal::service('renderer')->renderRoot($build);

    $this->assertEquals($expected_output, $actual_output);

    // Override 'foo' variable in the "alpha" snippet.
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['config:snippet_manager.snippet.alpha']);
    $expected_output = "<article><h3>bar</h3>\n<div>3 + 5 = <b>8</b></div></article>";

    $build = [
      '#type' => 'inline_template',
      '#template' => '<article>{{ snippet("alpha", {foo: "bar"}) }}</article>',
      '#context' => [],
    ];
    $actual_output = \Drupal::service('renderer')->renderRoot($build);

    $this->assertEquals($expected_output, $actual_output);
  }

}
