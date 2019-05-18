<?php

namespace Drupal\micro_simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Url;
use Drupal\micro_site\Entity\Site;
use Drupal\micro_site\Entity\SiteInterface;

/**
 * Trait MicroSiteUrlGenerator.
 */
trait MicroSiteUrlGeneratorTrait {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Get the site id from the sitemap variant name.
   *
   * @param string $sitemap_variant
   *   The variant name.
   *
   * @return mixed
   *   The site id.
   */
  public function getSiteId($sitemap_variant) {
    $site_id = str_replace('site-', '', $sitemap_variant);
    return $site_id;
  }

  /**
   * Get a Basefield on an entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $base_field
   *   The base field name.
   *
   * @return bool|\Drupal\Core\Field\FieldDefinitionInterface
   *   The base field definition or FALSE.
   */
  public function getBaseField($entity_type_id, $base_field) {
    $base_fields = $this->getEntityFieldManager()->getBaseFieldDefinitions($entity_type_id);
    if (isset($base_fields[$base_field])) {
      return $base_fields[$base_field];
    }
    return FALSE;
  }

  /**
   * Get a field on an entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle name.
   * @param string $field
   *   The field name.
   *
   * @return bool|\Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition.
   */
  public function getField($entity_type_id, $bundle, $field) {
    $fields = $this->getEntityFieldManager()->getFieldDefinitions($entity_type_id, $bundle);
    if (isset($fields[$field])) {
      return $fields[$field];
    }
    return FALSE;
  }

  /**
   * Get the entity field manager.
   *
   * @return \Drupal\Core\Entity\EntityFieldManagerInterface|mixed
   *   The entity field manager.
   */
  protected function getEntityFieldManager() {
    if (!$this->entityFieldManager) {
      $this->entityFieldManager = \Drupal::service('entity_field.manager');
    }
    return $this->entityFieldManager;
  }

  /**
   * Replace the base url with the micro site base url.
   *
   * @param string $url
   *   The url to check.
   *
   * @return string
   *   The updated url.
   */
  protected function replaceBaseUrlWithCustom($url) {
    $site_id = $this->getSiteId($this->sitemapVariant);
    $site = Site::load($site_id);
    if ($site instanceof SiteInterface) {
      $url = str_replace($GLOBALS['base_url'], $site->getSitePath(), $url);
    }
    return $url;
  }

  /**
   * Get alternates URLs for an entity on each language.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *   The content entity.
   * @param \Drupal\Core\Url $url_object
   *   The url object.
   *
   * @return array
   *   An array of alternates urls.
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

  /**
   * Check if an entity is affected to a micro site.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *   The content entity.
   * @param string $site_id
   *   The site id.
   *
   * @return bool
   *   TRUE if the entity is affected on a micro site.
   */
  protected function entityIsAffectedToSite(ContentEntityBase $entity, $site_id) {
    if ($entity->hasField('site_id')) {
      if ($entity->site_id->target_id == $site_id) {
        return TRUE;
      }
    }
    if ($entity->hasField('site_all')) {
      if ($entity->site_all->value) {
        return TRUE;
      }
    }
    if ($entity->hasField('field_sites_all')) {
      if ($entity->field_sites_all->value) {
        return TRUE;
      }
    }
    if ($entity->hasField('field_sites')) {
      $targets_ids = $entity->get('field_sites')->getValue();
      foreach ($targets_ids as $target) {
        if ($target['target_id'] == $site_id) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
