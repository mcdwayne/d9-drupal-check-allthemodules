<?php

namespace Drupal\Tests\multiversion\Kernel;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Entity\WorkspaceType;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests workspace interactions.
 *
 * @group multiversion
 */
class WorkspaceTest extends KernelTestBase {

  use UserCreationTrait;
  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'multiversion',
    'key_value',
    'serialization',
    'user',
    'system',
    'block',
    'block_content',
    'link',
    'menu_link_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('workspace');
    $this->installEntitySchema('user');
    $this->installEntitySchema('block');
    $this->installEntitySchema('block_content_type');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('menu_link_content');
    $this->installConfig('multiversion');
    $this->installSchema('key_value', 'key_value_sorted');
    $this->installSchema('system', ['sequences', 'key_value_expire']);
    $multiversion_manager = $this->container->get('multiversion.manager');
    $multiversion_manager->enableEntityTypes();
    $permissions = ['administer workspaces'];
    $this->setCurrentUser($this->createUser($permissions));

    // Create a test workspace type.
    WorkspaceType::create([
      'id' => 'test',
      'label' => 'Test',
    ])->save();

    // Create a live (default) and stage workspace.
    $this->live = Workspace::create([
      'type' => 'test',
      'machine_name' => 'live',
      'label' => 'Live',
    ]);
    $this->live->save();
    $this->stage = Workspace::create([
      'type' => 'test',
      'machine_name' => 'stage',
      'label' => 'Stage',
    ]);
    $this->stage->save();
  }

  /**
   * Tests unpublishing workspaces.
   */
  public function testUnpublishingWorkspaces() {
    // Set stage as the active workspace.
    \Drupal::service('workspace.manager')->setActiveWorkspace($this->stage);

    // Check both workspaces are published by default.
    $this->assertTrue($this->live->isPublished());
    $this->assertTrue($this->stage->isPublished());

    // Unpublish the stage workspace.
    $this->stage->setUnpublished();
    $violations = $this->live->validate();
    $this->assertEquals(0, $violations->count());
    $this->stage->save();

    // After unpublishing stage, live should be the active workspace.
    $active_workspace = \Drupal::service('workspace.manager')->getActiveWorkspace();
    $this->assertEquals(1, $active_workspace->id());

    // Check the stage workspace has been unpublished.
    $this->assertFalse($this->stage->isPublished());

    // Expect an exception if the default workspace is unpublished.
    $this->setExpectedException(\Exception::class);
    $this->live->setUnpublished();
    $violations = $this->live->validate();
    $this->assertEquals(1, $violations->count());
    $this->assertEquals('The default workspace cannot be unpublished or archived.', $violations[0]->getMessage());
    $this->live->save();
  }

  /**
   * Tests block plugins between workspaces.
   */
  public function testBlockPlugins() {
    // Make sure live is the active workspace.
    \Drupal::service('workspace.manager')->setActiveWorkspace($this->live);

    // Create a test block content type and block content, then place it.
    BlockContentType::create([
      'id' => 'test',
      'label' => 'Test',
    ])->save();
    $block_content_1 = BlockContent::create(['type' => 'test']);
    $block_content_1->save();
    $this->placeBlock('block_content:' . $block_content_1->uuid());

    // Test block 1 appears in the block definitions on live.
    /** @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = \Drupal::service('plugin.manager.block');
    $live_definitions = $block_manager->getDefinitions();
    $this->assertTrue(isset($live_definitions['block_content:' . $block_content_1->uuid()]));

    // Test block 2 appears in the block definitions on stage.
    \Drupal::service('workspace.manager')->setActiveWorkspace($this->stage);
    $block_content_2 = BlockContent::create(['type' => 'test']);
    $block_content_2->save();
    $this->placeBlock('block_content:' . $block_content_2->uuid());
    $stage_definitions = $block_manager->getDefinitions();
    $this->assertTrue(isset($stage_definitions['block_content:' . $block_content_2->uuid()]));

    // Test block 1 still appears in the block definitions on live.
    \Drupal::service('workspace.manager')->setActiveWorkspace($this->live);
    $live_definitions = $block_manager->getDefinitions();
    $this->assertTrue(isset($live_definitions['block_content:' . $block_content_1->uuid()]));
  }

}
