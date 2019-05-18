<?php

namespace Drupal\micro_simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Url;
use Drupal\simple_sitemap\EntityHelper;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\EntityUrlGenerator;

/**
 * Class DefaultEntityUrlGenerator.
 *
 * Override the default EntityUrlGenerator used by the default variant.
 */
class DefaultEntityUrlGenerator extends EntityUrlGenerator {

  use MicroSiteUrlGeneratorTrait;

  /**
   * @inheritdoc
   */
  public function getDataSets() {
    $data_sets = [];
    $sitemap_entity_types = $this->entityHelper->getSupportedEntityTypes();

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

        foreach ($bundles as $bundle_name => $bundle_settings) {
          if (!empty($bundle_settings['index'])) {
            $query = $entityTypeStorage->getQuery();

            if (empty($keys['id'])) {
              $query->sort($keys['id'], 'ASC');
            }
            if (!empty($keys['bundle'])) {
              $query->condition($keys['bundle'], $bundle_name);
            }
            if (!empty($keys['status'])) {
              $query->condition($keys['status'], 1);
            }

            if ($base_field_site_id) {
              $query->notExists('site_id');
            }

            foreach ($query->execute() as $entity_id) {
              $data_sets[] = [
                'entity_type' => $entity_type_name,
                'id' => $entity_id,
              ];
            }
          }
        }
      }
    }

    return $data_sets;
  }

  /**
   * Get alternate urls per language given an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *   The entity.
   * @param \Drupal\Core\Url $url_object
   *   The url object.
   *
   * @return array
   *   An array of urls.
   */
  protected function getAlternateUrlsForTranslatedLanguages(ContentEntityBase $entity, Url $url_object) {
    $alternate_urls = [];

    /** @var \Drupal\Core\Language\Language $language */
    foreach ($entity->getTranslationLanguages() as $language) {
      if (!isset($this->settings['excluded_languages'][$language->getId()]) || $language->isDefault()) {
        if ($entity->hasTranslation($language->getId())) {
          $alternate_urls[$language->getId()] = $this->replaceBaseUrlWithCustom($url_object
            ->setOption('language', $language)->toString()
          );
        }
      }
    }
    return $alternate_urls;
  }

}
