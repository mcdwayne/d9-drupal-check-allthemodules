<?php

namespace Drupal\blizz_bulk_creator\Services;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\blizz_bulk_creator\Entity\BulkcreateConfigurationInterface;
use Drupal\blizz_bulk_creator\Entity\BulkcreateUsageInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Class BulkcreateAdministrationHelper.
 *
 * Contains administrative helper function for
 * handling bulkcreate configurations.
 *
 * @package Drupal\blizz_bulk_creator\Services
 */
class BulkcreateAdministrationHelper implements BulkcreateAdministrationHelperInterface {

  /**
   * Drupal's entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal's entity type bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfoService;

  /**
   * Drupal's field widget plugin manager service.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $fieldWidgetPluginManager;

  /**
   * Custom service to ease the handling of media entities.
   *
   * @var \Drupal\blizz_bulk_creator\Services\EntityHelperInterface
   */
  protected $entityHelper;

  /**
   * Defined bulkcreate configurations.
   *
   * @var \Drupal\blizz_bulk_creator\Entity\BulkcreateConfigurationInterface[]
   */
  protected $bulkcreateConfigurations;

  /**
   * Configured bulkcreate usages.
   *
   * @var \Drupal\blizz_bulk_creator\Entity\BulkcreateUsageInterface[]
   */
  protected $bulkcreateUsages;

  /**
   * Bulkcreations grouped by entity type.
   *
   * @var array
   */
  protected $groupedBulkcreations;

  /**
   * Drupal's service to translate strings into other languages.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translator;

  /**
   * BulkcreateAdministrationHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Drupal's entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_bundle_info_service
   *   Drupal's entity type bundle information service.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $field_widget_plugin_manager_service
   *   Drupal's field widget plugin manager service.
   * @param \Drupal\blizz_bulk_creator\Services\EntityHelperInterface $entity_helper
   *   Custom service to ease the handling of media entities.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translator
   *   Drupal's service to translate strings into other languages.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_bundle_info_service,
    PluginManagerInterface $field_widget_plugin_manager_service,
    EntityHelperInterface $entity_helper,
    TranslationInterface $translator
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityBundleInfoService = $entity_bundle_info_service;
    $this->fieldWidgetPluginManager = $field_widget_plugin_manager_service;
    $this->entityHelper = $entity_helper;
    $this->translator = $translator;
  }

  /**
   * {@inheritdoc}
   */
  public function getBulkcreateConfigurations() {
    return !empty($this->bulkcreateConfigurations)
      ? $this->bulkcreateConfigurations
      : ($this->bulkcreateConfigurations = $this->entityTypeManager->getStorage('bulkcreate_configuration')->loadMultiple());
  }

  /**
   * {@inheritdoc}
   */
  public function getAllActiveBulkcreations() {
    return !empty($this->bulkcreateUsages)
      ? $this->bulkcreateUsages
      : ($this->bulkcreateUsages = $this->entityTypeManager->getStorage('bulkcreate_usage')->loadMultiple());
  }

  /**
   * {@inheritdoc}
   */
  public function getBulkcreationsByEntityType($entity_type_id = NULL) {

    if (empty($this->groupedBulkcreations)) {

      // Get all active bulkcreations.
      $configured_bulkcreations = $this->getAllActiveBulkcreations();

      // Extract all entity type ids used in all bulkcreations.
      $this->groupedBulkcreations = array_map(
        function (BulkcreateUsageInterface $usage) {
          return $usage->get('entity_type_id');
        },
        $configured_bulkcreations
      );

      // Remove doublettes.
      $this->groupedBulkcreations = array_unique($this->groupedBulkcreations);

      // We use the entity type id as the array index.
      $this->groupedBulkcreations = array_flip($this->groupedBulkcreations);

      // Filter the configured bulkcreations by entity type id.
      foreach ($this->groupedBulkcreations as $entity_type => &$bulkcreations) {
        $bulkcreations = array_filter(
          $configured_bulkcreations,
          function (BulkcreateUsageInterface $usage) use ($entity_type) {
            return $usage->get('entity_type_id') == $entity_type;
          }
        );
      }

    }

    // Return the usages requested (if any).
    if ($entity_type_id === NULL) {
      return $this->groupedBulkcreations;
    }
    elseif (!empty($this->groupedBulkcreations[$entity_type_id])) {
      return $this->groupedBulkcreations[$entity_type_id];
    }
    else {
      return [];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getBulkcreateConfigurationOptions() {
    return array_map(
      function (BulkcreateConfigurationInterface $bulkcreate_configuration) {
        return $bulkcreate_configuration->label();
      },
      $this->getBulkcreateConfigurations()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBulkcreateUsage($id) {
    return $this->entityTypeManager->getStorage('bulkcreate_usage')->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getBulkcreateUsages(BulkcreateConfigurationInterface $bulkcreate_configuration) {
    return $this->entityTypeManager->getStorage('bulkcreate_usage')->loadByProperties([
      'bulkcreate_configuration' => $bulkcreate_configuration->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getApplicableTargetFields(
    BulkcreateConfigurationInterface $bulkcreate_configuration,
    $entity_type_id,
    $bundle
  ) {

    // Determine fields appropriate for the desired bulkcreate target bundle.
    $referenceFields = $this->entityHelper->getReferenceFieldsForTargetBundle(
      $bulkcreate_configuration->get('target_bundle'),
      $entity_type_id,
      $bundle
    );

    // Turn these fields into usable options for a select field.
    return $this->entityHelper->flattenReferenceFieldsToOptions($referenceFields);

  }

  /**
   * {@inheritdoc}
   */
  public function getStructuredBulkcreateTargetFieldArray($entity_type_id, $bundle, $targetFieldDefinition) {
    $targetstages = array_map(
      function ($item) {
        return explode(':', $item);
      },
      explode('/', $targetFieldDefinition)
    );
    $hostentity = $this->entityTypeManager->getDefinition($entity_type_id);
    $hostentityFields = $this->entityHelper->getBundleFields($entity_type_id, $bundle);
    $result = [];
    foreach ($targetstages as $delta => $item) {
      $stage = (object) [
        'fieldname' => $item[0],
        'cardinality' => (int) $item[1],
        'hostentity' => $hostentity,
        'fieldDefinition' => $hostentityFields[$item[0]],
        'isMediaField' => ($delta == count($targetstages) - 1),
      ];
      if (isset($item[2])) {
        $stage->target_entity_type_id = $item[2];
        $stage->target_bundle = $item[3];
        $hostentity = $this->entityTypeManager->getDefinition($item[2]);
        $hostentityFields = $this->entityHelper->getBundleFields($item[2], $item[3]);
      }
      $result[] = $stage;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getBulkcreateUsagesForForm(array $form, FormStateInterface $form_state) {

    // Gather all form ids the given form provides.
    $form_ids = [$form_state->getBuildInfo()['form_id']];
    if (!empty($form_state->getBuildInfo()['base_form_id'])) {
      $form_ids[] = $form_state->getBuildInfo()['base_form_id'];
    }

    // Prepare an array to hold configured bulkcreations.
    $activeBulkcreations = [];

    // Check each configured bulkcreate usage if it applies to this form.
    foreach ($this->getAllActiveBulkcreations() as $bulkcreateUsage) {

      // Generate an array of form ids that will match the current usage.
      $usageFormIds = [
        sprintf('%s_form', $bulkcreateUsage->get('entity_type_id')),
        sprintf('%s_%s_form', $bulkcreateUsage->get('entity_type_id'), $bulkcreateUsage->get('bundle')),
      ];

      // If the arrays overlap, we have (probably) found
      // an active configuration.
      if (array_intersect($form_ids, $usageFormIds)) {

        // Only if the correct bundle is involved,
        // the configuration is really active.
        $buildInfo = $form_state->getBuildInfo();
        if (
          !empty($buildInfo['callback_object']) &&
          method_exists($buildInfo['callback_object'], 'getEntity') &&
          ($entity = $buildInfo['callback_object']->getEntity()) &&
          $entity->bundle() == $bulkcreateUsage->get('bundle')
        ) {
          $activeBulkcreations[] = $bulkcreateUsage;
        }
      }

    }

    // Return the configurations found (if any).
    return $activeBulkcreations;

  }

  /**
   * {@inheritdoc}
   */
  public function dynamicPermissions() {
    $bulkcreations = $this->getBulkcreateConfigurations();
    $dynamic_permissions = [];
    foreach ($bulkcreations as $bulkcreation) {
      $dynamic_permissions["use bulkcreation {$bulkcreation->id()}"] = $this->translator->translate(
        'Use Bulkcreation configuration %bulkcreation',
        [
          '%bulkcreation' => $bulkcreation->label(),
        ]
      );
    }
    return $dynamic_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidget(
    $field_name,
    FieldDefinitionInterface $field_definition,
    EntityAdapter $entityAdapter,
    array $form,
    FormStateInterface $form_state,
    $widget_type = 'default_widget'
  ) {

    // Create an instance of the appropriate field widget plugin.
    $fieldWidgetplugin = $this->fieldWidgetPluginManager->getInstance([
      'field_definition' => $field_definition,
      'form_mode' => 'default',
      'prepare' => FALSE,
      'configuration' => [
        'type' => $widget_type,
        'settings' => $field_definition->getSettings(),
        'third_party_settings' => [],
      ],
    ]);

    // Create the correct FieldItemList type.
    $fieldItemList = in_array(
        $field_definition->getType(),
        ['entity_reference', 'entity_reference_revisions']
      )
      ? new EntityReferenceFieldItemList($field_definition, $field_name, $entityAdapter)
      : new FieldItemList($field_definition, $field_name, $entityAdapter);

    // Create and return the form widget.
    return $fieldWidgetplugin->form(
      $fieldItemList,
      $form,
      $form_state
    );

  }

}
