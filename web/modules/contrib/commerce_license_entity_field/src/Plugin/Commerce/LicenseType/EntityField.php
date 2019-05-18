<?php

namespace Drupal\commerce_license_entity_field\Plugin\Commerce\LicenseType;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\LicenseTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @CommerceLicenseType(
 *   id = "entity_field",
 *   label = @Translation("Entity field value"),
 * )
 */
class EntityField extends LicenseTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Creates a EntityField instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildLabel(LicenseInterface $license) {
    // TODO: consider adding the label of the target field here too, in case
    // there are licenses targetting different fields on the same entities.
    $args = [
      // TODO: this is double-escaping!
      '@entity-label' => $license->license_target_entity->entity->label(),
    ];
    return $this->t('Entity field license for @entity-label', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'target_entity_type_id' => '',
      'target_entity_bundle' => '',
      'entity_field_name' => '',
      'entity_field_value' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigurationValuesOnLicense(LicenseInterface $license) {
    // Override this as our config and fields don't have the same names.
    $license->license_target_field = $this->configuration['entity_field_name'];
    $license->license_target_value = $this->configuration['entity_field_value'];

    // TODO: copy other configuration values once those are stable.
  }

  /**
   * {@inheritdoc}
   */
  public function grantLicense(LicenseInterface $license) {
    // Get the entity that this license targets.
    $target_entity = $license->license_target_entity->entity;

    // Get the field to set and the value to set on it.
    $target_field_name = $license->license_target_field->value;
    $target_value = $license->license_target_value->value;

    // TODO: catch exceptions here in case the license is badly configured, or
    // in case fields have been deleted since the license was created?
    $target_entity->{$target_field_name} = $target_value;

    $target_entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function revokeLicense(LicenseInterface $license) {
    // Get the entity that this license targets.
    $target_entity = $license->license_target_entity->entity;

    // Get the definition of the field to unset.
    $target_field_name = $license->license_target_field->value;
    $bundle_fields = $this->entityFieldManager->getFieldDefinitions($target_entity->getEntityTypeId(), $target_entity->bundle());
    $target_field_definition = $bundle_fields[$target_field_name];

    // Set the default value for this field onto the target entity.
    // TODO: consider instead storing the prior value of this field on the
    // license so we restore to what it was before the license was granted.
    $default_value = $target_field_definition->getDefaultValue($target_entity);
    $target_entity->{$target_field_name} = $default_value;

    $target_entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $entity_types = $this->entityTypeManager->getDefinitions();
    $bundle_info = $this->entityTypeBundleInfo->getAllBundleInfo();

    $entity_type_bundle_options = [];
    foreach ($bundle_info as $entity_type_id => $bundles) {
      // Skip config entities.
      if ($entity_types[$entity_type_id]->getGroup() == 'configuration') {
        continue;
      }

      // Skip non-fieldable entities.
      if (!$entity_types[$entity_type_id]->entityClassImplements(FieldableEntityInterface::class)) {
        continue;
      }

      foreach ($bundles as $bundle => $info) {
        $entity_type_bundle_options["$entity_type_id:$bundle"] = $entity_types[$entity_type_id]->getLabel() . ' - ' . $info['label'];
      }
    }
    natsort($entity_type_bundle_options);

    $target_entity_bundles_default = isset($this->configuration['target_entity_type_id'])
      ? $this->configuration['target_entity_type_id'] . ':' . $this->configuration['target_entity_bundle']
      : '';

    $form['target_entity_bundles'] = [
      '#type' => 'select',
      '#title' => $this->t("Entity type and bundle"),
      '#description' => $this->t("The type and bundle of the entities the user will be able to choose from when purchasing a license. NOTE: Cart form is not yet implemented!"),
      '#options' => $entity_type_bundle_options,
      '#required' => TRUE,
      '#default_value' => $target_entity_bundles_default,
      // Workaround for core bug: https://www.drupal.org/node/2906113
      '#empty_value' => '',
      /*
      // See ajaxCallback()
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => 'entity_field_name-wrapper',
      ],
      */
    ];

    // TODO: large chunk of commented-out form, as:
    // a. I haven't completely worked out how customers will select an entity
    // in the cart form for the license, and therefore how the selection
    // options are configured in the license plugin.
    // b. I got stuck trying to get AJAX to work within the IEF Product
    // variation form and it all turned into a monstrous rabbithole.
    /*
    // TODO: make this a plugin, as we need the value NOT set.
    // or should that be built-in, obviously?
    // Use an EntityReferenceSelection plugin here?
    // Subclass the derived one to provide own/any filtering, or is that too much work?
    $form['restriction'] = [
      '#type' => 'radios',
      '#title' => $this->t("Entity restriction"),
      '#description' => $this->t("Restricts the entities the user will be able to choose from."),
      '#options' => [
        'any' => $this->t("Any entity"),
        'own' => $this->t("Own entities only"),
        // TODO: consider adding an option for 'entities the user may edit'.
      ],
      '#default_value' => $this->configuration['restriction'] ?? '',
    ];

    $target_field_name_options = [];

    // TODO! clean up this hack!!!!!
    // This probably all needs to be done in a #process callback so the #parents
    // property is available.
    $target_entity_bundles_parents = [
    // WTF this worked last week!
      'variations',
      'form',
      'inline_entity_form',
      'entities',
      '0',
      'form',
      'license_type',
      '0',
      'target_plugin_configuration',
      'entity_field',
      'target_entity_bundles',
    ];
    $target_entity_bundles_parents = [
      'variations',
      'form',
      'inline_entity_form',
      'license_type',
      'target_plugin_configuration',
      'entity_field',
      'target_entity_bundles',
    ];

    // ARGH I can't get this to work. The form state values look right, but
    // NestedArray isn't getting the value I want. Probably something stupid,
    // but dont have time for rabbitholes.
    $target_entity_bundles_value = NestedArray::getValue($form_state->getUserInput(), $target_entity_bundles_parents);
    dsm($form_state->getUserInput()['variations']['form']['inline_entity_form']['license_type']['target_plugin_configuration']);
    dsm($target_entity_bundles_value);
    // Hardcoding this for now. If you want the form to work properly, please
    // submit a patch!
    $target_entity_bundles_value = 'node:directory_entry';

    if ($target_entity_bundles_value) {
      list($target_entity_type_id, $target_bundle) = explode(':', $target_entity_bundles_value);

      $bundle_fields = array_filter($this->entityFieldManager->getFieldDefinitions($target_entity_type_id, $target_bundle), function ($field_definition) {
        return !$field_definition->isComputed();
      });

      foreach ($bundle_fields as $field_name => $field_definition) {
        $target_field_name_options[$field_name] = $field_definition->getLabel();
      }
    }
    */

    /*
    $form['entity_field_name'] = [
      '#type' => 'select',
      '#title' => $this->t("Field"),
      '#description' => $this->t("The field on which the license will set a value."),
      '#options' => $target_field_name_options,
      '#default_value' => $this->configuration['entity_field_name'],
      '#required' => TRUE,
      // Workaround for core bug: https://www.drupal.org/node/2906113
      '#empty_value' => '',
      //'#default_value' => $this->configuration['license_og_role'],
      '#prefix' => '<div id="entity_field_name-wrapper">',
      '#suffix' => '</div>',
    ];
    */

    $form['entity_field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Field"),
      '#description' => $this->t("The field on which the license will set a value."),
      '#default_value' => $this->configuration['entity_field_name'],
      '#required' => TRUE,
    ];

    // TODO: can this be done by getting a default value widget? Look in
    // FieldConfigEditForm for code to ape.
    $form['entity_field_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Field value"),
      '#description' => $this->t("The value which will be set on the field."),
      '#default_value' => $this->configuration['entity_field_value'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * AJAX callback displaying the roles select box. TODO
   *
   * @todo doesn't work!
   */
  /*
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    //ddl($element);
    //ddl($form['variations']['widget']['form']['inline_entity_form']['license_type']['widget'][0]['target_plugin_configuration']['form']);

    //$parents = $form_state->getTriggeringElement()['#parents'];
    //dsm($parents);

    $array_parents = $form_state->getTriggeringElement()['#array_parents'];
    ddl($array_parents);
    array_pop($array_parents);
    $array_parents[] = 'entity_field_name';
    ddl($array_parents);

    $subform = NestedArray::getValue($form, $array_parents);
    ddl($subform);
    return $subform;

    return [];
    $array_parents = $form_state->getTriggeringElement()['#array_parents'];
    array_pop($parents);
    $parents[] = 'license_og_role';

    // TODO: this doesn't work -- the $form does not appear to have a structure
    // that $form_state->getTriggeringElement()['#parents'] corresponds with.
    return NestedArray::getValue($form, $parents);
  }
  */

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Form validation handler.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    list($target_entity_type_id, $target_entity_bundle) = explode(':', $values['target_entity_bundles']);
    $this->configuration['target_entity_type_id'] = $target_entity_type_id;
    $this->configuration['target_entity_bundle'] = $target_entity_bundle;

    $this->configuration['entity_field_name'] = $values['entity_field_name'];
    $this->configuration['entity_field_value'] = $values['entity_field_value'];

  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    // All entity types may be referenced by default.
    $fields['license_target_entity'] = BundleFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Target entity'))
      ->setDescription(t('The entity this license sets a value on.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'dynamic_entity_reference_label',
        'weight' => 10,
        'settings' => [
          'link' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);
      /*
      This needs to be done a different way:
      - Commerce License needs to figure out a way to allow a license plugin
        to configure its fields only for the add to cart form mode.
      - The widget used here depends on the configuration of the license plugin
        in the product variation type.
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])*/
    // A string name, rather than a field_config entity reference, as this
    // can be a base field.
    $fields['license_target_field'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Target field name'))
      ->setDescription(t('The entity field this license sets a value on.'))
      ->setCardinality(1)
      ->setRequired(TRUE);
    // TODO: this should be type 'map' so it supports large serialized values,
    // but that causes a crash. See https://www.drupal.org/node/2887105
    $fields['license_target_value'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Target field value'))
      ->setDescription(t('The value this license sets on the entity.'))
      ->setCardinality(1)
      ->setRequired(TRUE);

    return $fields;
  }

}
