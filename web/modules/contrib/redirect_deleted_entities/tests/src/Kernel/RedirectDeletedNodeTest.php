<?php

/**
 * @file
 * Contains \Drupal\Tests\redirect_deleted_entities\Kernel\RedirectDeletedNodeTest.
 */

namespace Drupal\Tests\redirect_deleted_entities\Kernel;

use Drupal\Core\Language\Language;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Ensures that redirects are created when nodes are deleted.
 *
 * @group redirect_deleted_entities
 *
 * @coversDefaultClass \Drupal\redirect_deleted_entities\RedirectManager
 */
class RedirectDeletedNodeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['redirect_deleted_entities', 'system', 'field', 'text', 'user', 'node', 'path', 'redirect', 'token', 'views', 'link'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setup();

    $this->installConfig(['redirect', 'redirect_deleted_entities', 'system', 'node']);

    $this->installEntitySchema('node');
    $this->installEntitySchema('redirect');

    $this->installSchema('node', ['node_access']);
    $this->installSchema('system', ['url_alias', 'sequences', 'router']);

    $type = NodeType::create(['type' => 'page']);
    $type->save();
    node_add_body_field($type);

    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * @covers ::getRedirectByEntity
   */
  public function testPatternLoadByEntity() {
    $this->config('redirect_deleted_entities.redirects')
      ->set('redirects.node.bundles.article.default', '/node')
      ->set('redirects.node.bundles.article.languages.en', '/node/en')
      ->set('redirects.node.bundles.page.default', '/page-list')
      ->save();

    $tests = [
      [
        'entity' => 'node',
        'bundle' => 'article',
        'language' => 'fi',
        'expected' => '/node',
      ],
      [
        'entity' => 'node',
        'bundle' => 'article',
        'language' => 'en',
        'expected' => '/node/en',
      ],
      [
        'entity' => 'node',
        'bundle' => 'article',
        'language' => Language::LANGCODE_NOT_SPECIFIED,
        'expected' => '/node',
      ],
      [
        'entity' => 'node',
        'bundle' => 'page',
        'language' => 'en',
        'expected' => '/page-list',
      ],
      [
        'entity' => 'invalid-entity',
        'bundle' => '',
        'language' => Language::LANGCODE_NOT_SPECIFIED,
        'expected' => '',
      ],
    ];
    foreach ($tests as $test) {
      $actual = \Drupal::service('redirect_deleted_entities.redirect_manager')->getRedirectByEntity($test['entity'], $test['bundle'], $test['language']);
      $this->assertSame($actual, $test['expected'], t("RedirectManager::getRedirectByEntity('@entity', '@bundle', '@language') returned '@actual', expected '@expected'", array(
        '@entity' => $test['entity'],
        '@bundle' => $test['bundle'],
        '@language' => $test['language'],
        '@actual' => $actual,
        '@expected' => $test['expected'],
      )));
    }
  }

}
