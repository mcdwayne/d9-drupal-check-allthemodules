<?php

/**
 * @file
 * Contains \Drupal\collect\Form\CollectSettingsFormBase.
 */

namespace Drupal\collect_common\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides form elements and methods for collect and collect client settings.
 */
abstract class CollectSettingsFormBase extends ConfigFormBase {

  /**
   * The injected entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The configuration name.
   *
   * @var string
   */
  protected $configurationName;

  /**
   * Creates a new CollectSettingsFormBase instance.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $configuration = $this->config($this->configurationName)->get('entity_capture');

    $form['entity_capture'] = array(
      '#title' => $this->t('Entity capturing'),
      '#type' => 'fieldgroup',
      '#description' => $this->t('Configure entity capture settings for different entity types.'),
      '#tree' => TRUE,
    );
    $display_user_reference_message = TRUE;

    foreach ($this->getContentEntityTypes() as $entity_type => $entity_type_label) {
      $capture_settings = isset($configuration[$entity_type]) ? $configuration[$entity_type] : [];
      $continuous_capturing = isset($capture_settings['continuous']);
      $reference_fields = isset($capture_settings['reference_fields']) ? $capture_settings['reference_fields'] : [];

      // Get the bundles for the entity type.
      $bundles = collect_common_get_bundles($this->entityManager, $entity_type);

      // The list of base fields used for capturing references.
      $base_reference_fields = $this->loadReferenceFields($entity_type, $bundles);

      // User references have been selected by default in case there is
      // no configuration saved for that entity type.
      $user_references = ['uid', 'recipient', 'user_id', 'revision_uid'];
      if (!$capture_settings) {
        foreach ($base_reference_fields as $base_reference_field_id => $base_reference_field) {
          if (in_array($base_reference_field_id, $user_references)) {
            $reference_fields[] = $base_reference_field_id;
            if ($display_user_reference_message) {
              drupal_set_message($this->t('Standard user references have been selected by default.'), 'warning');
              $display_user_reference_message = FALSE;
            }
          }
        }
      }

      $form['entity_capture'][$entity_type] = array(
        '#type' => 'details',
        '#title' => $this->t('@entity_type', ['@entity_type' => $entity_type_label]),
        '#open' => $continuous_capturing || $reference_fields,
      );

      $form['entity_capture'][$entity_type]['fields'] = array(
        '#title' => $this->t('List of fields for reference capturing'),
        '#type' => 'fieldgroup',
        '#access' => (bool) $base_reference_fields,
        '#description' => $this->t('You can select fields that can be used to capture referenced entities.'),
      );
      $headers = [
        'field_label' => $this->t('Field label'),
        'description' => $this->t('Description'),
        'target_type' => $this->t('Target type'),
      ];
      if ($bundles) {
        $headers['usage'] = $this->t('Used in');
      }

      $form['entity_capture'][$entity_type]['fields']['reference_fields'] = array(
        '#title' => $this->t('List of fields for reference capturing'),
        '#type' => 'tableselect',
        '#header' => $headers,
        '#options' => $base_reference_fields,
        '#default_value' => $reference_fields ? array_combine($reference_fields, $reference_fields) : [],
        '#empty' => $this->t('There are no fields that can be used for reference capturing.'),
      );

      $form['entity_capture'][$entity_type]['continuous'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Continuous entity capture'),
        '#description' => $this->t('Configure @entity_type for continuous entity capturing.', ['@entity_type' => $entity_type_label]),
        '#description_display' => 'before',
      );
      $form['entity_capture'][$entity_type]['continuous']['enable'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Automatically capture new or updated @entity_type entities.', [
          '@entity_type' => $entity_type_label,
        ]),
        '#default_value' => $continuous_capturing,
      );
      $form['entity_capture'][$entity_type]['continuous']['settings'] = array(
        '#type' => 'fieldgroup',
        '#access' => (bool) $bundles,
        '#states' => array(
          'visible' => array(
            ":input[name=\"entity_capture[$entity_type][continuous][enable]\"]" => array('checked' => TRUE),
          ),
        ),
      );
      $form['entity_capture'][$entity_type]['continuous']['settings']['default'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('What should be captured?'),
      );
      $form['entity_capture'][$entity_type]['continuous']['settings']['default']['options'] = array(
        '#type' => 'radios',
        '#default_value' => $continuous_capturing ? $capture_settings['continuous']['default'] : 'all',
        '#options' => array(
          'all' => $this->t('All (except those selected)'),
          'none' => $this->t('None (except those selected)'),
        ),
      );
      $form['entity_capture'][$entity_type]['continuous']['settings']['bundles'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Bundles'),
      );
      $form['entity_capture'][$entity_type]['continuous']['settings']['bundles']['options'] = array(
        '#type' => 'checkboxes',
        '#options' => $bundles,
        '#default_value' => $continuous_capturing ? $capture_settings['continuous']['bundles'] : [],
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $capture_settings = [];
    foreach ($form_state->getValue('entity_capture') as $entity_type_id => $entity_type_settings) {
      $continuous_capturing = $entity_type_settings['continuous']['enable'];
      $default = $entity_type_settings['continuous']['settings']['default']['options'];
      $selected_bundles = array_filter($entity_type_settings['continuous']['settings']['bundles']['options']);
      $reference_fields = array_filter($entity_type_settings['fields']['reference_fields']);
      $capture_settings[$entity_type_id]['reference_fields'] = $reference_fields ? array_keys($reference_fields) : [];

      if ((!$default || $default == 'none') && !$selected_bundles) {
        continue;
      }
      if ($continuous_capturing) {
        $capture_settings[$entity_type_id]['continuous']['default'] = $default;
        $capture_settings[$entity_type_id]['continuous']['bundles'] = $selected_bundles ?: [];
      }
    }

    $this->config($this->configurationName)->set('entity_capture', $capture_settings);
  }

  /**
   * Returns all content entity types with collect container excluded.
   */
  public function getContentEntityTypes() {
    $entity_types = $this->entityManager->getEntityTypeLabels(TRUE)['Content'];
    unset($entity_types['collect_container']);
    return $entity_types;
  }

  /**
   * Returns entity type label for given entity type.
   */
  public function getEntityTypeLabel($entity_type) {
    return $this->getContentEntityTypes()[$entity_type];
  }

  /**
   * Loads all fields for an entity type that can be referenced.
   *
   * @param string $entity_type
   *   The entity type id.
   * @param array $bundles
   *   An associative array of bundles of the given entity type.
   *
   * @return array
   *   An array of fields that can be used as references.
   */
  protected function loadReferenceFields($entity_type, array $bundles) {
    // For unbundled entity types, the entity type ID is used instead.
    if (empty($bundles)) {
      $bundles = [$entity_type => $this->entityManager->getDefinition($entity_type)->getLabel()];
    }

    // Iterate over fields of each bundle.
    $reference_fields = [];
    foreach ($bundles as $bundle_id => $bundle_label) {
      foreach ($this->entityManager->getFieldDefinitions($entity_type, $bundle_id) as $field_name => $field_definition) {
        // Skip field if it is not an entity reference field, or if its target
        // type is not a content entity.
        $field_class = $field_definition->getItemDefinition()->getClass();
        $entity_reference_class = 'Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem';
        $dynamic_entity_reference_class = 'Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceItem';
        if (($field_class != $entity_reference_class && !is_subclass_of($field_class, $entity_reference_class))
          && ($field_class != $dynamic_entity_reference_class && !is_subclass_of($field_class, $dynamic_entity_reference_class))) {
          continue;
        }

        if ($field_definition->getSetting('target_type')) {
          $target_type = $this->entityManager->getDefinition($field_definition->getSetting('target_type'));
          $reference_fields[$field_name]['target_type'] = $target_type->getLabel();
        }
        elseif ($field_definition->getSetting('entity_type_ids')) {
          $entity_type_ids = $field_definition->getSetting('entity_type_ids');
          $target_type = $this->entityManager->getDefinition(reset($entity_type_ids));
          $labels = [];
          foreach ($entity_type_ids as $key => $definition) {
            $labels[$key] = $this->entityManager->getDefinition($definition)->getLabel();
          }
          $reference_fields[$field_name]['target_type'] = ['data' => ['#markup' => implode(', ', $labels)]];
        }
        else {
          $target_type = NULL;
        }

        if (!$target_type || !$target_type instanceof ContentEntityTypeInterface) {
          continue;
        }

        $reference_fields[$field_name]['field_label'] = (string) $field_definition->getLabel();
        $reference_fields[$field_name]['description'] = (string) $field_definition->getDescription();
        $reference_fields[$field_name]['usage'] = $this->t('All bundles');

        // For configured fields, list the bundles where it is used.
        if (!$field_definition->getFieldStorageDefinition()->isBaseField()) {
          $usage = [];
          foreach ($this->entityManager->getFieldMap()[$entity_type][$field_name]['bundles'] as $usage_bundle) {
            $usage[$usage_bundle] = $bundles[$usage_bundle];
            // Link to field administration UI if it exists.
            if ($route_info = FieldUI::getOverviewRouteInfo($entity_type, $usage_bundle)) {
              $usage[$usage_bundle] = \Drupal::l($usage[$usage_bundle], $route_info);
            }
          }
          $reference_fields[$field_name]['usage'] = ['data' => ['#markup' => implode(', ', $usage)]];
        }
      }
    }

    return $reference_fields;
  }

}
