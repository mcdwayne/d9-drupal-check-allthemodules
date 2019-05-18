<?php

namespace Drupal\server_notice\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * The server_notice entity class.
 *
 * @ContentEntityType(
 *   id = "server_notice",
 *   label = @Translation("Server Notice"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\server_notice\Form\ServerNoticeForm",
 *       "delete" = "Drupal\server_notice\Form\ServerNoticeDeleteForm",
 *       "edit" = "Drupal\server_notice\Form\ServerNoticeForm"
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "\Drupal\server_notice\ServerNoticeStorageSchema"
 *   },
 *   base_table = "server_notice",
 *   translatable = FALSE,
 *   admin_permission = "administer server_notices",
 *   entity_keys = {
 *     "id" = "snid",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/server-notice/edit/{server_notice}",
 *     "delete-form" = "/admin/config/search/server-notice/delete/{server_notice}",
 *     "edit-form" = "/admin/config/search/server-notice/edit/{server_notice}",
 *   }
 * )
 */
class ServerNotice extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * Sets the server notice created datetime.
   *
   * @param int $datetime
   *   The server_notice created datetime.
   */
  public function setCreated($datetime) {
    $this->set('created', $datetime);
  }

  /**
   * Gets the server notice created datetime.
   *
   * @return int
   *   The server_notice created datetime.
   */
  public function getCreated() {
    return $this->get('created')->value;
  }

  /**
   * Gets the FDQN.
   *
   * @return string
   *   The server_notice fdqn.
   */
  public function getFqdn() {
    return $this->get('fqdn')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['snid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Server Notice ID'))
      ->setDescription(t('The server notice ID.'))
      ->setReadOnly(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID of the node author.'))
      ->setDefaultValueCallback('\Drupal\redirect\Entity\Redirect::getCurrentUserId')
      ->setSettings([
        'target_type' => 'user',
      ]);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The record UUID.'))
      ->setReadOnly(TRUE);

    $fields['fqdn'] = BaseFieldDefinition::create('string')
      ->setLabel(t('FQDN'))
      ->setDescription(t("Enter the fully qualified domain name."))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['colour'] = BaseFieldDefinition::create('color_field_type')
      ->setLabel(t('Colour'))
      ->setDescription(t("Choose your colour"))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'opacity' => FALSE,
      ])
      ->setDisplayOptions(
        'form', [
          'weight' => -5,
          'type' => 'color_field_widget_html5',
        ]
      )
      ->setDisplayConfigurable('form', TRUE);

    $fields['notice'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Notice'))
      ->setDescription(t("Text to be displayed."))
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The date when the redirect was created.'));
    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [Drupal::currentUser()->id()];
  }

}
