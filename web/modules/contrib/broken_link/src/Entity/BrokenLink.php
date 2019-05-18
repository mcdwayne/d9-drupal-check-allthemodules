<?php

namespace Drupal\broken_link\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the broken link entity class.
 *
 * @ContentEntityType(
 *   id = "broken_link",
 *   label = @Translation("Broken link"),
 *   handlers = {
 *     "list_builder" = "Drupal\broken_link\BrokenLinkListBuilder",
 *     "form" = {
 *       "delete" = "Drupal\broken_link\Form\BrokenLinkDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\broken_link\BrokenLinkHtmlRouteProvider",
 *     },
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "views_data" = "Drupal\broken_link\BrokenLinkViewsData",
 *   },
 *   admin_permission = "manage broken link list",
 *   base_table = "broken_link",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/broken_link/{broken_link}/delete",
 *     "collection" = "/admin/config/broken_link"
 *   },
 * )
 */
class BrokenLink extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The entity ID for this broken link content entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The content broken link UUID.'))
      ->setReadOnly(TRUE);

    $fields['link'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Link'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 2000,
      ])
      ->setDescription(t('Broken link.'));

    $fields['hits'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Hits'))
      ->setDescription(t('Number of times broken link is been hit.'))
      ->setSettings([
        'default_value' => 1,
      ])
      ->setSetting('unsigned', TRUE);

    $fields['query_string'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Request parameters'))
      ->setDescription(t('Request query string.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setConstraints([
        'type' => 'varchar',
        'length' => 2000,
      ]);

    $fields['created'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('First access time'))
      ->setDescription(t('First time this entity was created.'));

    $fields['updated'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last access time'))
      ->setDescription(t('Last time this entity was updated.'));

    $fields['referers'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Referers'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setConstraints([
        'type' => 'varchar',
        'length' => 2000,
      ])
      ->setDescription(t('All http referers for one broken link url.'));

    return $fields;
  }

  /**
   * Method to load broken link entity using link.
   *
   * @param string $link
   *   Broken link.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Broken link entity.
   */
  public function loadByLink($link) {
    $page_access_storage = \Drupal::entityManager()->getStorage('broken_link');
    $brokenLink = $page_access_storage->loadByProperties(['link' => $link]);
    return array_shift($brokenLink);
  }

  /**
   * Method to create or update broken link and its hits.
   *
   * @param string $request_path
   *   Broken link.
   *
   * @param string $request_args
   *   Query string parameters.
   */
  public function merge($request_path, $request_args = NULL) {
    $broken_link = $this->loadByLink($request_path);
    if ($broken_link === NULL) {
      $broken_link = $this->create();
      $broken_link->link = $request_path;
      $broken_link->hits = 1;
      $broken_link->created = time();
    }
    else {
      $hits = (int) $broken_link->get('hits')->get(0)->getValue()['value'];
      $broken_link->hits = ++$hits;
      $broken_link->updated = time();
    }
    $recorded_query_string = explode(', ', $broken_link->get('query_string')->getString());
    if (!empty($request_args) && !in_array($request_args, $recorded_query_string)) {
      $broken_link->query_string[] = $request_args;
    }
    $referer = \Drupal::request()->server->get('HTTP_REFERER');
    $recorded_referers = explode(', ', $broken_link->get('referers')->getString());
    if (!empty($referer) && !in_array($referer, $recorded_referers)) {
      $broken_link->referers[] = $referer;
    }
    $broken_link->save();
  }

}
