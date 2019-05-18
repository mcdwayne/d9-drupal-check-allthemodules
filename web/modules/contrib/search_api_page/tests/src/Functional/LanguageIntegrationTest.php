<?php

namespace Drupal\Tests\search_api_page\Functional;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Provides web tests for Search API Pages with language integration.
 *
 * @group search_api_page
 */
class LanguageIntegrationTest extends FunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalLogin($this->adminUser);
    $assert_session = $this->assertSession();

    ConfigurableLanguage::create([
      'id' => 'nl',
      'label' => 'Dutch',
    ])->save();
    ConfigurableLanguage::create([
      'id' => 'es',
      'label' => 'Spanish',
    ])->save();

    $bird_node = $this->drupalCreateNode([
      'title' => 'bird: Hawk',
      'language' => 'en',
      'type' => 'article',
      'body' => [['value' => 'Body translated']],
    ]);
    $bird_node->addTranslation('nl', ['title' => 'bird: Havik'])
      ->addTranslation('es', ['title' => 'bird: Halcon'])
      ->save();

    // Setup search api server and index.
    $this->setupSearchAPI();

    $this->drupalGet('admin/config/search/search-api-pages');
    $assert_session->statusCodeEquals(200);

    $step1 = [
      'label' => 'Search',
      'id' => 'search',
      'index' => $this->index->id(),
    ];
    $this->drupalPostForm('admin/config/search/search-api-pages/add', $step1, 'Next');

    $step2 = [
      'path' => 'search',
    ];
    $this->drupalPostForm(NULL, $step2, 'Save');
  }

  /**
   * Tests Search API Pages language integration.
   */
  public function testSearchApiPage() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/search');
    $this->drupalPostForm(NULL, ['keys' => 'bird'], 'Search');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('1 result found');
    $assert_session->pageTextContains('Hawk');
    $assert_session->pageTextNotContains('Your search yielded no results.');

    $this->drupalGet('/nl/search');
    $this->drupalPostForm(NULL, ['keys' => 'bird'], 'Search');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('1 result found');
    $assert_session->pageTextContains('Havik');
    $assert_session->pageTextNotContains('Your search yielded no results.');
  }

}
