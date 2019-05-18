<?php

namespace Drupal\Tests\og_sm\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\NodeInterface;
use Drupal\og\OgContextInterface;
use Drupal\simpletest\UserCreationTrait;
use Drupal\og_sm\Tests\SiteCreationTrait;

/**
 * Base class to do kernel tests for OG Site Manager functionality.
 */
abstract class OgSmKernelTestBase extends KernelTestBase {

  use UserCreationTrait;
  use SiteCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'node',
    'og',
    'og_sm',
    'system',
    'user',
    'options',
  ];

  /**
   * Node types to use in the test.
   */
  const TYPE_DEFAULT = 'og_sm_node_type_not_group';
  const TYPE_IS_GROUP = 'og_sm_node_type_is_group';
  const TYPE_IS_GROUP_CONTENT = 'og_sm_node_type_is_group_content';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig(['field', 'og', 'og_sm']);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
  }

  /**
   * Set the OG Context to the given Group node.
   *
   * @param \Drupal\node\NodeInterface $group
   *   Group node to set the context for.
   */
  protected function setOgContextToGroup(NodeInterface $group) {
    $this->container->set('og.context', $this->getMock(OgContextInterface::class));
    $this->container->get('og.context')
      ->method('getGroup')
      ->willReturn($group);
    // Force the site manager service to be recreated with the mock context
    // service.
    $this->container->set('og_sm.site_manager', NULL);
  }

  /**
   * Reset the Og Context.
   */
  protected function resetOgContext() {
    $this->container->set('og.context', $this->getMock(OgContextInterface::class));
    $this->container->get('og.context')
      ->method('getGroup')
      ->willReturn(NULL);
    $this->container->set('og_sm.site_manager', NULL);
  }

}
