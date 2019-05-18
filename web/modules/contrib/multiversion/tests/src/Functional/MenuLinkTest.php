<?php

namespace Drupal\Tests\multiversion\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\multiversion\Entity\Workspace;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests menu links deletion.
 *
 * @group multiversion
 */
class MenuLinkTest extends BrowserTestBase {

  protected $strictConfigSchema = FALSE;

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManager
   */
  protected $workspaceManager;

  /**
   * @var \Drupal\multiversion\Entity\WorkspaceInterface
   */
  protected $initialWorkspace;

  /**
   * @var \Drupal\multiversion\Entity\WorkspaceInterface
   */
  protected $newWorkspace;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'multiversion',
    'menu_link_content',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->workspaceManager = \Drupal::service('workspace.manager');
    $web_user = $this->drupalCreateUser(['administer menu', 'administer workspaces']);
    $this->drupalLogin($web_user);
    $this->drupalPlaceBlock('system_menu_block:main');

    $this->initialWorkspace = $this->workspaceManager->getActiveWorkspace();
    $this->newWorkspace = Workspace::create(['machine_name' => 'foo', 'label' => 'Foo', 'type' => 'basic']);
    $this->newWorkspace->save();
  }

  public function testMenuLinksInDifferentWorkspaces() {
    /** @var MenuLinkContentInterface $pineapple */
    $pineapple = MenuLinkContent::create([
      'menu_name' => 'main',
      'link' => 'route:user.page',
      'title' => 'Pineapple'
    ]);
    $pineapple->save();

    $this->assertEqual(
      $pineapple->get('workspace')->target_id,
      $this->initialWorkspace->id(),
      'Pineapple in initial workspace'
    );

    $this->assertNotEqual(
      $pineapple->get('workspace')->target_id,
      $this->newWorkspace->id(),
      'Pineapple not in new workspace'
    );

    $this->workspaceManager->setActiveWorkspace($this->newWorkspace);

    // Save another menu link.
    /** @var MenuLinkContentInterface $pear */
    $pear = MenuLinkContent::create([
      'menu_name' => 'main',
      'link' => 'route:user.page',
      'title' => 'Pear',
    ]);
    $pear->save();

    $this->assertEqual(
      $pear->get('workspace')->target_id,
      $this->newWorkspace->id(),
      'Pear in new workspace'
    );

    $this->assertNotEqual(
      $pear->get('workspace')->target_id,
      $this->initialWorkspace->id(),
      'Pear not in initial workspace'
    );
  }

}
