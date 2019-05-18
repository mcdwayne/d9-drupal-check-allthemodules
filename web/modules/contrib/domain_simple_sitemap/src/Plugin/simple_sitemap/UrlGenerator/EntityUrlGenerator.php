<?php

namespace Drupal\domain_simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\domain\DomainInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\simple_sitemap\EntityHelper;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\EntityUrlGenerator as SimpleSitemapEntityUrlGenerator;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\simple_sitemap\SitemapGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityUrlGenerator.
 *
 * @package Drupal\domain_simple_sitemap\Plugin\simple_sitemap\UrlGenerator
 *
 * @UrlGenerator(
 *   id = "domain_entity",
 *   title = @Translation("Entity URL generator"),
 *   description = @Translation("Generates URLs for entity bundles and bundle overrides."),
 *   weight = 10,
 *   settings = {
 *     "instantiate_for_each_data_set" = true,
 *   }
 * )
 */
class EntityUrlGenerator extends SimpleSitemapEntityUrlGenerator {

  /**
   * The domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * CustomUrlGenerator constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   *   The Simplesitemap object.
   * @param \Drupal\simple_sitemap\SitemapGenerator $sitemap_generator
   *   The sitemap generator.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\simple_sitemap\Logger $logger
   *   The logger.
   * @param \Drupal\simple_sitemap\EntityHelper $entityHelper
   *   The entity helper.
   * @param \Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager $url_generator_manager
   *   The url generator manager.
   * @param \Drupal\domain\DomainNegotiatorInterface $domain_negotiator
   *   The domain negotiator.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Simplesitemap $generator,
    SitemapGenerator $sitemap_generator,
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
    Logger $logger,
    EntityHelper $entityHelper,
    UrlGeneratorManager $url_generator_manager,
    DomainNegotiatorInterface $domain_negotiator) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $generator,
      $sitemap_generator,
      $language_manager,
      $entity_type_manager,
      $logger,
      $entityHelper,
      $url_generator_manager
    );
    $this->domainNegotiator = $domain_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.sitemap_generator'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('simple_sitemap.logger'),
      $container->get('simple_sitemap.entity_helper'),
      $container->get('plugin.manager.simple_sitemap.url_generator'),
      $container->get('domain.negotiator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function addBatchResult($result) {
    $this->context['results']['generate'][$this->domainNegotiator->getActiveId()][] = $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function addUrl(array $path_data) {
    if ($path_data['url'] instanceof Url) {
      $url_object = $path_data['url'];
      unset($path_data['url']);
      // Add current domain to URLs.
      $url_object->setOption('base_url', $this->domainNegotiator->getActiveDomain()->getRawPath());
      $this->addUrlVariants($path_data, $url_object);
    }
    else {
      $this->addBatchResult($path_data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function generate($data_sets = NULL) {
    if (isset($this->context['domain']) &&
      $this->context['domain'] instanceof DomainInterface) {
      $this->domainNegotiator->setActiveDomain($this->context['domain']);
    }
    parent::generate($data_sets);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBatchIterationElements($entity_info) {
    $query = $this->entityTypeManager->getStorage($entity_info['entity_type_name'])->getQuery();

    if (!empty($entity_info['keys']['id'])) {
      $query->sort($entity_info['keys']['id'], 'ASC');
    }
    if (!empty($entity_info['keys']['bundle'])) {
      $query->condition($entity_info['keys']['bundle'], $entity_info['bundle_name']);
    }
    if (!empty($entity_info['keys']['status'])) {
      $query->condition($entity_info['keys']['status'], 1);
    }
    if ($entity_info['entity_type_name'] == 'node') {
      $orGroupDomain = $query->orConditionGroup()
        ->condition(DOMAIN_ACCESS_FIELD . '.target_id', $this->domainNegotiator->getActiveId())
        ->condition(DOMAIN_ACCESS_ALL_FIELD, 1);
      $query->condition($orGroupDomain);
    }

    if ($this->needsInitialization()) {
      $count_query = clone $query;
      $this->initializeBatch($count_query->count()->execute());
    }
    if ($this->isBatch()) {
      $query->range($this->context['sandbox']['progress'], $this->batchSettings['batch_process_limit']);
    }
    return $this->entityTypeManager
      ->getStorage($entity_info['entity_type_name'])
      ->loadMultiple($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  protected function getProcessedElements() {
    $domain_id = $this->domainNegotiator->getActiveId();
    if (isset($this->context['results']['processed_paths'][$domain_id])
      && !empty($this->context['results']['processed_paths'])) {
      return $this->context['results']['processed_paths'][$domain_id];
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function addProcessedElement($path) {
    $this->context['results']['processed_paths'][$this->domainNegotiator->getActiveId()][] = $path;
  }

  /**
   * {@inheritdoc}
   */
  protected function setProcessedElements($elements) {
    $this->context['results']['processed_elements'][$this->domainNegotiator->getActiveId()] = $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function setProcessingBatchMessage() {
    $results = $this->getBatchResults();
    $result = end($results[$this->domainNegotiator->getActiveId()]);

    if (!empty($path = $result['meta']['path'])) {
      $this->context['message'] = $this->t(self::PROCESSING_PATH_MESSAGE, [
        '@current' => $this->context['sandbox']['progress'],
        '@max' => $this->context['sandbox']['max'],
        '@path' => HTML::escape($path),
      ]);
    }
  }

  /**
   * {@inherticdoc}
   */
  protected function processSegment() {
    if ($this->isBatch()) {
      $this->setProgressInfo();
    }

    if (!empty($max_links = $this->batchSettings['max_links'])) {
      foreach ($this->getBatchResults() as $domain_id => $domain_links) {
        if (count($domain_links) >= $max_links) {
          foreach (array_chunk($domain_links, $max_links) as $chunk_links) {
            if (count($chunk_links) == $max_links) {

              // Generate sitemap.
              $this->sitemapGenerator
                ->setSettings(['excluded_languages' => $this->batchSettings['excluded_languages']])
                ->generateSitemap([$domain_id => $chunk_links], empty($this->getChunkCount()));

              // Update chunk count info.
              $this->setChunkCount(empty($this->getChunkCount()) ? 1 : ($this->getChunkCount() + 1));

              // Remove links from result array that have been generated.
              $this->setBatchResults(array_slice($this->getBatchResults(), count($chunk_links)));
            }
          }
        }
      }
    }
  }

}
