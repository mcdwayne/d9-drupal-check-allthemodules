<?php

namespace Drupal\Tests\rest_block_layout\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\simpletest\BrowserTestBase;

/**
 * Tests the Block Layout endpoint.
 *
 * @group rest_block_layout
 */
class BlockLayoutTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['rest_block_layout', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $config = $this->container->get('config.factory');
    $settings = $config->getEditable('rest.settings');
    $resources = $settings->get('resources');
    $resources['block_layout']['GET'] = [
      'supported_formats' => [
        'json',
        'xml',
      ],
      'supported_auth' => [
        'cookie',
      ],
    ];
    $settings->set('resources', $resources)->save();
    $this->resetAll();
  }

  /**
   * Tests the block layout endpoint.
   */
  public function testBlockLayout() {
    $block = $this->drupalPlaceBlock('system_main_block', [
      'region' => 'content',
    ]);
    $node = $this->drupalCreateNode();
    $user = $this->drupalCreateUser(['restful get block_layout']);
    $this->drupalLogin($user);
    $path = '/node/' . $node->id();
    $content = $this->drupalGet('/block-layout', [
      'query' => [
        '_format' => 'json',
        'path' => $path,
      ],
    ]);
    $assert = $this->assertSession();
    $assert->addressEquals('/block-layout?_format=json&path=' . urlencode($path));
    $assert->statusCodeEquals(200);

    $data = Json::decode($content);

    $this->assertInternalType('array', $data);

    $this->assertArrayHasKey('header', $data);
    $this->assertEmpty($data['header']);

    $this->assertArrayHasKey('primary_menu', $data);
    $this->assertEmpty($data['primary_menu']);

    $this->assertArrayHasKey('secondary_menu', $data);
    $this->assertEmpty($data['secondary_menu']);

    $this->assertArrayHasKey('highlighted', $data);
    $this->assertEmpty($data['highlighted']);

    $this->assertArrayHasKey('help', $data);
    $this->assertEmpty($data['help']);

    $this->assertArrayHasKey('content', $data);
    $this->assertArrayHasKey($block->id(), $data['content']);
    $this->assertInternalType('array', $data['content'][$block->id()]);
    $this->assertNotEmpty($data['content'][$block->id()]);

    $this->assertArrayHasKey('route_name', $data['content'][$block->id()]);
    $this->assertInternalType('string', $data['content'][$block->id()]['route_name']);
    $this->assertEqual('entity.node.canonical', $data['content'][$block->id()]['route_name']);

    $this->assertArrayHasKey('entity_type', $data['content'][$block->id()]);
    $this->assertInternalType('string', $data['content'][$block->id()]['entity_type']);
    $this->assertEqual('node', $data['content'][$block->id()]['entity_type']);

    $this->assertArrayHasKey('entity', $data['content'][$block->id()]);
    $this->assertInternalType('array', $data['content'][$block->id()]['entity']);
    $this->assertArrayHasKey('nid', $data['content'][$block->id()]['entity']);
    $this->assertInternalType('array', $data['content'][$block->id()]['entity']['nid']);
    $this->assertArrayHasKey(0, $data['content'][$block->id()]['entity']['nid']);
    $this->assertInternalType('array', $data['content'][$block->id()]['entity']['nid'][0]);
    $this->assertArrayHasKey('value', $data['content'][$block->id()]['entity']['nid'][0]);
    $this->assertInternalType('string', $data['content'][$block->id()]['entity']['nid'][0]['value']);
    $this->assertEqual($node->id(), $data['content'][$block->id()]['entity']['nid'][0]['value']);

    $this->assertArrayHasKey('sidebar_first', $data);
    $this->assertEmpty($data['sidebar_first']);

    $this->assertArrayHasKey('sidebar_second', $data);
    $this->assertEmpty($data['sidebar_second']);

    $this->assertArrayHasKey('footer', $data);
    $this->assertEmpty($data['footer']);

    $this->assertArrayHasKey('breadcrumb', $data);
    $this->assertEmpty($data['breadcrumb']);
  }

}
