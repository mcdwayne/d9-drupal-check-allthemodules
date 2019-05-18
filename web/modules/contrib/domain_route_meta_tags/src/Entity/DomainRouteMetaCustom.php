<?php

namespace Drupal\domain_route_meta_tags\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\domain_route_meta_tags\DomainRouteMetaTagInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Meta entity.
 *
 * @ingroup domain_route_meta_tags
 *
 * This is the main definition of the entity type. From it, an entityType is
 * derived. The most important properties in this example are listed below.
 *
 * id: The unique identifier of this entityType. It follows the pattern
 * 'moduleName_xyz' to avoid naming conflicts.
 *
 * label: Human readable name of the entity type.
 *
 * handlers: Handler classes are used for different tasks. You can use
 * standard handlers provided by D8 or build your own, most probably derived
 * from the standard class. In detail:
 *
 * - view_builder: we use the standard controller to view an instance. It is
 *   called when a route lists an '_entity_view' default for the entityType
 *   (see routing.yml for details. The view can be manipulated by using the
 *   standard drupal tools in the settings.
 *
 * - list_builder: We derive our own list builder class from the
 *   entityListBuilder to control the presentation.
 *   If there is a view available for this entity from the views module, it
 *   overrides the list builder. @todo: any view? naming convention?
 *
 * - form: We derive our own forms to add functionality like additional fields,
 *   redirects etc. These forms are called when the routing list an
 *   '_entity_form' default for the entityType. Depending on the suffix
 *   (.add/.edit/.delete) in the route, the correct form is called.
 *
 * - access: Our own accessController where we determine access rights based on
 *   permissions.
 *
 * More properties:
 *
 *  - base_table: Define the name of the table used to store the data. Make sure
 *    it is unique. The schema is automatically determined from the
 *    BaseFieldDefinitions below. The table is automatically created during
 *    installation.
 *
 *  - fieldable: Can additional fields be added to the entity via the GUI?
 *    Analog to content types.
 *
 *  - entity_keys: How to access the fields. Analog to 'nid' or 'uid'.
 *
 *  - links: Provide links to do standard tasks. The 'edit-form' and
 *    'delete-form' links are added to the list built by the
 *    entityListController. They will show up as action buttons in an additional
 *    column.
 *
 * There are many more properties to be used in an entity type definition. For
 * a complete overview, please refer to the '\Drupal\Core\Entity\EntityType'
 * class definition.
 *
 * The following construct is the actual definition of the entity type which
 * is read and cached. Don't forget to clear cache after changes.
 *
 * @ContentEntityType(
 *   id = "domain_route_meta_tags",
 *   label = @Translation("Doamin Specific Meta entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\domain_route_meta_tags\Entity\Controller\DomainRouteMetaTagListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\domain_route_meta_tags\Form\DomainRouteMetaTagForm",
 *       "edit" = "Drupal\domain_route_meta_tags\Form\DomainRouteMetaTagForm",
 *       "delete" = "Drupal\domain_route_meta_tags\Form\DomainRouteMetaTagDeleteForm",
 *     },
 *     "access" = "Drupal\domain_route_meta_tags\DomainRouteMetaTagAccessControlHandler",
 *   },
 *   base_table = "domain_route_meta_tags",
 *   admin_permission = "administer domain_route_meta_tags entity",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/system/{domain_route_meta_tags}",
 *     "edit-form" = "/admin/config/system/domain_route_meta_tags/{domain_route_meta_tags}/edit",
 *     "delete-form" = "/admin/config/system/domain_route_meta_tags/{domain_route_meta_tags}/delete",
 *     "collection" = "/admin/config/system/domain_route_meta_tags/list"
 *   },
 * )
 *
 * The 'links' above are defined by their path.
 * For core to find the corresponding
 * route, the route name must follow the correct pattern:
 *
 * entity.<entity-name>.<link-name> (replace dashes with underscores)
 * Example: 'entity.domain_route_meta_tags_contact.canonical'
 *
 * See routing file above for the corresponding implementation
 *
 * The 'Contact' class defines methods and fields for the contact entity.
 *
 * Being derived from the ContentEntityBase class, we can override the methods
 * we want. In our case we want to provide access to the standard fields about
 * creation and changed time stamps.
 *
 * Our interface (see MetaCustomInterface)
 * also exposes the EntityOwnerInterface.
 * This allows us to provide methods for setting and providing ownership
 * information.
 *
 * The most important part is the definitions of the field properties for this
 * entity type. These are of the same type as fields added through the GUI, but
 * they can by changed in code. In the definition we can define if the user with
 * the rights privileges can influence the presentation (view, edit) of each
 * field.
 */
class DomainRouteMetaCustom extends ContentEntityBase implements DomainRouteMetaTagInterface {

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
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
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTime($timestamp) {
    $this->set('changed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTimeAcrossTranslations() {
    $changed = $this->getUntranslated()->getChangedTime();
    foreach ($this->getTranslationLanguages(FALSE) as $language) {
      $translation_changed = $this->getTranslation($language->getId())->getChangedTime();
      $changed = max($translation_changed, $changed);
    }
    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function getBasePath() {
    return \Drupal::service('router.request_context')->getCompleteBaseUrl();
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
  public function getDomain() {
    $url = \Drupal::service('domain.loader')->load($this->get('domain')->value)->getScheme() . \Drupal::service('domain.loader')->load($this->get('domain')->value)->getHostname();
    return $this->get('domain')->value ? $url : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value ? $this->get('title')->value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value ? $this->get('description')->value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeywords() {
    return $this->get('keywords')->value ? $this->get('keywords')->value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCanonical() {
    return $this->get('canonical')->value ? $this->getDomain() . $this->get('canonical')->value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getOgTitle() {
    return $this->get('og_title')->value ? $this->get('og_title')->value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getOgDescription() {
    return $this->get('og_description')->value ? $this->get('og_description')->value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getOgImage() {
    return $this->get('og_image_url')->value ? $this->get('og_image_url')->value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getOgUrl() {
    return $this->get('og_url')->value ? $this->getDomain() . $this->get('og_url')->value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTwitterTitle() {
    return $this->get('twitter_title')->value ? $this->get('twitter_title')->value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTwitterDescription() {
    return $this->get('twitter_description')->value ? $this->get('twitter_description')->value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getfbAppId() {
    return $this->get('fb_id')->value ? $this->get('fb_id')->value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityData() {
    return [
      'meta' => [
        'title' => $this->getTitle(),
        'description' => $this->getDescription(),
        'keywords' => $this->getKeywords(),
        'og:title' => $this->getOgTitle(),
        'og:description' => $this->getOgDescription(),
        'og:image' => $this->getOgImage(),
        'og:url' => $this->getOgUrl(),
        'twitter:title' => $this->getTwitterTitle(),
        'twitter:description' => $this->getTwitterDescription(),
        'fb:app_id' => $this->getfbAppId(),
      ],
      'link' => [
        'canonical' => $this->getCanonical(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $allDomains = \Drupal::service('domain.loader')->loadOptionsList();
    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Meta entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Meta entity.'))
      ->setReadOnly(TRUE);

    // Name field for the contact.
    // We set display options for the view as well as the form.
    // Users with correct privileges can change the view and edit configuration.
    $fields['domain'] = BaseFieldDefinition::create('list_string')
      ->setDisplayOptions('form', [
        'type' => 'select',
        'weight' => -4,
      ])
      ->setRequired(TRUE)
      ->setLabel(t('Select Domain'))
      ->setSetting('allowed_values', $allDomains)
      ->setDisplayConfigurable('form', TRUE);
    $fields['route_link'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Route Path'))
      ->setRequired(TRUE)
      ->setDescription(t('Example paths are /user. And route must exist for the path.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Meta Title'))
      ->setRequired(TRUE)
      ->setDescription(t('Title Tag to be entered.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Meta Description'))
      ->setRequired(TRUE)
      ->setDescription(t('The Description of the Meta entity.'))
      ->setSettings([
        'default_value' => '',
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 3,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['keywords'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Meta Keywords'))
      ->setDescription(t('The Keywords should be separated by "," should be there.'))
      ->setSettings([
        'default_value' => '',
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 3,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['canonical'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Canonical Url'))
      ->setDescription(t('The Canonical url for the page. Example paths are /user. And route must exist for the path.'))
      ->setSettings([
        'default_value' => '',
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 4,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['og_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('OG Title'))
      ->setDescription(t('The OG Title for the page.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['og_description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('OG Description'))
      ->setDescription(t('The OG Description for the page.'))
      ->setSettings([
        'default_value' => '',
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 6,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['og_image_url'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('OG Image Url'))
      ->setDescription(t('The OG Image Url for the page.'))
      ->setSettings([
        'default_value' => '',
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 7,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['og_url'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('OG Url'))
      ->setDescription(t('The OG Url for the page. Example paths are /user. And route must exist for the path.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['twitter_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Twitter Title'))
      ->setDescription(t('The Twitter Title for the page.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['twitter_description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Twitter Description'))
      ->setDescription(t('The Twitter Description for the page.'))
      ->setSettings([
        'default_value' => '',
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 10,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['fb_id'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Facebook Application Id'))
      ->setDescription(t('The Facebook Application Id for the page.'))
      ->setSettings([
        'default_value' => '',
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 11,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 11,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    // Owner field of the contact.
    // Entity reference field, holds the reference to the user object.
    // The view shows the user name field of the user.
    // The form presents a auto complete field for the user name.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User Name'))
      ->setDescription(t('The Name of the associated user.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference',
        'weight' => 12,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
        'weight' => 12,
        'disable' => TRUE,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['is_cachable'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Is Cachable'))
      ->setDescription(t('A boolean indicating whether the entity is cachable.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE);
    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of Contact entity.'));
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));
    return $fields;
  }

}
