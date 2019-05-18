<?php

namespace Drupal\field_tools\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_tools\FieldCloner;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to clone multiple fields from an entity bundle.
 */
class ConfigFieldsExportToCodeForm extends FormBase {

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
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The field cloner.
   *
   * @var \Drupal\field_tools\FieldCloner
   */
  protected $fieldCloner;

  /**
   * Creates a Clone instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\field_tools\FieldCloner $field_cloner
   *   The field cloner.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, QueryFactory $query_factory, FieldCloner $field_cloner) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->queryFactory = $query_factory;
    $this->fieldCloner = $field_cloner;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.query'),
      $container->get('field_tools.field_cloner')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_tools_field_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL) {
    $this->entityTypeId = $entity_type_id;
    $this->bundle = $bundle;

    $field_manager = \Drupal::service('entity_field.manager');
    $field_definitions = $field_manager->getFieldDefinitions($entity_type_id, $bundle);

    $code = [];
    if (!empty($form_state->getValues())) {
      $code = [];

      foreach (array_filter($form_state->getValues()['fields']) as $field_name) {
        $this->getBaseFieldCode($code, $field_definitions[$field_name]);
        // TODO: bundle fields and config field conversion.

        $code[] = '';
      }

      $form['code'] = [
        '#type' => 'textarea',
        '#value' => implode("\n", $code),
        '#rows' => count($code) + 1,
      ];
    }

    $field_options = [];
    foreach ($field_definitions as $field_id => $field) {
      $field_options[$field_id] = t("@field-label (machine name: @field-name)", array(
        '@field-label' => $field->getLabel(),
        '@field-name' => $field->getName(),
      ));
    }
    asort($field_options);

    $form['fields'] = array(
      '#title' => t('Fields to export to code'),
      '#type' => 'checkboxes',
      '#options' => $field_options,
      '#description' => t("Select fields to export as entity class code."),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Export field definitions to code'),
    );

    return $form;
  }

  protected function getBaseFieldCode(&$code, $field) {
    $code[] = sprintf("\$fields['%s'] = \Drupal\Core\Field\BaseFieldDefinition::create('%s')",
      $field->getName(),
      $field->getType()
    );
    $code[] = sprintf("->setLabel(t('%s'))", $field->getLabel());
    $code[] = sprintf("->setDescription(t('%s'))", $field->getDescription());
    if ($field->isRequired()) {
      $code[] = "  ->setRequired(TRUE)";
    }

    if ($field instanceof \Drupal\Core\Field\FieldStorageDefinitionInterface) {
      if ($field->isRevisionable()) {
        $code[] = "  ->setRevisionable(TRUE)";
      }
    }

    if ($field->isTranslatable()) {
      $code[] = "  ->setTranslatable(TRUE)";
    }

    foreach ($field->getSettings() as $setting => $value) {
      if (is_string($value)) {
        $code[] = "  ->setSetting('$setting' => '$value')";
      }
      elseif (is_bool($value)) {
        $code[] = "  ->setSetting('$setting' => " . ($value ? 'TRUE' : 'FALSE') . ")";
      }
      else {
        // TODO: format the output code properly!
        $code[] = "  ->setSetting('$setting' => " . var_export($value, TRUE) . ")";
      }
    }

    foreach (['form', 'view'] as $display_type) {
      $display = $this->entityTypeManager->getStorage("entity_{$display_type}_display")->load(
        $this->entityTypeId . '.' . $this->bundle . '.' . 'default'
      );
      if ($display) {
        $display_settings = $display->getComponent($field->getName());

        $code[] = "  ->setDisplayOptions('{$display_type}', [";
        $code[] = "    'type' => '{$display_settings['type']}',";
        $code[] = "    'weight' => '{$display_settings['weight']}',";
        if (!empty($display_settings['label'])) {
          $code[] = "    'label' => '{$display_settings['label']}',";
        }
        if (!empty($display_settings['settings'])) {
          $code[] = "    'settings' => [";
          foreach ($display_settings['settings'] as $key => $value) {
            $code[] = "      '$key' => '$value',";
          }
          $code[] = "    ],";
        }
        $code[] = "  ])";
        $code[] = "  ->setDisplayConfigurable('{$display_type}', TRUE)";
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

}
