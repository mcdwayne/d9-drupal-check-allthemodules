<?php

namespace Drupal\domain_simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathValidator;
use Drupal\Core\Url;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\simple_sitemap\EntityHelper;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\CustomUrlGenerator as SimpleSitemapCustomUrlGenerator;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\simple_sitemap\SitemapGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CustomUrlGenerator.
 *
 * @package Drupal\domain_simple_sitemap\Plugin\simple_sitemap\UrlGenerator
 *
 * @UrlGenerator(
 *   id = "domain_custom",
 *   weight = 0,
 *   title = @Translation("Domain Custom URL generator"),
 *   description = @Translation("Generates URLs set in admin/config/search/simplesitemap/custom."),
 * )
 */
class CustomUrlGenerator extends SimpleSitemapCustomUrlGenerator {

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
   * @param \Drupal\Core\Path\PathValidator $path_validator
   *   The path validator.
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
    PathValidator $path_validator,
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
      $path_validator
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
      $container->get('path.validator'),
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

}
