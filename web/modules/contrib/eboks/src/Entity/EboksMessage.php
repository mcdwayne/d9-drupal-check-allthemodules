<?php
/**
 * @file
 * Contains eBoks message entity definition.
 */

namespace Drupal\eboks\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the eBoks message entity.
 *
 * @ingroup eboks_message
 *
 * @ContentEntityType(
 *   id = "eboks_message",
 *   label = @Translation("Eboks message"),
 *   base_table = "eboks_message",
 *   translatable = FALSE,
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\eboks\Entity\Controller\EboksListBuilder",
 *     "form" = {
 *       "delete" = "Drupal\eboks\Form\EboksDeleteForm",
 *     },
 *   },
 *   admin_permission = "administer eboks",
 *   entity_keys = {
 *     "id" = "id",
 *     "messages" = "messages",
 *     "timestamp" = "timestamp",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/eboks/{eboks_message}",
 *     "delete-form" = "/eboks/{eboks_message}/delete",
 *     "collection" = "/eboks/list"
 *   },
 *   field_ui_base_route = "eboks_message.settings",
 * )
 */
class EboksMessage extends ContentEntityBase implements EboksMessageInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('id'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['messages'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Messages'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ]);

    $fields['shipment_xml'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('XML Shipment'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ]);

    $fields['timestamp'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Sending timestamp'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ]);

    $fields['sender_data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Sender information'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ]);

    $fields['status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Status'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ]);

    $fields['response'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Response'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ]);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function generateShipmentId() {
    if (empty($this->id())) {
      return FALSE;
    }
    return 'shipment' . date('Ymd', $this->get('timestamp')->value) . 'Id' . $this->id();
  }

}
