<?php

namespace Drupal\field_tools\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_tools\DisplaySettingsCopier;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to copy displays settings between displays.
 */
class EntityDisplaySettingsBulkCopyForm extends FormBase {

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
   * The entity display repository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The field cloner.
   *
   * @var \Drupal\field_tools\DisplaySettingsCopier
   */
  protected $displaySettingsCopier;

  /**
   * Creates a Clone instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\field_tools\DisplaySettingsCopier $display_settings_copier
   *   The display settings copier.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityFieldManagerInterface $entity_field_manager,
    EntityDisplayRepositoryInterface $entity_display_repository,
    QueryFactory $query_factory,
    DisplaySettingsCopier $display_settings_copier
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->queryFactory = $query_factory;
    $this->displaySettingsCopier = $display_settings_copier;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity.query'),
      $container->get('field_tools.display_settings_copier')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_tools_display_settings_copy_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL) {
    $bundle_fields = array_filter($this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle), function ($field_definition) {
      return !$field_definition->isComputed();
    });

    foreach ($bundle_fields as $field_name => $field_definition) {
      $source_field_options[$field_name] = $field_definition->getLabel();
    }
    natcasesort($source_field_options);

    $form['source_fields'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Fields to copy'),
      '#description' => t("Select the fields whose display settings should be copied."),
      '#options' => $source_field_options,
      '#required' => TRUE,
    );

    // Source display.
    $form['source_display'] = array(
      '#type' => 'select',
      '#title' => t('Source display'),
      '#description' => t("Select the display to copy settings from and to"),
      '#required' => TRUE,
      '#options' => $this->getDisplayOptions('entity_form_display', $entity_type_id, $bundle),
      // Workaround for core bug: https://www.drupal.org/node/2906113
      '#empty_value' => '',
    );

    // Destination bundles.
    $destination_bundle_options = [];
    $other_bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    foreach ($other_bundles as $other_bundle_name => $info) {
      $destination_bundle_options[$other_bundle_name] = $info['label'];
    }

    $form['destination_bundles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Destination bundles'),
      '#description' => t("Select the bundles to copy the settings to."),
      '#options' => $destination_bundle_options,
      '#required' => TRUE,
    );
    // Mark the current bundle as disabled.
    $form['destination_bundles'][$bundle]['#disabled'] = TRUE;
    $form['destination_bundles'][$bundle]['#description'] = t("This is the current bundle.");

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Copy field settings'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the original parameters given to buildForm().
    // TODO: is this the right way to do this?
    $build_info = $form_state->getBuildInfo();
    list($entity_type_id, $bundle) = $build_info['args'];

    $values = $form_state->getValues();

    $source_fields = array_filter($values['source_fields']);
    list($source_display_type, $source_display_id) = explode(':', $values['source_display']);
    $source_display = $this->entityTypeManager->getStorage($source_display_type)->load($source_display_id);
    $destination_bundles = array_filter($values['destination_bundles']);

    $bundle_fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);

    foreach ($source_fields as $source_field_name) {
      $field_definition = $bundle_fields[$source_field_name];

      foreach ($destination_bundles as $bundle) {
        $this->displaySettingsCopier->copyDisplaySettings($field_definition, $source_display, $bundle);

        drupal_set_message($this->t("Copied settings for @field-name to @bundle.", [
          // TODO: use human-readable labels here.
          '@field-name' => $source_field_name,
          '@bundle' => $bundle,
        ]));
      }
    }
  }

  /**
   * Get the form options for a display type.
   *
   * @todo refactor to a trait.
   *
   * @param $type
   *  The entity type ID of the display type.
   * @param $entity_type_id
   *  The target entity type ID of the displays.
   * @param $bundle
   *  The target bundle.
   *
   * @return
   *  An array of form options.
   */
  protected function getDisplayOptions($type, $entity_type_id, $bundle) {
    $display_options = [];

    $types = [
      'entity_form_display' => 'Form',
      'entity_view_display' => 'View',
    ];

    foreach ($types as $type => $label) {
      $display_ids = $this->queryFactory->get($type)
        ->condition('targetEntityType', $entity_type_id)
        ->condition('bundle', $bundle)
        ->execute();
      $displays = $this->entityTypeManager->getStorage($type)->loadMultiple($display_ids);

      // Unfortunately, getDisplayModesByEntityType() is protected :(
      if ($type == 'entity_form_display') {
        $mode_options = $this->entityDisplayRepository->getFormModeOptions($entity_type_id);
      }
      else {
        $mode_options = $this->entityDisplayRepository->getViewModeOptions($entity_type_id);
      }

      foreach ($displays as $id => $display) {
        // The label() method of displays returns NULL always, so we get the label
        // from the related mode.
        $display_options[$type . ':' . $id] = $label . ': ' . $mode_options[$display->getMode()];
      }

      // Sort within each type.
      asort($display_options);
    }

    return $display_options;
  }

}
