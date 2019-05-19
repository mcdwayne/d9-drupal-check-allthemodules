<?php

namespace Drupal\swiper_slider\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Swiper slide entity.
 *
 * @ingroup swiper_slider
 *
 * @ContentEntityType(
 *   id = "swiper_slider",
 *   label = @Translation("Swiper slide"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\swiper_slider\SwiperSliderListBuilder",
 *     "views_data" = "Drupal\swiper_slider\Entity\SwiperSlideViewsData",
 *     "translation" = "Drupal\swiper_slider\SwiperSliderTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\swiper_slider\Form\SwiperSlideForm",
 *       "add" = "Drupal\swiper_slider\Form\SwiperSlideForm",
 *       "edit" = "Drupal\swiper_slider\Form\SwiperSlideForm",
 *       "delete" = "Drupal\swiper_slider\Form\SwiperSlideDeleteForm",
 *     },
 *     "access" = "Drupal\swiper_slider\SwiperSliderAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\swiper_slider\SwiperSliderHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "swiper_slider",
 *   data_table = "swiper_slide_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer swiper slide entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/swiper_slider/{swiper_slider}",
 *     "add-form" = "/admin/structure/swiper_slider/add",
 *     "edit-form" = "/admin/structure/swiper_slider/{swiper_slider}/edit",
 *     "delete-form" = "/admin/structure/swiper_slider/{swiper_slider}/delete",
 *     "collection" = "/admin/structure/swiper_slider",
 *   },
 *   field_ui_base_route = "swiper_slider.settings"
 * )
 */
class SwiperSlider extends ContentEntityBase implements SwiperSliderInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    return $this->get('class')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setClass($class) {
    $this->set('class', $class);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBackground() {
    return $this->get('background')->value;
  }

  /**
   * {@inheritdoc}
   *
   * @return string
   *   Public function getBackgroundUrl.
   */
  public function getBackgroundUrl() {
    return file_create_url($this->background->entity->getFileUri());
  }

  /**
   * {@inheritdoc}
   */
  public function setBackground($background) {
    $this->set('background', $background);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getContent() {
    return $this->get('content')->value;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   Public function getRenderedContent.
   */
  public function getRenderedContent() {
    if ($this->content) {
      $display_settings = [
        'label' => 'hidden',
      ];
      return $this->content->view($display_settings);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setContent($content) {
    $this->set('content', $content);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Swiper slide entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Swiper slide.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['class'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Class'))
      ->setDescription(t('The css class of the Swiper slide.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['content'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Content'))
      ->setDescription(t('The content of the Swiper slide.'))
      ->setSettings([
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => -8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => -8,
        'settings' => ['format' => 'basic_html'],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['background'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Background'))
      ->setDescription(t('The background of the slide'))
      ->setSettings([
        'file_directory' => '[date:custom:Y]-[date:custom:m]',
        'file_extensions' => 'png gif jpg jpeg',
        'max_filesize' => '',
        'max_resolution' => '',
        'min_resolution' => '',
        'alt_field' => TRUE,
        'alt_field_required' => FALSE,
        'title_field' => FALSE,
        'title_field_required' => FALSE,
        'default_image' => [
          'uuid' => '',
          'alt' => '',
          'title' => '',
          'width' => NULL,
          'height' => NULL,
        ],
        'handler' => 'default:file',
        'handler_settings' => [],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'image',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => -7,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Swiper slide is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
