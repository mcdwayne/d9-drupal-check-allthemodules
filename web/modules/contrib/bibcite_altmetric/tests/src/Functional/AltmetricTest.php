<?php

namespace Drupal\Tests\bibcite_altmetric\Functional;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Test for altmetric functions.
 *
 * @group bibcite
 */
class AltmetricTest extends BrowserTestBase {

  public static $modules = [
    'bibcite',
    'bibcite_entity',
    'bibcite_altmetric',
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
      'administer bibcite',
    ]);
  }

  /**
   * Test Settings form.
   */
  public function testSettingsAltmetricForm() {
    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/bibcite/settings/reference/settings/altmetric');
    $page = $this->getSession()->getPage();
    $this->assertSession()->pageTextContains('DOI');
    $this->assertSession()->pageTextContains('ISBN');
    $this->assertSession()->pageTextContains('URI');
    $this->assertSession()->pageTextContains('PubMed');
    $this->assertSession()->pageTextContains('arXiv');
    $this->assertSession()->pageTextContains('Handle');
    $this->assertSession()->pageTextContains('URN');
    $this->assertSession()->pageTextContains('NCT');
    $this->assertSession()->pageTextContains('Altmetric');
    $page->selectFieldOption('edit-badges', 'Donut');
    $page->selectFieldOption('edit-sizes', 'Medium');
    $page->selectFieldOption('edit-details', 'Without details');
    $page->uncheckField('edit-scores');
    $page->uncheckField('edit-data');
    $page->checkField('edit-scores');
    $page->checkField('edit-data');
    $page->checkField('edit-condensed');
    $page->uncheckField('edit-condensed');
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
