<?php

namespace Drupal\Tests\og_sm_context\Unit;

use Drupal\node\NodeInterface;
use Drupal\og\OgResolvedGroupCollectionInterface;
use Drupal\og_sm\SiteManager;
use Drupal\Tests\og\Unit\Plugin\OgGroupResolver\OgGroupResolverTestBase;

/**
 * Base class for testing OgGroupResolver plugins defined by og_sm.
 */
abstract class OgSmGroupResolverTestBase extends OgGroupResolverTestBase {

  /**
   * The mocked site manager.
   *
   * @var \Drupal\og_sm\SiteManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $siteManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->siteManager = $this->prophesize(SiteManager::class);

    $test_entity_properties = $this->getTestEntityProperties();
    foreach ($this->testEntities as $id => $entity) {
      $properties = $test_entity_properties[$id];
      $is_site = !empty($properties['site']);
      $is_group_content = !empty($properties['group_content']);

      $this->siteManager->isSite($entity)->willReturn($is_site);
      if ($is_site) {
        $this->siteManager->load($id)->willReturn($entity);
      }
      else {
        $this->siteManager->load($id)->willReturn(FALSE);
      }

      if ($is_group_content) {
        $sites = [];
        foreach ($properties['group_content'] as $group_id) {
          $group = $this->testEntities[$group_id];
          if ($this->siteManager->isSite($group)) {
            $sites[] = $group;
          }

        }
        $this->siteManager->getSiteFromEntity($entity)->willReturn(reset($sites));
      }
      else {
        $this->siteManager->getSiteFromEntity($entity)->willReturn(FALSE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createMockedEntity($id, array $properties) {
    /** @var \Drupal\node\NodeInterface|\Prophecy\Prophecy\ObjectProphecy $entity */
    $entity = $this->prophesize(NodeInterface::class);

    $entity->id()->willReturn($id);
    $entity->getEntityTypeId()->willReturn('node');
    $entity->bundle()->willReturn($properties['bundle']);

    return $entity;
  }

  /**
   * Adds an expectation that the plugin will (not) retrieve the site.
   *
   * @param string $expected_added_group
   *   The group that is expected to be added by the plugin. If left empty it is
   *   explicitly expected that the plugin will not add any group to the
   *   collection.
   */
  protected function mightRetrieveSite($expected_added_group = NULL) {
    /** @var \Drupal\og\OgResolvedGroupCollectionInterface|\Prophecy\Prophecy\ObjectProphecy $collection */
    $collection = $this->prophesize(OgResolvedGroupCollectionInterface::class);

    if ($expected_added_group) {
      $collection->addGroup($this->testEntities[$expected_added_group], ['url'])
        ->shouldBeCalled();
    }
    else {
      $collection->addGroup()
        ->shouldNotBeCalled();
    }

    $plugin = $this->getPluginInstance();
    $plugin->resolve($collection->reveal());
  }

  /**
   * {@inheritdoc}
   */
  protected function getTestEntityProperties() {
    return [
      // A group node (non-site).
      'group' => [
        'type' => 'node',
        'bundle' => 'group',
        'group' => TRUE,
      ],
      // A site node.
      'site' => [
        'type' => 'node',
        'bundle' => 'site',
        'group' => TRUE,
        'site' => TRUE,
      ],
      // Content that belong to the site.
      'site_content' => [
        'type' => 'node',
        'bundle' => 'content',
        'group_content' => ['site'],
      ],
      // Content that belong to the group.
      'group_content' => [
        'type' => 'node',
        'bundle' => 'content',
        'group_content' => ['group'],
      ],
      // A non-group, non-group-content node.
      'non_group' => ['type' => 'entity_test', 'bundle' => 'non_group'],
    ];
  }

}
