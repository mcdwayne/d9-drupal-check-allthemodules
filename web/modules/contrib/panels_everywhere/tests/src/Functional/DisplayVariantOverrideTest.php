<?php

namespace Drupal\Tests\panels_everywhere\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\page_manager\Entity\PageVariant;
use Drupal\Tests\BrowserTestBase;

/**
 * Make sure the route override behaviour works as intended.
 *
 * @group panels_everywhere
 */
class DisplayVariantOverrideTest extends PanelsEverywhereBrowserTestBase {

  /**
   * A test node.
   *
   * @var NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // remove all placed block to more easily test placements
    $block_ids = \Drupal::entityQuery('block')
      ->condition('theme', 'bartik')
      ->execute();

    $blocks = \Drupal::entityTypeManager()->getStorage('block')
      ->loadMultiple($block_ids);
    foreach ($blocks as $block) {
      if ($block->getPluginId() == 'system_main_block') {
        continue;
      }
      $block->delete();
    }

    $this->node = Node::create(['type' => 'article']);
    $this->node->setTitle('This is a node');
    $this->node->body->value = 'This is some text.';
    $this->node->save();
  }

  /**
   * Verify disabling override has no effect on rendering of the site_template.
   */
  public function testDisabledRouteOverrideForSiteTemplateCausesNodeToNotBeRendered() {
    $site_template = $this->pageStorage->load('site_template');
    $site_template->setStatus(TRUE);
    $site_template->save();


    $defaultVariant = $site_template->getVariant('panels_everywhere');
    $this->placeBlockOnVariant($defaultVariant, 'system_main_block', 'content');
    $this->placeBlockOnVariant($defaultVariant, 'system_powered_by_block', 'content');
    $defaultVariant->save();
    // @todo: Remove once cache info is setup correctly
    drupal_flush_all_caches();

    $this->drupalGet('node/'.$this->node->id());

    $this->assertSession()->pageTextContains('Powered by');
    $this->assertSession()->pageTextContains($this->node->body->value);
  }

  /**
   * Verify enabling override has no effect on rendering of the site_template.
   */
  public function testEnabledRouteOverrideForSiteTemplateCausesNodeToNotBeRendered() {
    $site_template = $this->pageStorage->load('site_template');
    $site_template->setStatus(TRUE);
    $site_template->save();


    $defaultVariant = $site_template->getVariant('panels_everywhere');
    $defaultVariant->set('route_override_enabled', TRUE);
    $this->placeBlockOnVariant($defaultVariant, 'system_main_block', 'content');
    $this->placeBlockOnVariant($defaultVariant, 'system_powered_by_block', 'content');
    $defaultVariant->save();
    // @todo: Remove once cache info is setup correctly
    drupal_flush_all_caches();

    $this->drupalGet('node/'.$this->node->id());

    $this->assertSession()->pageTextContains('Powered by');
    $this->assertSession()->pageTextContains($this->node->body->value);
  }

  /**
   * Verify that site_template & panels can be rendered on the same page.
   */
  public function testSiteTemplateAndPanelsCanBeRenderedOnTheSamePage() {
    // set up panels everywhere
    $site_template = $this->pageStorage->load('site_template');
    $site_template->setStatus(TRUE);
    $site_template->save();

    $panelsEverywhereVariant = $site_template->getVariant('panels_everywhere');
    $this->placeBlockOnVariant($panelsEverywhereVariant, 'system_main_block', 'content');
    $this->placeBlockOnVariant($panelsEverywhereVariant, 'system_powered_by_block', 'content');
    $panelsEverywhereVariant->save();

    // set up panels
    $node_view = $this->pageStorage->load('node_view');
    $panelsVariant = $this->pageVariantStorage->create([
      'id' => 'this-is-a-panels-variant',
      'variant' => 'panels_variant',
      'variant_settings' => [
        'id' => 'panels_variant',
        'layout' => 'layout_onecol',
        'builder' => 'standard',
      ],
    ]);
    $panelsVariant->setPageEntity($node_view);
    $this->placeBlockOnVariant($panelsVariant, 'entity_view:node', 'content', [
      'view_mode' => 'full',
      'context_mapping' => [
        'entity' => 'node'
      ]
    ]);
    $this->placeBlockOnVariant($panelsVariant, 'views_block:who_s_new-block_1', 'content', [
      'label_display' => 'visible',
      'items_per_page' => 'none'
    ]);
    $panelsVariant->save();
    // @todo: Remove once cache info is setup correctly
    drupal_flush_all_caches();


    $this->drupalGet('node/'.$this->node->id());

    $this->assertSession()->pageTextContains('Powered by');
    $this->assertSession()->pageTextContains($this->node->body->value);
    $this->assertSession()->pageTextContains("Who's new");
  }

  /**
   * Verify that disabling route overrides work.
   */
  public function testDisabledRouteOverrideForNodeViewCausesNodeToBeRenderedNormally() {
    $page = $this->pageStorage->load('node_view');

    $customVariant = $this->pageVariantStorage->create([
      'id' => 'this-is-a-custom-variant',
      'variant' => 'panels_everywhere_variant',
      'variant_settings' => [
        'id' => 'panels_everywhere_variant',
        'layout' => 'layout_onecol',
        'builder' => 'standard',
        'route_override_enabled' => FALSE
      ],
    ]);
    $customVariant->setPageEntity($page);
    $this->placeBlockOnVariant($customVariant, 'system_main_block', 'content');
    $this->placeBlockOnVariant($customVariant, 'system_powered_by_block', 'content');
    $customVariant->save();
    // @todo: Remove once cache info is setup correctly
    drupal_flush_all_caches();


    $this->drupalGet('node/'.$this->node->id());

    $this->assertSession()->pageTextContains('Powered by');
    $this->assertSession()->pageTextContains($this->node->body->value);
  }

  /**
   * Verify that enabling route overrides work.
   */
  public function testEnabledRouteOverrideForNodeViewWithoutOtherVariantPresentWillCausesNodeToNotBeRendered() {
    $page = $this->pageStorage->load('node_view');

    $customVariant = $this->pageVariantStorage->create([
      'id' => 'this-is-a-custom-variant',
      'variant' => 'panels_everywhere_variant',
      'variant_settings' => [
        'id' => 'panels_everywhere_variant',
        'layout' => 'layout_onecol',
        'builder' => 'standard',
        'route_override_enabled' => TRUE
      ],
    ]);
    $customVariant->setPageEntity($page);
    $this->placeBlockOnVariant($customVariant, 'system_main_block', 'content');
    $this->placeBlockOnVariant($customVariant, 'system_powered_by_block', 'content');
    $customVariant->save();
    // @todo: Remove once cache info is setup correctly
    drupal_flush_all_caches();


    $this->drupalGet('node/'.$this->node->id());

    $this->assertSession()->pageTextContains('Powered by');
    $this->assertSession()->pageTextNotContains($this->node->body->value);
  }

  /**
   * Verify that disabled overrides work will allow panels to render node.
   */
  public function testDisabledRouteOverrideForNodeViewWithPanelsVariantPresentWillCausesNodeToBeRendered() {
    $page = $this->pageStorage->load('node_view');

    // set up panels everywhere
    $panelsEverywhereVariant = $this->pageVariantStorage->create([
      'id' => 'this-is-a-panels_everywhere-variant',
      'variant' => 'panels_everywhere_variant',
      'variant_settings' => [
        'id' => 'panels_everywhere_variant',
        'layout' => 'layout_onecol',
        'builder' => 'standard',
        'route_override_enabled' => FALSE
      ],
    ]);
    $panelsEverywhereVariant->setPageEntity($page);
    $this->placeBlockOnVariant($panelsEverywhereVariant, 'system_main_block', 'content');
    $this->placeBlockOnVariant($panelsEverywhereVariant, 'system_powered_by_block', 'content');
    $panelsEverywhereVariant->save();

    // set up panels
    $panelsVariant = $this->pageVariantStorage->create([
      'id' => 'this-is-a-panels-variant',
      'variant' => 'panels_variant',
      'variant_settings' => [
        'id' => 'panels_variant',
        'layout' => 'layout_onecol',
        'builder' => 'standard',
      ],
    ]);
    $panelsVariant->setPageEntity($page);
    $this->placeBlockOnVariant($panelsVariant, 'entity_view:node', 'content', [
      'view_mode' => 'full',
      'context_mapping' => [
        'entity' => 'node'
      ]
    ]);
    $this->placeBlockOnVariant($panelsVariant, 'views_block:who_s_new-block_1', 'content', [
      'label_display' => 'visible',
      'items_per_page' => 'none'
    ]);
    $panelsVariant->save();
    // @todo: Remove once cache info is setup correctly
    drupal_flush_all_caches();


    $this->drupalGet('node/'.$this->node->id());

    $this->assertSession()->pageTextContains('Powered by');
    $this->assertSession()->pageTextContains($this->node->body->value);
    $this->assertSession()->pageTextContains("Who's new");
  }


}
