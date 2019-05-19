<?php

namespace Drupal\Tests\snippet_manager\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\snippet_manager\Entity\Snippet;

/**
 * Tests access control handler.
 *
 * @group snippet_manager
 */
class SnippetAccessTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'snippet_manager',
    'snippet_manager_test',
    'filter',
    'user',
  ];

  /**
   * Test callback.
   */
  public function testAccess() {

    $this->installConfig(['filter']);

    $snippet = Snippet::create();

    // Unprivileged can only view enabled snippets.
    $this->assertTrue($snippet->access('view'));
    $this->assertFalse($snippet->access('edit'));

    $snippet->disable();

    \Drupal::entityTypeManager()
      ->getAccessControlHandler('snippet')
      ->resetCache();

    $this->assertFalse($snippet->access('view'));

    // Privileged user can view and edit disabled snippets.
    \Drupal::currentUser()->setAccount($this->createUser([], ['administer snippets']));
    $this->assertTrue($snippet->access('view'));
    $this->assertTrue($snippet->access('edit'));
  }

}
