<?php

namespace Drupal\embederator\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\embederator\EmbederatorInterface;

/**
 * Defines the entity.
 *
 * @ingroup embederator
 *
 * This is the main definition of the entity type. From it, an EntityType object
 * is derived. The most important properties in this example are listed below.
 *
 * id: The unique identifier of this entity type. It follows the pattern
 * 'moduleName_xyz' to avoid naming conflicts.
 *
 * label: Human readable name of the entity type.
 *
 * handlers: Handler classes are used for different tasks. You can use
 * standard handlers provided by Drupal or build your own, most probably derived
 * from the ones provided by Drupal. In detail:
 *
 * - view_builder: we use the standard controller to view an instance. It is
 *   called when a route lists an '_entity_view' default for the entity type.
 *   You can see this in the entity.embederator.canonical
 *   route in the embederator.routing.yml file. The view can be
 *   manipulated by using the standard Drupal tools in the settings.
 *
 * - list_builder: We derive our own list builder class from EntityListBuilder
 *   to control the presentation. If there is a view available for this entity
 *   from the views module, it overrides the list builder if the "collection"
 *   key in the links array in the Entity annotation below is changed to the
 *   path of the View. In this case the entity collection route will give the
 *   view path.
 *
 * - form: We derive our own forms to add functionality like additional fields,
 *   redirects etc. These forms are used when the route specifies an
 *   '_entity_form' default for the entity type. Depending on the suffix
 *   (.add/.edit/.delete) of the '_entity_form' default, the form specified in
 *   the annotation is used.
 *
 * - access: Our own access controller, where we determine access rights based
 *   on permissions.
 *
 * More properties:
 *
 *  - base_table: Define the name of the table used to store the data. Make sure
 *    it is unique. The schema is automatically determined from the
 *    BaseFieldDefinitions below. The table is automatically created during
 *    installation.
 *
 *  - fieldable: Can additional fields be attached to the entity via the GUI?
 *    Can the user add fields, like they would to a node?
 *
 *  - entity_keys: How to access the fields. Specify fields from
 *    baseFieldDefinitions() which can be used as keys.
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
 *   id = "embederator",
 *   label = @Translation("Embederator"),
 *   fieldable = TRUE,
 *   base_table = "embederator",
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\embederator\Entity\Controller\EmbederatorListBuilder",
 *     "form" = {
 *       "default" = "Drupal\embederator\Form\EmbederatorForm",
 *       "add" = "Drupal\embederator\Form\EmbederatorForm",
 *       "edit" = "Drupal\embederator\Form\EmbederatorForm",
 *       "delete" = "Drupal\embederator\Form\EmbederatorDeleteForm",
 *     },
 *     "access" = "Drupal\embederator\EmbederatorAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   admin_permission = "administer embederator types",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *   },
 *   links = {
 *     "canonical" = "/embederator/{embederator}",
 *     "edit-form" = "/embederator/{embederator}/edit",
 *     "delete-form" = "/embederator/{embederator}/delete",
 *     "collection" = "/embederator/list"
 *   },
 *   bundle_entity_type = "embederator_type",
 *   field_ui_base_route = "entity.embederator_type.edit_form",
 * )
 *
 * The 'links' above are defined by their path. For core to find the
 * corresponding route, the route name must follow the correct pattern:
 *
 * entity.<entity-name>.<link-name> (replace dashes with underscores)
 * Example: 'entity.embederator.canonical'
 *
 * See routing file above for the corresponding implementation
 *
 * This class defines methods and fields for the entity.
 *
 * Being derived from the ContentEntityBase class, we can override the methods
 * we want. In our case we want to provide access to the standard fields about
 * creation and changed time stamps.
 *
 * Our interface (see EmbederatorInterface) also exposes the EntityOwnerInterface.
 * This allows us to provide methods for setting and providing ownership
 * information.
 *
 * The most important part is the definitions of the field properties for this
 * entity type. These are of the same type as fields added through the GUI, but
 * they can by changed in code. In the definition we can define if the user with
 * the rights privileges can influence the presentation (view, edit) of each
 * field.
 */
class Embederator extends ContentEntityBase implements EmbederatorInterface {

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
  public function getEmbedId() {
    return $this->get('embed_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmbedId($embed_id) {
    $this->set('embed_id', $embed_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
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
    $fields = parent::baseFieldDefinitions($entity_type);

    // Label field for the embederator.
    // We set display options for the view as well as the form.
    // Users with correct privileges can change the view and edit configuration.
    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDescription(t('An administrative name for this instance of the embed (e.g., "Lunch Menu Form").'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      // Set no default value.
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Embed ID field.
    $fields['embed_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Embed ID'))
      ->setDescription(t('Main embed identifier (e.g., "POsLD3i6vNM")'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      // Set no default value.
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'embederator_default',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Owner field of the contact.
    // Entity reference field, holds the reference to the user object.
    // The view shows the user name field of the user.
    // The form presents a auto complete field for the user name.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User Name'))
      ->setDescription(t('The name of the associated user.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'hidden',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'hidden',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
