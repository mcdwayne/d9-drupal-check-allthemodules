<?php

namespace Drupal\Tests\disable_route_normalizer\Functional;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Language\Language;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;

/**
 * Tests the redirect alteration of nodes.
 *
 * @group disable_route_normalizer
 */
class NodeRedirectTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['language', 'locale', 'content_translation', 'node', 'redirect'];

  /**
   * This test creates simple config on the fly breaking schema checking.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create additional language.
    $language = ConfigurableLanguage::createFromLangcode('de');
    $language->save();

    // Configure language negotiation.
    $config = $this->config('language.negotiation');
    $config->set('url.source', LanguageNegotiationUrl::CONFIG_PATH_PREFIX);
    $config->set('url.prefixes', ['en' => 'en', 'de' => 'de']);
    $config->save();

    // Configure language types.
    $config = $this->config('language.types');
    $config->set('negotiation.language_url.enabled', ['language-url' => 0, 'language-url-fallback' => 1]);
    $config->set('negotiation.language_interface.enabled', ['language-url' => -20, 'language-selected' => -19]);
    $config->save();

    // Configure redirect.
    $config = $this->config('redirect.settings');
    $config->set('route_normalizer_enabled', TRUE);
    $config->save();

    // Create translatable basic page content type.
    $node_type = NodeType::create([
      'type' => 'basic_page',
      'name' => 'Basic page',
      'langcode' => Language::LANGCODE_DEFAULT,
      'display_submitted' => FALSE,
      'third_party_settings' => [
        'content_translation' => [
          'enabled' => TRUE,
        ],
      ],
    ]);
    $node_type->save();

    // Create non-translatable landing page content type.
    $node_type = NodeType::create([
      'type' => 'landing_page',
      'name' => 'Landing page',
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
      'display_submitted' => FALSE,
    ]);
    $node_type->save();

    \Drupal::service('kernel')->rebuildContainer();
    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Tests redirections of language prefix.
   */
  public function testNodeRedirect() {
    // Create language defined page to be tested.
    $langcode = 'de';
    $page = Node::create([
      'type' => 'basic_page',
      'title' => 'Kontakt',
      'revision_log' => $this->randomMachineName(),
      'langcode' => $langcode,
    ]);
    $page->save();
    // Test redirection of language defined page.
    $request = Request::create('/node/' . $page->id());
    $kernel = \Drupal::getContainer()->get('http_kernel');
    $response = $kernel->handle($request);

    // We assume that if we find the path prefix in the canonical URI, the
    // request is redirected correctly.
    $uri = $this->findCanonicalUri($response);
    self::assertTrue((strpos($uri, '/' . $langcode . '/') === 0), t('Language defined node is redirected with language prefix'));

    // Create language neutral page to be tested.
    $page2 = Node::create([
      'type' => 'landing_page',
      'title' => 'Landing page',
      'revision_log' => $this->randomMachineName(),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    ]);
    $page2->save();
    // Test redirection of language not specified page.
    $request = Request::create('/node/' . $page2->id());
    $kernel = \Drupal::getContainer()->get('http_kernel');
    $response = $kernel->handle($request);

    // We assume that if we don't find the path prefix in the canonical URI, the
    // request is redirect correctly.
    $uri = $this->findCanonicalUri($response);
    self::assertTrue((strpos($uri, '/' . $langcode . '/') === FALSE), t('Language neutral node is not redirected with language prefix'));
  }

  /**
   * Returns the canonical URI from response object headers.
   *
   * @param $response
   *
   * @return string
   */
  protected function findCanonicalUri($response) {
    $response_headers = $response->headers->all();
    if (!empty($response_headers['link'])) {
      $matches = preg_grep('/.*rel="canonical".*/', $response_headers['link']);
      if (!empty($matches)) {
        $item = array_shift($matches);
        preg_match('/<(.*?)>/', $item, $uri);
        if (!empty($uri[1])) {
          return $uri[1];
        }
      }
    }
    return FALSE;
  }

}
