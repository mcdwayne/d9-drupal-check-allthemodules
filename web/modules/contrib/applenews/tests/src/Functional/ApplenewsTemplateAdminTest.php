<?php

namespace Drupal\Tests\applenews\Functional;

use Drupal\node\Entity\NodeType;

/**
 * Tests node administration page functionality.
 *
 * @group applenews
 */
class ApplenewsTemplateAdminTest extends ApplenewsTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    \Drupal::service('module_installer')->install(['node']);
    NodeType::create([
      'type' => 'page',
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ])->save();
  }

  /**
   * Tests template pages.
   */
  public function testAppleNewsTemplateAdminPages() {
    $assert_session = $this->assertSession();
    $this->drupalLogin($this->adminUser);

    // Verify overview page has empty message by default.
    $this->drupalGet('admin/config/services/applenews');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('There are no applenews template entities yet.');

    $assert_session->linkExists('Add Apple News Template');
  }

  /**
   * Tests template pages.
   */
  public function testAppleNewsTemplateAdd() {
    $assert_session = $this->assertSession();
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/config/services/applenews/template/add');
    $assert_session->statusCodeEquals(200);

    $assert_session->pageTextContains('Label');
    $assert_session->pageTextContains('Node type');
    $fields = [
      'label',
      'id',
      'node_type',
      'columns',
      'width',
      'gutter',
      'margin',
    ];
    foreach ($fields as $field) {
      $assert_session->fieldExists($field);
    }
    $this->drupalPostForm(NULL, [], 'Save');

    // Validation.
    $assert_session->pageTextContains('Label field is required.');
    $assert_session->pageTextContains('Machine-readable name field is required.');
    $assert_session->pageTextContains('Node Type field is required.');

    // Submission.
    $edit = [
      'label' => $this->randomString(10),
      'id' => strtolower($this->randomMachineName()),
      'node_type' => 'page',
      'columns' => 11,
      'width' => 20,
      'gutter' => 20,
      'margin' => 20,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $assert_session->statusCodeEquals(200);
    $assert_session->responseContains(t('Saved the %label Template.', ['%label' => $edit['label']]));
  }

}
