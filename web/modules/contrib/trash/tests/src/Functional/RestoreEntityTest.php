<?php

namespace Drupal\Tests\trash\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests restoring nodes and blocks from trash.
 *
 * @group trash
 */
class RestoreEntityTest extends BrowserTestBase {
  use TrashTestTrait;

  public static $modules = [
    'block_content',
    'node',
    'content_moderation',
    'trash'
  ];

  protected $permissions = [
    'administer moderation states',
    'view any unpublished content',
    'use published_archived transition',
    'use draft_draft transition',
    'use published_draft transition',
    'use published_published transition',
    'use draft_published transition',
    'use archived_published transition',
    'use archived_draft transition',
    'access content overview',
  ];

  public function testRestoreNode() {
    $node_type = $this->createNodeType('test');
    $this->permissions = array_merge($this->permissions,
      [
        'administer nodes',
        'administer content types',
        'access content',
        'create test content',
        'edit own test content',
        'delete own test content',
      ]);
    $editor1 = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($editor1);

    $session = $this->getSession();

    $this->drupalGet('/admin/structure/types/manage/test/moderation');
    $page = $session->getPage();
    $page->checkField('edit-enable-moderation-state');
    $page->findButton(t('Save'))->click();

    $this->createContent($node_type, 'Moderated content');
    $this->drupalGet('/admin/content');
    $this->assertTrue($session->getPage()->hasContent("Moderated content"));
    $session->getPage()->clickLink('Delete');
    $this->assertTrue($session->getPage()->hasContent("The Content Moderated content has been moved to the trash."));

    $this->drupalGet('/admin/trash');
    $this->assertTrue($session->getPage()->hasContent('Moderated content'));
    $session->getPage()->clickLink('Restore');
    $session->getPage()->findButton(t('Restore'))->click();
    $this->assertModerationState('node', 'Moderated content', 'published');

  }

  public function testRestoreBlock() {
    $block_type = $this->createBlockContentType('test');

    $this->permissions = array_merge($this->permissions,
      [
        'administer blocks',
      ]);
    $editor1 = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($editor1);

    $session = $this->getSession();

    $this->drupalGet('admin/structure/block/block-content/manage/' . $block_type->id() . '/moderation');
    $page = $session->getPage();
    $page->checkField('edit-enable-moderation-state');
    $page->findButton(t('Save'))->click();

    $this->createBlock($block_type, 'Moderated block');
    $this->drupalGet('/admin/structure/block/block-content');
    $this->assertTrue($session->getPage()->hasContent('Moderated block'));
    $session->getPage()->clickLink('Delete');
    $this->assertTrue($session->getPage()->hasContent("The Custom block Moderated block has been moved to the trash."));

    $this->drupalGet('/admin/trash');
    $this->assertTrue($session->getPage()->hasContent("Moderated block"));
    $session->getPage()->clickLink('Restore');
    $session->getPage()->findButton(t('Restore'))->click();
    $this->assertModerationState('block_content', 'Moderated block', 'published');
  }

}
