<?php

namespace Drupal\Tests\snippet_manager\Kernel;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\filter\FilterPluginCollection;
use Drupal\KernelTests\KernelTestBase;
use Drupal\snippet_manager\Entity\Snippet;
use Symfony\Component\Debug\BufferingLogger;

/**
 * Tests snippet filter.
 *
 * @group snippet_manager
 */
class SnippetFilterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['snippet_manager', 'filter', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig(['filter']);
    $values = [
      'id' => 'super_foo',
      'template' => [
        'value' => 'Snippet content',
        'format' => filter_default_format(),
      ],
    ];
    Snippet::create($values)->save();
  }

  /**
   * Test callback.
   */
  public function testSnippetFilter() {
    $plugin_manager = \Drupal::service('plugin.manager.filter');
    $filter_collection = new FilterPluginCollection($plugin_manager);
    $filter = $filter_collection->get('snippet_manager_snippet');

    $renderer = $this->container->get('renderer');

    // Use prerender to ensure the snippet is rendered inside renderRoot().
    $build = [];
    $build['#pre_render'][] = function ($elements) use ($filter) {
      $elements['#markup'] = (string) $filter->process('<div>[snippet:super_foo]</div>', 'en');
      return $elements;
    };
    $output = $renderer->renderRoot($build);
    $this->assertEquals("<div><p>Snippet content</p>\n</div>", $output);

    // Mocked logger channel cannot be serialized when added to a container. So
    // we use BufferingLogger to verify the logged message later.
    $logger_channel = $this->container->get('logger.channel.snippet_manager');
    $logger = new BufferingLogger();
    $logger_channel->addLogger($logger);

    $build = [];
    $build['#pre_render'][] = function ($elements) use ($filter) {
      $elements['#markup'] = (string) $filter->process('<div>[snippet:bar]</div>', 'en');
      return $elements;
    };
    $output = $renderer->renderRoot($build);
    $this->assertEquals('<div></div>', $output);

    // Check logged entry.
    list($severity, $message, $context) = $logger->cleanLogs()[0];
    $this->assertEquals(RfcLogLevel::ERROR, $severity);
    $this->assertEquals('Could not render snippet: %snippet', $message);
    $this->assertEquals('bar', $context['%snippet']);

    // Test filter tips.
    $tips = 'Snippet tokens are replaced with their rendered values. The tokens should look like follows: <code>[snippet:example]</code> where <em>example</em> is a snippet ID.';
    $this->assertEquals($tips, $filter->tips());
  }

}
