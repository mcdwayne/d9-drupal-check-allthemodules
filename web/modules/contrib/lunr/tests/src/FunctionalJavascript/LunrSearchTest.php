<?php

namespace Drupal\Tests\lunr\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\lunr\Entity\LunrSearch;
use Drupal\node\Entity\Node;

/**
 * Tests basic Lunr search functionality.
 *
 * @group lunr
 */
class LunrSearchTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'lunr', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $user = $this->drupalCreateUser(['administer lunr search']);
    $this->drupalLogin($user);
    $this->createContentType(['type' => 'article']);
    $this->createContentType(['type' => 'page']);

    ConfigurableLanguage::createFromLangcode('fr')->save();
    $this->config('language.negotiation')->set('url.prefixes', [
      'en' => '',
      'fr' => 'french',
    ])->save();

    /** @var \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager */
    $content_translation_manager = $this->container->get('content_translation.manager');
    $content_translation_manager->setEnabled('node', 'article', TRUE);
    drupal_flush_all_caches();

    for ($i = 0; $i < 50; ++$i) {
      $node = Node::create([
        'type' => 'article',
      ]);
      $node->title = 'Cat ' . $this->randomString();
      $node->body->generateSampleItems();
      $node->setPublished();
      $node->save();
      $node->addTranslation('fr', [
        'title' => 'Chat ' . $this->randomString(),
      ])->save();
    }
    for ($i = 0; $i < 50; ++$i) {
      $node = Node::create([
        'type' => 'article',
      ]);
      $node->title = 'Dog ' . $this->randomString();
      $node->body->generateSampleItems();
      $node->setPublished();
      $node->save();
      $node->addTranslation('fr', [
        'title' => 'Chien ' . $this->randomString(),
      ])->save();
    }
  }

  /**
   * Tests really basic Lunr search functionality.
   */
  public function testLunrSearch() {
    $this->getSession()->maximizeWindow();
    $assert_session = $this->assertSession();
    $lunr_search = LunrSearch::load('default');
    $this->drupalGet($lunr_search->toUrl('index'));
    $this->click('.js-lunr-search-index-button');
    $assert_session->waitForElement('css', 'form.lunr-search-indexing-complete');
    $this->drupalGet(Url::fromRoute('lunr_search.default'));
    $assert_session->pageTextContains('Search');
    $assert_session->pageTextContains('Keywords');
    $input = $assert_session->elementExists('css', '.js-lunr-search-input');
    $submit = $assert_session->elementExists('css', '.js-lunr-search-submit');
    $submit->click();
    $assert_session->waitForElement('css', 'form.lunr-ready');
    $assert_session->pageTextContains('Page 1 of 100 results');
    $assert_session->elementsCount('css', '.lunr-search-result-row', 10);
    $assert_session->pageTextContains('Cat');
    $assert_session->pageTextNotContains('Chat');
    $input->setValue('Dog');
    $submit->click();
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Chien');
    $assert_session->pageTextContains('Page 1 of 50 results');
    $assert_session->elementsCount('css', '.lunr-search-result-row', 10);
    $this->getSession()->executeScript('jQuery("a[data-page=\'1\']").click()');
    $assert_session->waitForElement('css', 'a[data-page="1"].active');
    $assert_session->pageTextContains('Page 2 of 50 results');
    $assert_session->elementsCount('css', '.lunr-search-result-row', 10);

    $this->drupalGet(Url::fromRoute('lunr_search.default', [], ['language' => $this->container->get('language_manager')->getLanguage('fr')]));
    $submit = $assert_session->elementExists('css', '.js-lunr-search-submit');
    $submit->click();
    $assert_session->waitForElement('css', 'form.lunr-ready');
    $assert_session->pageTextContains('Page 1 of 100 results');
    $assert_session->elementsCount('css', '.lunr-search-result-row', 10);
    $assert_session->pageTextNotContains('Cat');
    $assert_session->pageTextContains('Chat');
    $input->setValue('Chien');
    $submit->click();
    $assert_session->pageTextNotContains('Dog');
    $assert_session->pageTextContains('Chien');
    $assert_session->pageTextContains('Page 1 of 50 results');
    $assert_session->elementsCount('css', '.lunr-search-result-row', 10);
  }

  /**
   * Tests the pager logic.
   *
   * @dataProvider providerPagerData
   */
  public function testLunrSearchPager($results_per_page, $links) {
    $this->getSession()->maximizeWindow();
    $assert_session = $this->assertSession();
    $lunr_search = LunrSearch::load('default');
    $lunr_search->setResultsPerPage($results_per_page);
    $lunr_search->save();

    $this->drupalGet($lunr_search->toUrl('index'));
    $this->click('.js-lunr-search-index-button');
    $assert_session->waitForElement('css', 'form.lunr-search-indexing-complete');

    $this->drupalGet(Url::fromRoute('lunr_search.default'));
    $assert_session->waitForElement('css', 'form.lunr-ready');
    $submit = $assert_session->elementExists('css', '.js-lunr-search-submit');
    $submit->click();
    $assert_session->waitForElement('css', 'form.lunr-ready');

    $assert_session->elementsCount('css', '.lunr-search-pager a', $links);
  }

  /**
   * Data provider for testLunrSearchPager().
   */
  public function providerPagerData() {
    return [
      // [$results_per_page, $links]
      // Smaller than page limit, pager links visible.
      [99, 4],
      // Exactly page limit, single page.
      [100, 0],
      // Bigger than page limit, single page.
      [101, 0],
    ];
  }

  /**
   * Tests field based search.
   */
  public function testLunrFieldSearch() {
    $node = Node::create([
      'type' => 'page',
    ]);
    $node->title = 'Bear One';
    $node->body->generateSampleItems();
    $node->setPublished();
    $node->save();

    $node = Node::create([
      'type' => 'page',
    ]);
    $node->title = 'Bear Two';
    $node->body->generateSampleItems();
    $node->setPublished();
    $node->save();

    $this->container->get('module_installer')->install([
      'lunr_facet_example',
    ], TRUE);

    $this->getSession()->maximizeWindow();
    $assert_session = $this->assertSession();
    $lunr_search = LunrSearch::load('facet_example');

    $this->drupalGet($lunr_search->toUrl('index'));
    $this->click('.js-lunr-search-index-button');
    $assert_session->waitForElement('css', 'form.lunr-search-indexing-complete');

    $this->drupalGet(Url::fromRoute('lunr_search.facet_example'));
    $assert_session->waitForElement('css', 'form.lunr-ready');
    $select = $assert_session->elementExists('css', '[data-lunr-search-field="type"]');
    $select->setValue('article');
    $submit = $assert_session->elementExists('css', '.js-lunr-search-submit');
    $submit->click();
    $assert_session->waitForElement('css', 'form.lunr-ready');

    $assert_session->pageTextContains('Cat');
    $assert_session->pageTextNotContains('Bear');

    $select->setValue('page');
    $submit->click();
    $assert_session->waitForElement('css', 'form.lunr-ready');

    $assert_session->pageTextContains('Bear');
    $assert_session->pageTextNotContains('Cat');

    $input = $assert_session->elementExists('css', '.js-lunr-search-input');
    $input->setValue('Two');
    $submit->click();
    $assert_session->waitForElement('css', 'form.lunr-ready');

    $assert_session->pageTextContains('Bear Two');
    $assert_session->pageTextNotContains('Cat');
    $assert_session->pageTextNotContains('Bear One');
  }

}
