<?php

namespace Drupal\micro_simple_sitemap;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\simple_sitemap\SimplesitemapManager;

/**
 * Class MicroSimpleSitemapManager.
 */
class MicroSimpleSitemapManager implements MicroSimpleSitemapManagerInterface {

  /**
   * The simple sitemap generator.
   *
   * @var \Drupal\simple_sitemap\Simplesitemap
   */
  protected $generator;

  /**
   * The simple sitemap manager.
   *
   * @var \Drupal\simple_sitemap\SimplesitemapManager
   */
  protected $manager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * MicroSimpleSitemapManager constructor.
   *
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   *   The simple sitemap generator.
   * @param \Drupal\simple_sitemap\SimplesitemapManager $manager
   *   The simple sitemap manager.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(Simplesitemap $generator, SimplesitemapManager $manager, ConfigFactory $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->generator = $generator;
    $this->manager = $manager;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariantName(SiteInterface $site) {
    return 'site-' . $site->id();
  }

  /**
   * {@inheritdoc}
   */
  public function createSitemapVariant(SiteInterface $site) {
    $variant_name = $this->getVariantName($site);
    $this->manager->addSitemapVariant($variant_name, ['type' => 'micro_site', 'label' => $site->label()]);

    $this->setDefaultBundleSettingsVariant($variant_name);

    $default_settings = $this->configFactory->get('simple_sitemap.settings')->getRawData();
    /** @var \Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\DefaultSitemapGenerator $generator */
    $default_generator = $this->manager->getSitemapGenerator('default');
    $default_generator->setSitemapVariant($variant_name);
    $default_generator->setSettings($default_settings);
    $default_generator->remove();

    if ($site->isPublished()) {
      $default_generator->generate([]);
      $default_generator->publish();
    }
  }

  /**
   * Remove a sitemap variant given a micro site.
   *
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   *   The micro site entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function removeSitemapVariant(SiteInterface $site) {
    $variant_name = $this->getVariantName($site);
    $this->manager->removeSitemapVariants([$variant_name]);

    $default_settings = $this->configFactory->get('simple_sitemap.settings')->getRawData();
    /** @var \Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\DefaultSitemapGenerator $generator */
    $default_generator = $this->manager->getSitemapGenerator('default');
    $default_generator->setSitemapVariant($variant_name);
    $default_generator->setSettings($default_settings);
    $default_generator->remove();
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultBundleSettingsVariant($variant_name, $default_bundle_settings = []) {
    if (empty($default_bundle_settings)) {
      $default_bundle_settings = $this->generator->setVariants(['default'])->getBundleSettings();
    }
    foreach ($default_bundle_settings as $entity_type_id => $bundles) {
      foreach ($bundles as $bundle_name => $bundle_settings) {
        if ($entity_type_id == 'menu_link_content') {
          if ($bundle_name == $variant_name) {
            $this->generator->setVariants([$variant_name])->setBundleSettings($entity_type_id, $bundle_name, $bundle_settings);
          }
        }
        else {
          $this->generator->setVariants([$variant_name])->setBundleSettings($entity_type_id, $bundle_name, $bundle_settings);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSitemapVariants($sitemap_type) {
    $sitemap_variants = $this->manager->getSitemapVariants($sitemap_type);
    return $sitemap_variants;
  }

  /**
   * {@inheritdoc}
   */
  public function publishSitemapVariant(SiteInterface $site) {
    $variant_name = $this->getVariantName($site);
    $default_settings = $this->configFactory->get('simple_sitemap.settings')->getRawData();
    /** @var \Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\DefaultSitemapGenerator $generator */
    $default_generator = $this->manager->getSitemapGenerator('default');
    $default_generator->setSitemapVariant($variant_name);
    $default_generator->setSettings($default_settings);
    $default_generator->generate([]);
    $default_generator->publish();
  }

}
