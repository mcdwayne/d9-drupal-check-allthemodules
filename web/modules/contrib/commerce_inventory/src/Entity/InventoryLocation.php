<?php

namespace Drupal\commerce_inventory\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\core_extend\Entity\EntityActiveTrait;
use Drupal\core_extend\Entity\EntityCreatedTrait;
use Drupal\core_extend\Entity\EntityOwnerTrait;

/**
 * Defines the Inventory Location entity.
 *
 * @ingroup commerce_inventory
 *
 * @ContentEntityType(
 *   id = "commerce_inventory_location",
 *   label = @Translation("Inventory Location"),
 *   label_collection = @Translation("Inventory Locations"),
 *   label_singular = @Translation("inventory location"),
 *   label_plural = @Translation("inventory locations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count inventory location",
 *     plural = "@count inventory locations",
 *   ),
 *   bundle_label = @Translation("Inventory Location type"),
 *   bundle_plugin_type = "commerce_inventory_provider",
 *   handlers = {
 *     "storage" = "Drupal\commerce_inventory\Entity\Storage\InventoryLocationStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_inventory\Entity\ListBuilder\InventoryLocationListBuilder",
 *     "views_data" = "Drupal\commerce_inventory\Entity\ViewsData\InventoryLocationViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\commerce_inventory\Form\InventoryLocationForm",
 *       "add" = "Drupal\commerce_inventory\Form\InventoryLocationForm",
 *       "edit" = "Drupal\commerce_inventory\Form\InventoryLocationForm",
 *       "status" = "Drupal\core_extend\Form\ContentEntityStatusForm",
 *       "delete" = "Drupal\commerce_inventory\Form\InventoryLocationDeleteForm",
 *     },
 *     "access" = "Drupal\commerce_inventory\Entity\Access\InventoryLocationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_inventory\Entity\Routing\InventoryLocationHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_inventory_location",
 *   admin_permission = "administer commerce inventory location",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "bundle" = "commerce_inventory_provider",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "status" = "status",
 *   },
 *   links = {
 *     "collection" = "/admin/commerce/location",
 *     "add-page" = "/admin/commerce/location/add",
 *     "add-form" = "/admin/commerce/location/add/{commerce_inventory_provider}",
 *
 *     "canonical" = "/admin/commerce/location/{commerce_inventory_location}",
 *     "edit-form" = "/admin/commerce/location/{commerce_inventory_location}/edit",
 *     "status-form" = "/admin/commerce/location/{commerce_inventory_location}/status",
 *     "delete-form" = "/admin/commerce/location/{commerce_inventory_location}/delete",
 *     "inventory" = "/admin/commerce/location/{commerce_inventory_location}/inventory",
 *     "inventory-add-confirm" = "/admin/commerce/location/{commerce_inventory_location}/inventory/add/confirm",
 *     "inventory-adjustments" = "/admin/commerce/location/{commerce_inventory_location}/inventory/adjustments",
 *     "inventory-edit-multiple" = "/admin/commerce/location/{commerce_inventory_location}/inventory/edit"
 *   }
 * )
 */
class InventoryLocation extends ContentEntityBase implements InventoryLocationInterface {

  use EntityActiveTrait;
  use EntityCreatedTrait;
  use EntityOwnerTrait;

  /**
   * Get the inventory provider manager.
   *
   * @return \Drupal\commerce_inventory\InventoryProviderManager
   *   An inventory provider manager instance.
   */
  protected static function getProviderManager() {
    return \Drupal::service('plugin.manager.commerce_inventory_provider');
  }

  /**
   * {@inheritdoc}
   */
  public function isItemConfigurationRequired() {
    $definition = self::getProviderManager()->getDefinition($this->bundle());
    return (is_array($definition) && ($definition['item_configuration_required'] || $definition['item_remote_id_required']));
  }

  /**
   * {@inheritdoc}
   */
  public function setRemoteId($remote_id, $provider_id = NULL) {
    $provider_id = is_string($provider_id) ? $provider_id : $this->bundle();
    $this->get('remote_id')->setByProvider($provider_id, $remote_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteId($provider_id = NULL) {
    $provider_id = is_string($provider_id) ? $provider_id : $this->bundle();
    return $this->get('remote_id')->getByProvider($provider_id);
  }

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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Inventory Location.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ]);

    $fields['remote_id'] = BaseFieldDefinition::create('commerce_remote_id')
      ->setLabel(t('Remote ID'))
      ->setDescription(t('The remote ID to be used with the selected provider. Used IDs are automatically filtered out.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_inventory_remote_id_autocomplete',
        'weight' => 10,
        'settings' => [
          'size' => '60',
          'placeholder' => 'Start typing to find an ID.',
        ],
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active status'))
      ->setDescription(t('A boolean indicating whether the Inventory Location is activated.'))
      ->setDefaultValue(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owned by'))
      ->setDescription(t('The user ID of author that owns the Inventory Location.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 10,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $base_field_definitions */
    /** @var \Drupal\commerce_inventory\Plugin\Commerce\InventoryProvider\InventoryProviderInterface $bundle_plugin */
    $bundle_plugin = self::getProviderManager()->createInstance($bundle);

    $bundle_field_definitions = $bundle_plugin->bundleFieldDefinitionsAlter($entity_type, $bundle, $base_field_definitions);

    // Make sure the remote ID is required.
    if ($bundle_plugin->isLocationRemoteIdRequired()) {
      if (!array_key_exists('remote_id', $bundle_field_definitions)) {
        $bundle_field_definitions['remote_id'] = $base_field_definitions['remote_id'];
      }
      $base_field_definitions['remote_id']->setRequired(TRUE);
    }

    return $bundle_field_definitions;
  }

}
