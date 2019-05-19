<?php

namespace Drupal\Tests\webform_scheduled_tasks\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTask;

/**
 * Test the email export task plugin UI.
 *
 * @group webform_scheduled_tasks
 */
class EmailedExportUiTest extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'webform_scheduled_tasks',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');

    $this->drupalLogin($this->drupalCreateUser([
      'administer webform',
    ]));
  }

  /**
   * Test the export UI.
   */
  public function testExportUi() {
    WebformScheduledTask::create([
      'id' => 'foo',
      'result_set_type' => 'all_submissions',
      'task_type' => 'export_email_results',
      'webform' => 'contact',
    ])->save();

    $this->drupalGet('admin/structure/webform/manage/contact/scheduled-tasks/foo/edit');

    // By default the delimited settings should appear.
    $this->assertSession()->fieldValueEquals('task_settings[exporter]', 'delimited');
    $this->assertSession()->pageTextContains('Delimiter text format');

    // Ensure the AJAX switching between export formats works.
    $this->getSession()->getPage()->fillField('task_settings[exporter]', 'table');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldValueEquals('task_settings[exporter]', 'table');
    $this->assertSession()->pageTextContains('Open HTML table in Excel');
  }

}
