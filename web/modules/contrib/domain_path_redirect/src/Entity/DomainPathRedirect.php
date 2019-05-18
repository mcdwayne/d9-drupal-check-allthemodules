<?php

namespace Drupal\domain_path_redirect\Entity;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\redirect\Entity\Redirect;
use Drupal\domain\DomainInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * The redirect entity class.
 *
 * @ContentEntityType(
 *   id = "domain_path_redirect",
 *   label = @Translation("Domain Path Redirect"),
 *   bundle_label = @Translation("Redirect type"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\domain_path_redirect\Form\DomainPathRedirectForm",
 *       "delete" = "Drupal\domain_path_redirect\Form\DomainPathRedirectDeleteForm",
 *       "edit" = "Drupal\domain_path_redirect\Form\DomainPathRedirectForm"
 *     },
 *     "views_data" = "Drupal\domain_path_redirect\DomainPathRedirectViewsData",
 *     "storage_schema" = "Drupal\domain_path_redirect\DomainPathRedirectStorageSchema"
 *   },
 *   base_table = "domain_path_redirect",
 *   translatable = FALSE,
 *   admin_permission = "administer redirects",
 *   entity_keys = {
 *     "id" = "rid",
 *     "label" = "redirect_source",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "langcode" = "language",
 *     "domain" = "domain",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/domain_path_redirect/edit/{domain_path_redirect}",
 *     "delete-form" = "/admin/config/search/domain_path_redirect/delete/{domain_path_redirect}",
 *     "edit-form" = "/admin/config/search/domain_path_redirect/edit/{domain_path_redirect}",
 *   }
 * )
 */
class DomainPathRedirect extends Redirect {

  /**
   * Generates a unique hash for identification purposes.
   *
   * @param string $source_path
   *   Source path of the redirect.
   * @param string $domain
   *   Domain id of the redirect.
   * @param array $source_query
   *   Source query as an array.
   * @param string $language
   *   Redirect language.
   *
   * @return string
   *   Base 64 hash.
   */
  public static function generateDomainHash($source_path, $domain, array $source_query, $language) {
    $hash = [
      'source' => mb_strtolower($source_path),
      'language' => $language,
      'domain' => $domain,
    ];

    if (!empty($source_query)) {
      $hash['source_query'] = $source_query;
    }
    redirect_sort_recursive($hash, 'ksort');
    return Crypt::hashBase64(serialize($hash));
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage_controller) {
    $this->set('hash', DomainPathRedirect::generateDomainHash($this->redirect_source->path, $this->domain->target_id, (array) $this->redirect_source->query, $this->getLanguage()));
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['domain'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Domain'))
      ->setDescription(t('The domain name for the redirect.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'domain')
      ->setDefaultValueCallback('Drupal\domain_path_redirect\Entity\DomainPathRedirect::getCurrentDomainId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'domain',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -99,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'domain' base field definition.
   */
  public static function getCurrentDomainId() {
    return [\Drupal::service('domain.negotiator')->getActiveId()];
  }

  /**
   * Sets the domain if of created redirect.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   The active domain request.
   */
  public function setDomain(DomainInterface $domain) {
    $this->set('domain', $domain);
  }

  /**
   * Gets the redirect language.
   *
   * @return string
   *   The language code.
   */
  public function getLanguage() {
    return $this->get('language')->value;
  }

  /**
   * Gets the domain if of created redirect.
   */
  public function getDomain() {
    return $this->getEntityKey('domain');
  }

}
