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
class FieldBulkCloneForm extends FormBase {

  use BundleDestinationOptionsTrait;

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
    return 'field_tools_field_clone_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL) {
    $field_ids = $this->queryFactory->get('field_config')
      ->condition('entity_type', $entity_type_id)
      ->condition('bundle', $bundle)
      ->execute();
    $current_bundle_fields = $this->entityTypeManager->getStorage('field_config')->loadMultiple($field_ids);

    $field_options = array();
    foreach ($current_bundle_fields as $field_id => $field) {
      $field_options[$field_id] = t("@field-label (machine name: @field-name)", array(
        '@field-label' => $field->getLabel(),
        '@field-name' => $field->getName(),
      ));
    }
    asort($field_options);

    $form['fields'] = array(
      '#title' => t('Fields to clone'),
      '#type' => 'checkboxes',
      '#options' => $field_options,
      '#description' => t("Select fields to clone onto one or more bundles."),
    );

    $form['destinations'] = [
      '#type' => 'checkboxes',
      '#title' => t("Bundles to clone the fields to"),
      '#options' => $this->getDestinationOptions($this->entityTypeManager, $this->entityTypeBundleInfo),
    ];

    // Mark the current bundle as disabled.
    $form['destinations']["$entity_type_id::$bundle"]['#disabled'] = TRUE;
    $form['destinations']["$entity_type_id::$bundle"]['#description'] = t("This is the current bundle.");

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Clone fields'),
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

    $destinations = array_filter($form_state->getValue('destinations'));
    $fields_to_clone = array_filter($form_state->getValue('fields'));

    foreach ($destinations as $destination) {
      list ($destination_entity_type, $destination_bundle) = explode('::', $destination);

      foreach ($fields_to_clone as $field_id) {
        $field_config = $this->entityTypeManager->getStorage('field_config')->load($field_id);

        // Check the field is not already on the destination bundle.
        $field_ids = $this->queryFactory->get('field_config')
          ->condition('entity_type', $destination_entity_type)
          ->condition('bundle', $destination_bundle)
          ->condition('field_name', $field_config->getName())
          ->execute();

        if ($field_ids) {
          drupal_set_message(t("Field @name is already on @entity_type @bundle, skipping.", [
            '@name' => $field_config->getName(),
            // TODO: use labels!
            '@entity_type' => $destination_entity_type,
            '@bundle' => $destination_bundle,
          ]));

          continue;
        }

        // Check the field is not already on the destination entity type but
        // with a different type.
        $existing_destination_field_storage_ids = $this->queryFactory->get('field_storage_config')
          ->condition('entity_type', $destination_entity_type)
          ->condition('field_name', $field_config->getName())
          ->execute();
        if ($existing_destination_field_storage_ids) {
          // There will be only one.
          $existing_field_storage_config = $this->entityTypeManager->getStorage('field_storage_config')->load(reset($existing_destination_field_storage_ids));

          if ($existing_field_storage_config->getType() != $field_config->getType()) {
            drupal_set_message(t("Field @name is already on @entity_type with a different field type, skipping.", [
              '@name' => $field_config->getName(),
              // TODO: use labels!
              '@entity_type' => $destination_entity_type,
            ]));

            continue;
          }
        }

        $this->fieldCloner->cloneField($field_config, $destination_entity_type, $destination_bundle);
      }
    }

    drupal_set_message(t("The fields have been cloned."));
  }

}
