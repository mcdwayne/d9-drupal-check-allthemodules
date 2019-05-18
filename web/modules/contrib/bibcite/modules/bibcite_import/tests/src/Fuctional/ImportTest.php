<?php

namespace Drupal\Tests\bibcite_import\Functional;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Test for main import functions.
 *
 * @group bibcite
 */
class ImportTest extends BrowserTestBase {

  public static $modules = [
    'bibcite_import_test',
  ];

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->user = $this->drupalCreateUser([
      'create bibcite_reference',
      'edit own bibcite_reference',
      'edit any bibcite_reference',
      'view bibcite_reference',
      'administer bibcite',
    ]);
  }

  /**
   * Test Import form.
   */
  public function testImportForm() {
    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/bibcite/import');
    $page = $this->getSession()->getPage();
    $page->attachFileToField('edit-file', __DIR__ . '/data/zero_test.ris');
    $page->selectFieldOption('edit-format', 'RIS');
    $page->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test Populate form.
   *
   * @dataProvider importDataProvider
   */
  public function testPopulateForm($input_data, $format, $title, $year) {
    $this->drupalLogin($this->user);

    $this->drupalGet('admin/content/bibcite/reference/populate');
    $page = $this->getSession()->getPage();
    $page->fillField('edit-data', $input_data);
    $page->selectFieldOption('edit-format', $format);
    $page->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('edit-title-0-value', $title);
    $this->assertSession()->fieldValueEquals('edit-bibcite-year-0-value', $year);
  }

  /**
   * Test Settings form.
   */
  public function testSettingsImportForm() {
    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/bibcite/settings/import');
    $page = $this->getSession()->getPage();
    $page->uncheckField('edit-contributor-deduplication');
    $page->uncheckField('edit-keyword-deduplication');
    $page->checkField('edit-contributor-deduplication');
    $page->checkField('edit-keyword-deduplication');
    $page->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Get test data from YAML.
   *
   * @return array
   *   Data for URL test.
   */
  public function importDataProvider() {
    $yaml_text = file_get_contents(__DIR__ . '/data/testEntityList.data.yml');
    return Yaml::parse($yaml_text);
  }

}
