<?php

namespace Drupal\warmer_cdn\Plugin\warmer;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\warmer\Plugin\WarmerPluginBase;
use Drupal\warmer\Plugin\WarmerPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use vipnytt\SitemapParser;

/**
 * The cache warmer for the built-in entity cache.
 *
 * @Warmer(
 *   id = "sitemap",
 *   label = @Translation("CDN via Sitemap"),
 *   description = @Translation("Collects the URLs to warm from the sitemap. Then uses the CDN warmer for those.")
 * )
 */
final class SitemapWarmer extends WarmerPluginBase {

  use UserInputParserTrait;

  /**
   * The sitemap parser.
   *
   * @var \vipnytt\SitemapParser
   */
  private $sitemapParser;

  /**
   * The CDN warmer.
   *
   * @var \Drupal\warmer_cdn\Plugin\warmer\CdnWarmer
   */
  private $warmer;

  /**
   * The warmer manager.
   *
   * @var \Drupal\warmer\Plugin\WarmerPluginManager
   */
  private $warmerManager;

  /**
   * The URL collection.
   *
   * @var array|NULL
   */
  private $urlCollection;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    assert($instance instanceof SitemapWarmer);
    $instance->setSitemapParser($container->get('warmer_cdn.sitemap_parser'));
    $warmer_manager = $container->get('plugin.manager.warmer');
    assert($warmer_manager instanceof WarmerPluginManager);
    $instance->setWarmerManager($warmer_manager);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = []) {
    return $this->cdnWarmer()->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function warmMultiple(array $items = []) {
    return $this->cdnWarmer()->warmMultiple($items);
  }

  /**
   * {@inheritdoc}
   */
  public function buildIdsBatch($cursor) {
    // Parse the sitemaps and extract the URLs.
    $urls = $this->parseSitemaps();
    $cursor_position = is_null($cursor) ? -1 : array_search($cursor, $urls);
    if ($cursor_position === FALSE) {
      return [];
    }
    return array_slice($urls, $cursor_position + 1, (int) $this->getBatchSize());
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $min_priority = $form_state->getValue('minPriority');
    if (!is_numeric($min_priority) || !is_float(floatval($min_priority)) || $min_priority < 0 || $min_priority > 1) {
      $form_state->setError($form['minPriority'], $this->t('minPriority should be a float between 0 and 1.'));
    }
    $this->validateHeaders($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function addMoreConfigurationFormElements(array $form, SubformStateInterface $form_state) {
    // Remove manual URLs.
    $configuration = $this->getConfiguration();
    $form['sitemaps'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Sitemaps'),
      '#description' => $this->t('Enter the list of sitemap URLs. One on each line. Examples: https://example.org/sitemap.xml, /drupal-sitemap.xml.'),
      '#default_value' => empty($configuration['sitemaps']) ? '' : implode("\n", $configuration['sitemaps']),
    ];
    $form['minPriority'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum Priority'),
      '#description' => $this->t('URLs with a lower priority than the configured will not be warmed. A float value between 0 and 1.'),
      '#default_value' => empty($configuration['minPriority']) ? 0 : $configuration['minPriority'],
    ];
    $form['headers'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Headers'),
      '#description' => $this->t('Specific headers to use when making HTTP requests. Format: <code>Header-Name: value1; value2</code>'),
      '#default_value' => empty($configuration['headers']) ? '' : implode('\n\r', $configuration['headers']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $form_state->getValues() + $this->configuration;
    $configuration['sitemaps'] = $this->extractTextarea($configuration, 'sitemaps');
    $configuration['headers'] = $this->extractTextarea($configuration, 'headers');
    $this->setConfiguration($configuration);
  }

  /**
   * Set the sitemap parser.
   *
   * @param \vipnytt\SitemapParser $parser
   *   The client.
   */
  public function setSitemapParser(SitemapParser $parser) {
    $this->sitemapParser = $parser;
  }

  /**
   * Set the warmer manager.
   *
   * @param \Drupal\warmer\Plugin\WarmerPluginManager $warmer_manager
   *   The warmer manager.
   */
  public function setWarmerManager(WarmerPluginManager $warmer_manager) {
    $this->warmerManager = $warmer_manager;
  }

  /**
   * Parse and cache the configured sitemaps.
   *
   * @return string[]
   *   The URLs from parsing the sitemap.
   */
  private function parseSitemaps() {
    if (isset($this->urlCollection)) {
      return $this->urlCollection;
    }
    $configuration = $this->getConfiguration();
    $sitemaps = empty($configuration['sitemaps']) ? [] : $configuration['sitemaps'];
    $sitemap_urls = array_map([$this, 'resolveUri'], $sitemaps);
    array_map([$this, 'parseSitemap'], $sitemap_urls);
    $min_priority = empty($configuration['minPriority']) ? 0 : $configuration['minPriority'];
    $min_priority = floatval($min_priority);
    // Only keep the URLs with enough priority.
    $parsed_urls = array_filter(
      $this->sitemapParser->getURLs(),
      function (array $tags) use ($min_priority) {
        return $tags['priority'] >= $min_priority;
      }
    );
    $this->urlCollection = array_keys($parsed_urls);
    return $this->urlCollection;
  }

  /**
   * Parses a sitemap and logs exceptions.
   *
   * @param string $location
   *   The fully loaded URL for the sitemap.
   */
  private function parseSitemap($location) {
    try {
      $this->sitemapParser->parseRecursive($location);
    }
    catch (SitemapParser\Exceptions\SitemapParserException $exception) {
      watchdog_exception('warmer_cdn', $exception);
      $message = $this->t(
        'There was an error parsing the Sitemap in %location. Please check the watchdog logs for more information.',
        ['%location' => $location]
      );
      $this->messenger()->addError($message);
    }
  }

  /**
   * Lazily get the CDN warmer.
   *
   * @return \Drupal\warmer_cdn\Plugin\warmer\CdnWarmer
   *   The CDN warmer.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function cdnWarmer() {
    if ($this->warmer instanceof CdnWarmer) {
      return $this->warmer;
    }
    $configuration = $this->getConfiguration();
    $warmer = $this->warmerManager->createInstance('cdn', [
      'headers' => $configuration['headers'],
    ]);
    assert($warmer instanceof CdnWarmer);
    $this->warmer = $warmer;
    return $warmer;
  }
}
