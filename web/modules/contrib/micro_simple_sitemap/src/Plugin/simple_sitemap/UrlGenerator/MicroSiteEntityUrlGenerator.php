<?php

namespace Drupal\micro_simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\micro_site\Entity\Site;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\simple_sitemap\EntityHelper;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\EntityUrlGenerator;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager;
use Drupal\simple_sitemap\Simplesitemap;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MicroSiteEntityUrlGenerator.
 *
 * @UrlGenerator(
 *   id = "micro_site_entity",
 *   label = @Translation("Micro Site entity URL generator"),
 *   description = @Translation("Generates URLs for entity bundles and bundle overrides for a micro site."),
 * )
 */
class MicroSiteEntityUrlGenerator extends EntityUrlGenerator {

  use MicroSiteUrlGeneratorTrait;

  /**
   * The micro site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * MicroSiteEntityUrlGenerator constructor.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param string $plugin_definition
   *   The plugin definition.
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   *   The simple sitemap generator.
   * @param \Drupal\simple_sitemap\Logger $logger
   *   The simple sitemap logguer.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\simple_sitemap\EntityHelper $entityHelper
   *   The entity helper service.
   * @param \Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager $url_generator_manager
   *   The URL generator manager.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $negotiator
   *   The micro site negotiator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Simplesitemap $generator, Logger $logger, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, EntityHelper $entityHelper, UrlGeneratorManager $url_generator_manager, SiteNegotiatorInterface $negotiator, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $generator, $logger, $language_manager, $entity_type_manager, $entityHelper, $url_generator_manager);
    $this->negotiator = $negotiator;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * @inheritdoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.logger'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('simple_sitemap.entity_helper'),
      $container->get('plugin.manager.simple_sitemap.url_generator'),
      $container->get('micro_site.negotiator'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * @inheritdoc
   */
  public function getDataSets() {
    $data_sets = [];
    $sitemap_entity_types = $this->entityHelper->getSupportedEntityTypes();
    $site_id = $this->getSiteId($this->sitemapVariant);

    // @TODO remove this two unused variables (for debug purpose only)
    $default_bundle_settings = $this->generator->setVariants(['default'])->getBundleSettings();
    $test = $this->generator->setVariants($this->sitemapVariant)->getBundleSettings();

    foreach ($this->generator->setVariants($this->sitemapVariant)->getBundleSettings() as $entity_type_name => $bundles) {
      if (isset($sitemap_entity_types[$entity_type_name])) {
        // Skip this entity type if another plugin is written to override its
        // generation.
        foreach ($this->urlGeneratorManager->getDefinitions() as $plugin) {
          if (isset($plugin['settings']['overrides_entity_type'])
            && $plugin['settings']['overrides_entity_type'] === $entity_type_name) {
            continue 2;
          }
        }

        $entityTypeStorage = $this->entityTypeManager->getStorage($entity_type_name);
        $keys = $sitemap_entity_types[$entity_type_name]->getKeys();

        $base_field_site_id = $this->getBaseField($entity_type_name, 'site_id');
        $base_field_site_all = $this->getBaseField($entity_type_name, 'site_all');

        foreach ($bundles as $bundle_name => $bundle_settings) {
          $query = $entityTypeStorage->getQuery()->accessCheck(FALSE);

          if (empty($keys['id'])) {
            $query->sort($keys['id'], 'ASC');
          }
          if (!empty($keys['bundle'])) {
            $query->condition($keys['bundle'], $bundle_name);
          }
          if (!empty($keys['status'])) {
            $query->condition($keys['status'], 1);
          }

          $orGroupMicroSite = $query->orConditionGroup();

          if ($base_field_site_id) {
            $orGroupMicroSite->condition('site_id', $site_id);
          }
          if ($base_field_site_all) {
            $orGroupMicroSite->condition('site_all', 1);
          }
          $field_sites = $this->getField($entity_type_name, $bundle_name, 'field_sites');
          if ($field_sites) {
            $orGroupMicroSite->condition('field_sites.target_id', $site_id);
          }
          $field_sites_all = $this->getField($entity_type_name, $bundle_name, 'field_sites_all');
          if ($field_sites_all) {
            $orGroupMicroSite->condition('field_sites_all.value', 1);
          }

          $query->condition($orGroupMicroSite);

          foreach ($query->execute() as $entity_id) {
            $data_sets[] = [
              'entity_type' => $entity_type_name,
              'id' => $entity_id,
            ];
          }
        }
      }
    }

    return $data_sets;
  }

  /**
   * @inheritdoc
   */
  protected function processDataSet($data_set) {
    if (empty($entity = $this->entityTypeManager->getStorage($data_set['entity_type'])->load($data_set['id']))) {
      return FALSE;
    }

    $entity_id = $entity->id();
    $entity_type_name = $entity->getEntityTypeId();

    $entity_settings = $this->generator
      ->setVariants($this->sitemapVariant)
      ->getEntityInstanceSettings($entity_type_name, $entity_id);

    $url_object = $entity->toUrl();

    // Do not include external paths.
    if (!$url_object->isRouted()) {
      return FALSE;
    }

    $path = $url_object->getInternalPath();

    $url_object->setOption('absolute', TRUE);

    return [
      'url' => $url_object,
      'lastmod' => method_exists($entity, 'getChangedTime') ? date_iso8601($entity->getChangedTime()) : NULL,
      'priority' => isset($entity_settings['priority']) ? $entity_settings['priority'] : NULL,
      'changefreq' => !empty($entity_settings['changefreq']) ? $entity_settings['changefreq'] : NULL,
      'images' => !empty($entity_settings['include_images'])
      ? $this->getImages($entity_type_name, $entity_id)
      : [],

      // Additional info useful in hooks.
      'meta' => [
        'path' => $path,
        'entity_info' => [
          'entity_type' => $entity_type_name,
          'id' => $entity_id,
        ],
      ],
    ];
  }

  /**
   * Set a sitemap variant and the active micro site.
   *
   * @param string $sitemap_variant
   *   The variant name.
   *
   * @return $this
   *   The entity URL generator.
   */
  public function setSitemapVariant($sitemap_variant) {
    parent::setSitemapVariant($sitemap_variant);
    $site_id = $this->getSiteId($sitemap_variant);
    $site = Site::load($site_id);
    if ($site instanceof SiteInterface) {
      $this->negotiator->setActiveSite($site);
    }
    return $this;
  }

}
