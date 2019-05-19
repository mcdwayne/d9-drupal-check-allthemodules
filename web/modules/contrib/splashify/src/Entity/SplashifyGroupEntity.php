<?php

namespace Drupal\splashify\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Splashify group entity entity.
 *
 * @ingroup splashify
 *
 * @ContentEntityType(
 *   id = "splashify_group_entity",
 *   label = @Translation("Splashify group entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\splashify\SplashifyGroupEntityListBuilder",
 *     "views_data" = "Drupal\splashify\Entity\SplashifyGroupEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\splashify\Form\SplashifyGroupEntityForm",
 *       "add" = "Drupal\splashify\Form\SplashifyGroupEntityForm",
 *       "edit" = "Drupal\splashify\Form\SplashifyGroupEntityForm",
 *       "delete" = "Drupal\splashify\Form\SplashifyGroupEntityDeleteForm",
 *     },
 *     "access" = "Drupal\splashify\SplashifyGroupEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\splashify\SplashifyGroupEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "splashify_group_entity",
 *   admin_permission = "administer Splashify group entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/splashify_group_entity/{splashify_group_entity}",
 *     "add-form" = "/admin/structure/splashify_group_entity/add",
 *     "edit-form" = "/admin/structure/splashify_group_entity/{splashify_group_entity}/edit",
 *     "delete-form" = "/admin/structure/splashify_group_entity/{splashify_group_entity}/delete",
 *     "collection" = "/admin/structure/splashify_group_entity",
 *   },
 * )
 */
class SplashifyGroupEntity extends ContentEntityBase implements SplashifyGroupEntityInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
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
    $this->set('status', $published ? NODE_PUBLISHED : NODE_NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSplashMode() {
    return $this->get('field_splash_mode')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isRestrictRoles() {
    return $this->get('field_restrict')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles() {
    $roles = [];
    foreach ($this->get('field_roles')->getValue() as $role) {
      $roles[] = $role['target_id'];
    }

    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getWhere() {
    return $this->get('field_where')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getListPages() {
    return $this->get('field_list_pages')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isOpposite() {
    return $this->get('field_opposite')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOften() {
    return $this->get('field_often')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isDisableReferrerCheck() {
    return $this->get('field_disable_check_referrer')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSize() {
    return $this->get('field_size')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMode() {
    return $this->get('field_mode')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Splashify group entity entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Splashify group entity entity.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Splashify group entity is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['field_disable_check_referrer'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Disable referrer check'))
      ->setDescription(t('Show splash page even when page was loaded from an internal page.'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setDefaultValue(0)
      ->setDisplayOptions('view', array(
        'type' => 'boolean',
        'label' => 'inline',
        'weight' => 3,
        'settings' => array(
          'format' => 'unicode-yes-no',
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => 3,
        'settings' => array(
          'display_label' => TRUE,
        ),
      ))
      ->setDisplayConfigurable('from', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['field_list_pages'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('List Pages'))
      ->setDescription(t('Enter the paths of the pages where you want the splash'
        . ' page to appear. Enter one path per line. Example paths are <b>/blog</b>'
        . ' for the blog page. <b>&lt;front&gt;</b> is the front page.'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setSetting('case_sensitive', FALSE)
      ->setDisplayOptions('view', array(
        'type' => 'basic_string',
        'label' => 'inline',
        'weight' => 9,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 4,
        'settings' => array(
          'rows' => 5,
        ),
      ))
      ->setDisplayConfigurable('from', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['field_mode'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Splash Display Mode'))
      ->setDescription(t('Determines how the splash page should show up.'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setDefaultValue('full_screen')
      ->setSetting('allowed_values', array(
        'full_screen' => t('Full screen'),
        'lightbox' => t('Open in a Lightbox (colorbox)'),
        'window' => t('Open in new window'),
        'redirect' => t('Redirect'),
      ))
      ->setDisplayOptions('view', array(
        'type' => 'list_default',
        'label' => 'inline',
        'weight' => 11,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 4,
      ))
      ->setDisplayConfigurable('from', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['field_often'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('How often should visitors see the splash page?'))
      ->setDescription(t('How often should visitors see the splash page?'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setDefaultValue('never')
      ->setSetting('allowed_values', array(
        'never' => t('Never (off)'),
        'always' => t('Always'),
        'once' => t('Once'),
        'daily' => t('Daily'),
        'weekly' => t('Weekly'),
      ))
      ->setDisplayOptions('view', array(
        'type' => 'list_default',
        'label' => 'inline',
        'weight' => 4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 4,
      ))
      ->setDisplayConfigurable('from', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['field_opposite'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Show the splash page on every page except for the option selected above.'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setDefaultValue(0)
      ->setDisplayOptions('view', array(
        'type' => 'boolean',
        'label' => 'inline',
        'weight' => 10,
        'settings' => array(
          'format' => 'unicode-yes-no',
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => 5,
        'settings' => array(
          'display_label' => TRUE,
        ),
      ))
      ->setDisplayConfigurable('from', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['field_restrict'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Restrict to selected roles'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setDefaultValue(0)
      ->setDisplayOptions('view', array(
        'type' => 'boolean',
        'label' => 'inline',
        'weight' => 6,
        'settings' => array(
          'format' => 'unicode-yes-no',
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => 5,
        'settings' => array(
          'display_label' => TRUE,
        ),
      ))
      ->setDisplayConfigurable('from', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['field_roles'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Which roles should this apply to:'))
      ->setDescription(t('Add the roles for which it will apply'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(-1)
      ->setSetting('target_type', 'user_role')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'type' => 'entity_reference_label',
        'label' => 'inline',
        'weight' => 7,
        'settings' => array(
          'link' => FALSE,
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 6,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => 60,
        ),
      ))
      ->setDisplayConfigurable('from', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['field_size'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Window/Box size'))
      ->setDescription(t('Size (<code>WIDTHxHEIGHT</code>, e.g. 400x300) of the Window or Lightbox.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'max_length' => 255,
        'case_sensitive' => FALSE,
      ))
      ->setDefaultValue('400x300')
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'label' => 'inline',
        'weight' => 12,
        'settings' => array(
          'link_to_entity' => FALSE,
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 6,
        'settings' => array(
          'size' => 60,
        ),
      ))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['field_splash_mode'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Splash Mode'))
      ->setDescription(t('Determines how the content field below will be displayed'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setDefaultValue('plain_text')
      ->setSetting('allowed_values', array(
        'plain_text' => t('Plain text'),
        'template' => t('Use template'),
      ))
      ->setDisplayOptions('view', array(
        'type' => 'list_default',
        'label' => 'inline',
        'weight' => 13,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 5,
      ))
      ->setDisplayConfigurable('from', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['field_where'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Specify where the splash page should show up:'))
      ->setDescription(t('Define where the splash page should show up.'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setDefaultValue('home')
      ->setSetting('allowed_values', array(
        'home' => t('Front Page'),
        'all' => t('All Pages'),
        'list' => t('List Pages'),
      ))
      ->setDisplayOptions('view', array(
        'type' => 'list_default',
        'label' => 'inline',
        'weight' => 8,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 3,
      ))
      ->setDisplayConfigurable('from', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    return $fields;
  }

}
