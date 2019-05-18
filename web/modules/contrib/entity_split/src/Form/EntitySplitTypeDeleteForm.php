<?php

namespace Drupal\entity_split\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_split\Entity\EntitySplitType;

/**
 * Builds the form to delete Entity split type entities.
 */
class EntitySplitTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * Number of entities to delete at a time.
   */
  const BATCH_LIMIT = 20;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $split_count = static::getEntitySplitCount($this->entity->id());

    $warning = empty($split_count) ? '' : '<p>' . $this->formatPlural($split_count,
      '%type is used by 1 entity split which also will be deleted.',
      '%type is used by @count entity splits which also will be deleted.',
      ['%type' => $this->entity->label()]) . '</p>';

    return $warning . $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return static::getEntitySplitTypeOverviewUrl();
  }

  /**
   * Returns the route of entity split type list page.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public static function getEntitySplitTypeOverviewUrl() {
    return new Url('entity.entity_split_type.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl(static::getEntitySplitTypeOverviewUrl());

    $bundle = $this->entity->id();
    $split_count = static::getEntitySplitCount($bundle);

    if ($split_count <= self::BATCH_LIMIT) {
      static::deleteEntitySplits($bundle);
      \Drupal::messenger()->addStatus(t('Entity split type @type has been deleted.', ['@type' => $this->entity->label()]));
      $this->entity->delete();
      return;
    }

    $batch = [
      'operations' => [[[get_class($this), 'batchOperation'], [$bundle]]],
      'finished' => [get_class($this), 'batchFinished'],
      'title' => $this->t('Entity split deletion'),
      'init_message' => $this->t('Starting entity split deletion.'),
      'progress_message' => '',
      'error_message' => $this->t('Entity split deletion has encountered an error.'),
    ];

    batch_set($batch);
  }

  /**
   * Returns count of existing entity split entities.
   */
  public static function getEntitySplitCount($bundle) {
    return \Drupal::entityTypeManager()->getStorage('entity_split')->getQuery()
      ->condition('type', $bundle)
      ->count()
      ->execute();
  }

  /**
   * Deletes existing entity split entities.
   */
  public static function deleteEntitySplits($bundle, $limit = 0) {
    $query = \Drupal::entityQuery('entity_split');
    $query->condition('type', $bundle);
    if ($limit > 0) {
      $query->range(0, $limit);
    }
    $query->accessCheck(FALSE);

    $entity_ids = $query->execute();

    if (!empty($entity_ids)) {
      $storage_handler = \Drupal::entityTypeManager()->getStorage('entity_split');
      $entities = $storage_handler->loadMultiple($entity_ids);
      $storage_handler->delete($entities);
    }

    return count($entity_ids);
  }

  /**
   * Batch operation function.
   */
  public static function batchOperation($bundle, &$context) {
    if (empty($context['results'])) {
      $context['results'] = [$bundle];
    }

    if (empty($context['sandbox'])) {
      $context['sandbox'] = [];
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['count'] = static::getEntitySplitCount($bundle);

      if ($context['sandbox']['count'] === 0) {
        return;
      }
    }

    $processed = static::deleteEntitySplits($bundle, self::BATCH_LIMIT);
    $context['sandbox']['progress'] += $processed;

    if ($processed === self::BATCH_LIMIT) {
      $progress = $context['sandbox']['progress'] / $context['sandbox']['max'];
      if ($progress >= 1.0) {
        $progress = 0.99;
      }
      $context['finished'] = $progress;
      $context['message'] = t('Deleted @progress entity splits out of @total.', [
        '@progress' => $context['sandbox']['progress'],
        '@total' => $context['sandbox']['count'],
      ]);
    }
  }

  /**
   * Batch finish function.
   */
  public static function batchFinished($success, array $results, array $operations) {
    if ($success) {
      $bundle = $results[0];
      $entity_split_type = EntitySplitType::load($bundle);
      \Drupal::messenger()->addStatus(t('Entity split type @type has been deleted.', ['@type' => $entity_split_type->label()]));
      $entity_split_type->delete();
    }
  }

}
