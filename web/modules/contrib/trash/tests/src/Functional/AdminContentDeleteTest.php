<?php

namespace Drupal\Tests\trash\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the delete tab on nodes and blocks.
 *
 * @group trash
 */
class AdminContentDeleteTest extends BrowserTestBase {
  use TrashTestTrait;

  public static $modules = [
    'block_content',
    'node',
    'content_moderation',
    'trash',
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

  /**
   *  Test Node delete.
   */
  public function testNodeDelete() {
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

    $this->createContent($node_type, 'Test content', FALSE);

    $this->drupalGet('/admin/content');
    $this->assertTrue($session->getPage()->hasContent("Test content"));
    $session->getPage()->clickLink('Delete');
    $session->getPage()->findButton(t('Delete'))->click();
    $this->drupalGet('/admin/content');
    $this->assertFalse($session->getPage()->hasContent("Test content"));

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
    $this->assertTrue($session->getPage()->hasContent("Moderated content"));
  }

  /**
   *  Test Block Delete.
   */
  public function testBlockDelete() {
    $block_type = $this->createBlockContentType('test');

    $this->permissions = array_merge($this->permissions,
      [
        'administer blocks',
      ]);
    $editor1 = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($editor1);

    $session = $this->getSession();

    $this->createBlock($block_type, 'Test block', FALSE);

    $this->drupalGet('/admin/structure/block/block-content');
    $this->assertTrue($session->getPage()->hasContent('Test block'));
    $session->getPage()->clickLink('Delete');
    $session->getPage()->findButton(t('Delete'))->click();
    $this->drupalGet('/admin/structure/block/block-content');
    $this->assertFalse($session->getPage()->hasContent('Test block'));

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
  }

}
