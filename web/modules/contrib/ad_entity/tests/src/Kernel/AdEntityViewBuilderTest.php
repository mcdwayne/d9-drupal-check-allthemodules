<?php

namespace Drupal\Tests\ad_entity\Kernel;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\ad_entity\Traits\AdEntityKernelTrait;

/**
 * Tests the view builder for Advertising entities.
 *
 * @coversDefaultClass \Drupal\ad_entity\AdEntityViewBuilder
 * @group ad_entity
 */
class AdEntityViewBuilderTest extends EntityKernelTestBase {

  use AdEntityKernelTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'ad_entity',
    'ad_entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['ad_entity']);
  }

  /**
   * Test the output of the view builder.
   *
   * @covers ::view
   */
  public function testOutput() {
    $ad_entity = $this->createNewAdEntity();
    $view_builder = $this->getAdEntityViewBuilder();
    $view = $view_builder->view($ad_entity);
    $context_manager = $this->getContextManager();
    // Add a targeting context.
    $targeting_data = ['targeting' => ['testkey' => 'testval']];
    $context_manager->addContextData('targeting', $targeting_data);

    // There must be caching, but not with its own key.
    $this->assertArrayHasKey('#cache', $view);
    if (isset($view['#cache'])) {
      $this->assertArrayNotHasKey('keys', $view['#cache']);
      $this->assertArrayHasKey('tags', $view['#cache']);
      $this->assertArrayHasKey('contexts', $view['#cache']);
      $this->assertArrayHasKey('max-age', $view['#cache']);
    }

    $rendered = $this->getRenderer()->renderRoot($view);
    // There must be content.
    $this->assertNotEmpty($rendered);
    $this->assertTrue($rendered instanceof MarkupInterface);
    $rendered = trim((string) $rendered);

    // Ensure that important attributes exist.
    $this->assertStringStartsWith('<div id="ad-entity-', $rendered);
    $this->assertContains('ad-entity-container', $rendered);
    $this->assertContains('not-initialized', $rendered);
    $this->assertContains('data-ad-entity="test_entity"', $rendered);
    $this->assertContains('data-ad-entity-type="test_type"', $rendered);
    $this->assertContains('data-ad-entity-view="test_view"', $rendered);
    $this->assertContains('data-ad-entity-variant=\'["any"]\'', $rendered);
    $this->assertContains('data-ad-entity-targeting=\'{"testkey":"testval"}\'', $rendered);
    $this->assertContains('<div class="ad-entity-test-view"></div>', $rendered);

    // Double-rendering should not be an equal result,
    // because the id must be different.
    $view = $view_builder->view($ad_entity);
    $rendered2 = $this->getRenderer()->renderRoot($view);
    $rendered2 = trim((string) $rendered2);
    $this->assertNotEquals($rendered, $rendered2);
    $container = Html::load($rendered)->getElementsByTagName('div')->item(0);
    $container2 = Html::load($rendered2)->getElementsByTagName('div')->item(0);
    $this->assertNotEquals($container->getAttribute('id'), $container2->getAttribute('id'));
  }

}
