<?php

namespace Drupal\Tests\bibcite_entity\Functional;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Test for entity functions.
 *
 * @group bibcite
 */
class BibciteEntityTest extends BrowserTestBase {

  public static $modules = [
    'bibcite_entity_test',
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
      'administer bibcite_reference',
      'create bibcite_reference',
      'view bibcite_reference',
      'edit any bibcite_reference',
      'delete any bibcite_reference',
      'create bibcite_keyword',
      'view bibcite_keyword',
      'edit bibcite_keyword',
      'delete bibcite_keyword',
      'administer bibcite_keyword',
      'create bibcite_contributor',
      'view bibcite_contributor',
      'edit bibcite_contributor',
      'delete bibcite_contributor',
      'administer bibcite_contributor',
    ]);

    $this->simpleUser = $this->drupalCreateUser();
  }

  /**
   * Test Reference type routes.
   */
  public function testReferenceTypeRoutes() {
    $this->drupalGet('admin/config/bibcite/settings/reference/types/add');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/bibcite/settings/reference/types/book/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/bibcite/settings/reference/types/book');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/bibcite/settings/reference/types');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/bibcite/settings/reference/types/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/config/bibcite/settings/reference/types/book/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/config/bibcite/settings/reference/types/book');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/config/bibcite/settings/reference/types');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($this->simpleUser);

    $this->drupalGet('admin/config/bibcite/settings/reference/types/add');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/bibcite/settings/reference/types/book/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/bibcite/settings/reference/types/book');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/bibcite/settings/reference/types');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test Reference routes.
   */
  public function testReferenceRoutes() {
    $this->drupalGet('bibcite/reference/1');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/reference/1/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/reference/1/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/reference/add');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/reference/add/book');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/reference');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/reference/delete');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->user);

    $this->drupalGet('bibcite/reference/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('bibcite/reference/1/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('bibcite/reference/1/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('bibcite/reference/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('bibcite/reference/add/book');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/content/bibcite/reference');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/content/bibcite/reference/delete');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($this->simpleUser);

    $this->drupalGet('bibcite/reference/1');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/reference/1/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/reference/1/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/reference/add');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/reference/add/book');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/reference');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/reference/delete');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test Keyword routes.
   */
  public function testKeywordRoutes() {
    $this->drupalGet('bibcite/keyword/1');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/keyword/1/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/keyword/1/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/keyword/add');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/keyword');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/keyword/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/keyword/1/merge');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/keyword/1/merge/2');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->user);

    $this->drupalGet('bibcite/keyword/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('bibcite/keyword/1/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('bibcite/keyword/1/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('bibcite/keyword/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/content/bibcite/keyword');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/content/bibcite/keyword/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/content/bibcite/keyword/1/merge');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/content/bibcite/keyword/1/merge/2');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($this->simpleUser);

    $this->drupalGet('bibcite/keyword/1');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/keyword/1/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/keyword/1/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/keyword/add');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/keyword');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/keyword/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/keyword/1/merge');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/keyword/1/merge/2');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test Contributor routes.
   */
  public function testContributorRoutes() {
    $this->drupalGet('bibcite/contributor/1');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/contributor/1/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/contributor/1/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/contributor/add');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/contributor');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/contributor/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/contributor/1/merge');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/contributor/1/merge/2');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->user);

    $this->drupalGet('bibcite/contributor/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('bibcite/contributor/1/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('bibcite/contributor/1/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('bibcite/contributor/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/content/bibcite/contributor');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/content/bibcite/contributor/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/content/bibcite/contributor/1/merge');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/content/bibcite/contributor/1/merge/2');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($this->simpleUser);

    $this->drupalGet('bibcite/contributor/1');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/contributor/1/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/contributor/1/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('bibcite/contributor/add');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/contributor');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/contributor/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/contributor/1/merge');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/bibcite/contributor/1/merge/2');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test Contributor category routes.
   */
  public function testContributorCategoryRoutes() {
    $this->drupalGet('admin/config/bibcite/settings/contributor/category/primary');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/bibcite/settings/contributor/category/primary/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/bibcite/settings/contributor/category/add');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/bibcite/settings/contributor/category');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/bibcite/settings/contributor/category/primary');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/config/bibcite/settings/contributor/category/primary/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/config/bibcite/settings/contributor/category/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/config/bibcite/settings/contributor/category');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($this->simpleUser);

    $this->drupalGet('admin/config/bibcite/settings/contributor/category/primary');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/bibcite/settings/contributor/category/primary/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/bibcite/settings/contributor/category/add');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/bibcite/settings/contributor/category');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test Contributor role routes.
   */
  public function testContributorRoleRoutes() {
    $this->drupalGet('admin/config/bibcite/settings/contributor/role/author');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/bibcite/settings/contributor/role/author/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/bibcite/settings/contributor/role/add');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/bibcite/settings/contributor/role');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/bibcite/settings/contributor/role/author');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/config/bibcite/settings/contributor/role/author/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/config/bibcite/settings/contributor/role/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/config/bibcite/settings/contributor/role');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($this->simpleUser);

    $this->drupalGet('admin/config/bibcite/settings/contributor/role/author');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/bibcite/settings/contributor/role/author/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/bibcite/settings/contributor/role/add');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/bibcite/settings/contributor/role');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test CSL mapping form.
   */
  public function testCslMappingForm() {
    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/bibcite/settings/mapping/csl');
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('edit-types-book-entity', 'book');
    $page->selectFieldOption('edit-types-map-entity', 'map');
    $page->selectFieldOption('edit-fields-edition-entity', 'bibcite_edition');
    $page->selectFieldOption('edit-fields-note-entity', 'bibcite_notes');
    $page->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test Settings Reference form.
   */
  public function testSettingsReferenceForm() {
    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/bibcite/settings/reference/settings');
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('edit-view-mode-reference-page-view-mode', 'citation');
    $page->selectFieldOption('edit-view-mode-reference-page-view-mode', 'table');
    $page->selectFieldOption('edit-view-mode-reference-page-view-mode', 'default');
    $page->uncheckField('edit-ui-override-enable-form-override');
    $page->checkField('edit-ui-override-enable-form-override');
    $page->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test Settings Links form.
   */
  public function testSettingsLinksForm() {
    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/bibcite/settings/reference/settings/links');
    $page = $this->getSession()->getPage();
    $page->uncheckField('edit-links-google-scholar-enabled');
    $page->uncheckField('edit-links-doi-enabled');
    $page->uncheckField('edit-links-pubmed-enabled');
    $page->checkField('edit-links-google-scholar-enabled');
    $page->checkField('edit-links-doi-enabled');
    $page->checkField('edit-links-pubmed-enabled');
    $page->pressButton('edit-submit');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test Settings Contributor form.
   */
  public function testSettingsContributorForm() {
    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/bibcite/settings/contributor/settings');
    $page = $this->getSession()->getPage();
    $this->assertSession()->fieldValueEquals('edit-full-name-pattern', '@prefix @first_name @last_name @suffix');
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
