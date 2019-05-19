<?php

namespace Drupal\licensing\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the License entity.
 *
 * @ingroup license
 *
 * @ContentEntityType(
 *   id = "license",
 *   label = @Translation("License"),
 *   bundle_label = @Translation("License type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\licensing\LicenseListBuilder",
 *     "views_data" = "Drupal\licensing\Entity\LicenseViewsData",
 *     "translation" = "Drupal\licensing\LicenseTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\licensing\Form\LicenseForm",
 *       "add" = "Drupal\licensing\Form\LicenseForm",
 *       "edit" = "Drupal\licensing\Form\LicenseForm",
 *       "delete" = "Drupal\licensing\Form\LicenseDeleteForm",
 *     },
 *     "access" = "Drupal\licensing\LicenseAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\licensing\LicenseHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "license",
 *   data_table = "license_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer license entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "status" = "published",
 *     "langcode" = "langcode"
 *   },
 *   links = {
 *     "canonical" = "/content/license/{license}",
 *     "add-page" = "/license/add",
 *     "add-form" = "/license/add/{license_type}",
 *     "edit-form" = "/license/{license}/edit",
 *     "delete-form" = "/license/{license}/delete",
 *     "collection" = "/admin/content/license",
 *   },
 *   bundle_entity_type = "license_type",
 *   field_ui_base_route = "entity.license_type.edit_form"
 * )
 */
class License extends ContentEntityBase implements LicenseInterface {

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
  public function getType() {
    return $this->bundle();
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
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   *
   */
  public function isActive() {
    return $this->getStatus() == LICENSE_ACTIVE;
  }

  /**
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getLicensedEntity() {
    return $this->get('licensed_entity')->first()->entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the License entity.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The user ID of author of license owner.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 1,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Status'))
      ->setDescription(t('The license status.'))
      ->setDefaultValue(LICENSE_CREATED)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 2,
      ))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        LICENSE_CREATED => t('Created'),
        LICENSE_PENDING => t('Pending'),
        LICENSE_ACTIVE => t('Active'),
        LICENSE_EXPIRED => t('Expired'),
        LICENSE_SUSPENDED => t('Suspended'),
        LICENSE_REVOKED => t('Revoked'),
      ]);

    $fields['expires_automatically'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Expires automatically'))
      ->setDescription(t('If true, the license will expire automatically at the expiry date and time.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => 3,
        'settings' => array(
          'display_label' => TRUE,
        ),
      ))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // @todo Display this field conditionally.
    $fields['expiry'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expiry'))
      ->setDescription(t('The license expiration date and time.'))
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp',
        'weight' => 4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['licensed_entity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Licensed entity'))
      ->setDescription(t('The entity to which this license grants the owner access.'))
      ->setRevisionable(TRUE)
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 1,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields = [];
    if ($license_type = LicenseType::load($bundle)) {
      $fields['licensed_entity'] = clone $base_field_definitions['licensed_entity'];

      $target_type = $license_type->get('target_entity_type') ? $license_type->get('target_entity_type') : 'node';
      $fields['licensed_entity']->setSetting('target_type', $target_type);

      $target_bundles = $license_type->get('target_bundles') ? $license_type->get('target_bundles') : [];
      if ($target_bundles) {
        $fields['licensed_entity']->setSetting('handler_settings', [
          'target_bundles' => $license_type->get('target_bundles'),
        ]);
      }

    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // @todo clear cached entries for the licensed entity so that newly licensed user can view.
    $owner = $this->getOwner()->label();
    $uid = $this->getOwner()->id();
    $entity_type = $this->getLicensedEntity()->getEntityTypeId();
    $id = $this->getLicensedEntity()->id();
    $name =  "$owner($uid): $entity_type $id";
    $name = substr($name, 0, 255);
    $this->setName($name);

    parent::preSave($storage);
  }

}
