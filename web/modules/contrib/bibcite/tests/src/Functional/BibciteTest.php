<?php

namespace Drupal\Tests\bibcite\Functional;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Test for main module functions.
 *
 * @group bibcite
 */
class BibciteTest extends BrowserTestBase {

  public static $modules = [
    'bibcite',
  ];

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Test user without special permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $simpleUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->user = $this->drupalCreateUser([
      'administer bibcite',
    ]);
    $this->simpleUser = $this->drupalCreateUser();
  }

  /**
   * Test CSL style routes.
   */
  public function testCslStyleRoutes() {
    $this->drupalGet('/admin/config/bibcite/settings/csl_style/add');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/admin/config/bibcite/settings/csl_style/apa/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/admin/config/bibcite/settings/csl_style/apa');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/admin/config/bibcite/settings/csl_style');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/admin/config/bibcite/settings/csl_style/add-file');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->user);

    $this->drupalGet('/admin/config/bibcite/settings/csl_style/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/admin/config/bibcite/settings/csl_style/apa/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/admin/config/bibcite/settings/csl_style/apa');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/admin/config/bibcite/settings/csl_style');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/admin/config/bibcite/settings/csl_style/add-file');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($this->simpleUser);

    $this->drupalGet('/admin/config/bibcite/settings/csl_style/add');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/admin/config/bibcite/settings/csl_style/apa/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/admin/config/bibcite/settings/csl_style/apa');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/admin/config/bibcite/settings/csl_style');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/admin/config/bibcite/settings/csl_style/add-file');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test Config page.
   */
  public function testConfigPage() {
    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/bibcite');
    $page = $this->getSession()->getPage();
    $link = $page->findLink('Settings');
    $link->click();
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test Settings form.
   */
  public function testSettingsBibciteForm() {
    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/bibcite/settings');
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('edit-processor', 'citeproc-php');
    $page->selectFieldOption('edit-default-style', 'apa');
    $page->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test Style page.
   */
  public function testStylePage() {
    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/bibcite/settings/csl_style');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test CslStyleFile form.
   */
  public function testStyleFileForm() {
    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/bibcite/settings/csl_style/add-file');
    $page = $this->getSession()->getPage();
    $page->fillField('edit-label', 'bmj');
    $page->attachFileToField('edit-file', __DIR__ . '/../../styles/bmj.csl');
    $page->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test AddStyle form.
   */
  public function testAddStyleForm() {
    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/bibcite/settings/csl_style/add');
    $page = $this->getSession()->getPage();
    $page->fillField('edit-label', 'bmj');
    $csl_file = file_get_contents(__DIR__ . '/../../styles/bmj.csl');
    $page->fillField('edit-csl', $csl_file);
    $page->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('bmj');
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
