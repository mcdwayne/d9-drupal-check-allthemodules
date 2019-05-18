<?php

namespace Drupal\entity_usage\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to launch batch tracking of existing entities.
 */
class BatchUpdateForm extends FormBase {

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * BatchUpdateForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The EntityTypeManager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_update_batch_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $entity_types = $this->entityTypeManager->getDefinitions();
    $types = [];
    foreach ($entity_types as $type => $entity_type) {
      // Only look for content entities.
      if ($entity_type->entityClassImplements('\Drupal\Core\Entity\ContentEntityInterface')) {
        $types[$type] = new FormattableMarkup('@label (@machine_name)', [
          '@label' => $entity_type->getLabel(),
          '@machine_name' => $type,
        ]);
      }
    }

    $form['description'] = [
      '#markup' => $this->t("This form allows you to reset and track again all entity usages in your system.<br /> It may be useful if you want to have available the information about the relationships between entities before installing the module.<br /><b>Be aware though that using this operation will delete all tracked statistics and recreate everything again.</b>"),
    ];
    $form['host_entity_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Delete and recreate all usage statistics for these entity types:'),
      '#options' => $types,
      '#default_value' => array_keys($types),
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Recreate entity usage statistics'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $host_entity_types = array_filter($form_state->getValue('host_entity_types'));

    // Generate a batch to recreate the statistics for all entities.
    // Note that if we force all statistics to be created, there is no need to
    // separate them between host / target cases. If all entities are going to
    // be re-tracked, tracking all of them as hosts is enough, because there
    // could never be a target without host.
    $batch = $this->generateBatch($host_entity_types);
    batch_set($batch);
  }

  /**
   * Create a batch to process the entity types in bulk.
   *
   * @param string[] $types
   *   An array of entity types ids.
   *
   * @return array
   *   The batch array.
   */
  public function generateBatch(array $types) {
    $operations = [];

    foreach ($types as $type) {
      $operations[] = ['Drupal\entity_usage\Form\BatchUpdateForm::updateHostsBatchWorker', [$type]];
    }

    $batch = [
      'operations' => $operations,
      'finished' => 'Drupal\entity_usage\Form\BatchUpdateForm::batchFinished',
      'title' => $this->t('Updating entity usage statistics.'),
      'progress_message' => $this->t('Processed @current of @total entity types.'),
      'error_message' => $this->t('This batch encountered an error.'),
    ];

    return $batch;
  }

  /**
   * Batch operation worker for recreating statistics for host entities.
   *
   * @param string $entity_type_id
   *   The entity type id, for example 'node'.
   * @param array $context
   *   The context array.
   */
  public static function updateHostsBatchWorker($entity_type_id, array &$context) {
    $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $entity_type_key = $entity_type->getKey('id');

    if (empty($context['sandbox']['total'])) {
      // Delete current usage statistics for these entities.
      \Drupal::service('entity_usage.usage')->bulkDeleteHosts($entity_type_id);

      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_id'] = -1;
      $context['sandbox']['total'] = (int) $entity_storage->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();
    }

    $entity_ids = $entity_storage->getQuery()
      ->condition($entity_type_key, $context['sandbox']['current_id'], '>')
      ->range(0, 10)
      ->accessCheck(FALSE)
      ->sort($entity_type_key)
      ->execute();

    $entities = $entity_storage->loadMultiple($entity_ids);
    foreach ($entities as $entity) {
      // Hosts are tracked as if they were new entities.
      \Drupal::service('entity_usage.entity_update_manager')->trackUpdateOnCreation($entity);

      $context['sandbox']['progress']++;
      $context['sandbox']['current_id'] = $entity->id();
      $context['results'][] = $entity_type_id . ':' . $entity->id();
    }

    if ($context['sandbox']['progress'] < $context['sandbox']['total']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['total'];
    }
    else {
      $context['finished'] = 1;
    }

    $context['message'] = t('Updating entity usage for @entity_type: @current of @total', [
      '@entity_type' => $entity_type_id,
      '@current' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['total'],
    ]);
  }

  /**
   * Finish callback for our batch processing.
   *
   * @param bool $success
   *   Whether the batch completed successfully.
   * @param array $results
   *   The results array.
   * @param array $operations
   *   The operations array.
   */
  public static function batchFinished($success, array $results, array $operations) {
    if ($success) {
      drupal_set_message(t('Recreated entity usage for @count entities.', ['@count' => count($results)]));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      drupal_set_message(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

}
