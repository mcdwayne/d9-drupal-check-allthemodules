<?php

/**
 * @file
 * Contains Drupal\domain_redirect\Entity\DomainRedirect.
 */

namespace Drupal\domain_redirect\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\link\LinkItemInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Defines the domain redirect entity.
 *
 * @ingroup domain_redirect
 *
 * @ContentEntityType(
 *   id = "domain_redirect",
 *   label = @Translation("Domain redirect"),
 *   bundle_label = @Translation("Redirect type"),
 *   admin_permission = "administer domain redirect",
 *   handlers = {
 *     "access" = "Drupal\domain_redirect\DomainRedirectAccessController",
 *     "list_builder" = "Drupal\domain_redirect\Controller\DomainRedirectListBuilder",
 *     "form" = {
 *       "add" = "Drupal\domain_redirect\Form\DomainRedirectAddForm",
 *       "edit" = "Drupal\domain_redirect\Form\DomainRedirectEditForm",
 *       "delete" = "Drupal\domain_redirect\Form\DomainRedirectDeleteForm"
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "domain_redirect",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "drid",
 *     "label" = "domain",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/domain-redirect/manage/{domain_redirect}",
 *     "edit-form" = "/admin/structure/domain-redirect/manage/{domain_redirect}",
 *     "delete-form" = "/admin/structure/domain-redirect/manage/{domain_redirect}/delete"
 *   }
 * )
 */
class DomainRedirect extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    $values += [
      'type' => 'domain_redirect',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['drid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Domain redirect ID'))
      ->setDescription(t('The domain redirect ID.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The record UUID.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The redirect type.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID of the node author.'))
      ->setDefaultValueCallback('\Drupal\redirect\Entity\Redirect::getCurrentUserId')
      ->setSettings([
        'target_type' => 'user',
      ]);

    $fields['redirect_domain'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Domain'))
      ->setDescription(t('The domain to redirect from. Do not include http://, https:// or any slashes.'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'text',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['redirect_destination'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Destination'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'link_type' => LinkItemInterface::LINK_GENERIC,
        'title' => DRUPAL_DISABLED
      ])
      ->setDisplayOptions('form', [
        'type' => 'link',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The date when the redirect was created.'));

    return $fields;
  }

  /**
   * Gets the redirect domain.
   *
   * @return string
   */
  public function getDomain() {
    return $this->get('redirect_domain')->value;
  }

  /**
   * Gets the redirect destination.
   *
   * @return array
   */
  public function getDestination() {
    return $this->get('redirect_destination')->get(0)->getValue();
  }

  /**
   * Sets the redirect destination URL data.
   *
   * @param string $url
   *   The base url of the redirect destination.
   * @param array $query
   *   Query arguments.
   * @param array $options
   *   The source url options.
   */
  public function setDestination($url, array $query = [], array $options = []) {
    $uri = $url . ($query ? '?' . UrlHelper::buildQuery($query) : '');
    $this->redirect_destination->set(0, ['uri' => 'internal:/' . ltrim($uri, '/'), 'options' => $options]);
  }

  /**
   * Sets the redirect domain.
   *
   * @param string $domain
   *   The redirect domain.
   */
  public function setDomain($domain) {
    $this->set('redirect_domain', $domain);
  }
}
