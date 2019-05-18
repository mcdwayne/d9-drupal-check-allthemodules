<?php

namespace Drupal\sendinblue\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Contact entity.
 *
 * @ingroup content_entity_example
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
 *   id = "sendinblue_signup_form",
 *   label = @Translation("Sendinblue Signup Form entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\sendinblue\Entity\Controller\SignupListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\sendinblue\Form\SignupForm",
 *       "edit" = "Drupal\sendinblue\Form\SignupForm",
 *       "delete" = "Drupal\sendinblue\Form\SignupDeleteForm",
 *     },
 *     "access" = "Drupal\sendinblue\SignupAccessControlHandler",
 *   },
 *   base_table = "sendinblue_signup",
 *   admin_permission = "administer sendinblue",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "mcsId",
 *     "name" = "name",
 *   },
 *   links = {
 *     "canonical" = "/sendinblue_signup_form/{sendinblue_signup_form}",
 *     "add-form" = "/sendinblue_signup_form/add",
 *     "edit-form" = "/sendinblue_signup_form/{sendinblue_signup_form}/edit",
 *     "delete-form" = "/sendinblue_signup_form/{sendinblue_signup_form}/delete",
 *     "collection" = "/sendinblue_signup_form/list"
 *   },
 * )
 *
 * The 'links' above are defined by their path. For core to find the
 * corresponding
 * route, the route name must follow the correct pattern:
 *
 * entity.<entity-name>.<link-name> (replace dashes with underscores)
 * Example: 'entity.content_entity_example_contact.canonical'
 *
 * See routing file above for the corresponding implementation
 *
 * The 'Contact' class defines methods and fields for the contact entity.
 *
 * Being derived from the ContentEntityBase class, we can override the methods
 * we want. In our case we want to provide access to the standard fields about
 * creation and changed time stamps.
 *
 * Our interface (see ContactInterface) also exposes the EntityOwnerInterface.
 * This allows us to provide methods for setting and providing ownership
 * information.
 *
 * The most important part is the definitions of the field properties for this
 * entity type. These are of the same type as fields added through the GUI, but
 * they can by changed in code. In the definition we can define if the user with
 * the rights privileges can influence the presentation (view, edit) of each
 * field.
 */
class Signup extends ContentEntityBase {

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

    // Standard field, used as unique if primary index.
    $fields['mcsId'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Contact entity.'))
      ->setReadOnly(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Contact entity.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 32,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['mcLists'] = BaseFieldDefinition::create('map')
      ->setLabel(t('settings'))
      ->setDescription(t('The ID of the Contact entity.'));

    $fields['mode'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('mode'))
      ->setDescription(t('The ID of the Contact entity.'));

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The name of the Contact entity.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 32,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['settings'] = BaseFieldDefinition::create('map')
      ->setLabel(t('settings'))
      ->setDescription(t('The ID of the Contact entity.'));

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('status'))
      ->setDescription(t('The ID of the Contact entity.'))
      ->setDefaultValue(1);

    $fields['module'] = BaseFieldDefinition::create('string')
      ->setLabel(t('module'))
      ->setDescription(t('The name of the Contact entity.'));

    return $fields;
  }

}
