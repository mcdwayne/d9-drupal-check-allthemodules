<?php

namespace Drupal\token_custom\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\token_custom\TokenCustomInterface;

/**
 * Defines the token_custom entity class.
 *
 * @ContentEntityType(
 *   id = "token_custom",
 *   label = @Translation("Custom Token"),
 *   bundle_label = @Translation("Custom Token Type"),
 *   handlers = {
 *     "storage" = "Drupal\token_custom\TokenCustomStorage",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\token_custom\TokenCustomListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\token_custom\Form\TokenCustomForm",
 *       "delete" = "Drupal\token_custom\Form\TokenCustomDeleteForm",
 *       "default" = "Drupal\token_custom\Form\TokenCustomForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "token_custom",
 *   multiversion = FALSE,
 *   translatable = TRUE,
 *   render_cache = TRUE,
 *   entity_keys = {
 *     "id" = "machine_name",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode"
 *   },
 *   bundle_entity_type = "token_custom_type",
 *   permission_granularity = "entity_type",
 *   admin_permission = "administer custom tokens",
 *   links = {
 *     "collection" = "/admin/structure/token-custom",
 *     "canonical" = "/admin/structure/token-custom/manage/{token_custom}",
 *     "add-page" = "/admin/structure/token-custom/add",
 *     "add-form" = "/admin/structure/token-custom/add",
 *     "delete-form" = "/admin/structure/token-custom/manage/{token_custom}/delete",
 *     "edit-form" = "/admin/structure/token-custom/manage/{token_custom}/edit",
 *   }
 * )
 */
class TokenCustom extends ContentEntityBase implements TokenCustomInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine name ID'))
      ->setDescription(t('A unique machine-readable name for this token. It must only contain lowercase letters, numbers, and underscores.'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', [
        'label' => 'hidden',
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Custom Token Type'))
      ->setDescription(t('Custom Token Type.'))
      ->setSetting('target_type', 'token_custom_type')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 2,
      ]);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Administrative name.'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setRevisionable(FALSE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('Description.'))
      ->setRequired(FALSE)
      ->setRevisionable(FALSE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setTranslatable(TRUE);

    $fields['content'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Content'))
      ->setDescription(t('The content that will replace this token.'))
      ->setRequired(TRUE)
      ->setRevisionable(FALSE)
      ->setDefaultValue([
        'value' => '',
        'format' => 'plain_text',
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setTranslatable(TRUE);

    $fields['is_new'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Is new'))
      ->setDescription(t('TRUE if token has been created and not edited before.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the entity.'))
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRawContent() {
    return $this->content->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedContent() {
    $content = $this->content->value;
    $format = $this->content->format;
    return check_markup($content, $format);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormat() {
    $format = $this->content->format;
    if (!$format) {
      $format = filter_default_format();
    }
    return $format;
  }

}
