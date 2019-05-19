<?php

namespace Drupal\entity_extra\Form;

use Drupal\system\Form\PrepareModulesEntityUninstallForm as DPrepareModulesEntityUninstallForm;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Shows a confirmation for to delete entities of a specific bundle before
 * uninstalling a module.
 */
class PrepareModulesEntityUninstallForm extends DPrepareModulesEntityUninstallForm {

  /**
   * The bundle of the entities to delete.
   *
   * @var string
   */
  protected $bundle;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_extra_prepare_modules_entity_uninstall';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);
    if (!empty($this->bundle)) {
      $bundle_entity_type_id = $entity_type->getBundleEntityType();
      $storage = $this->entityTypeManager->getStorage($bundle_entity_type_id);
      $bundle_entity = $storage->load($this->bundle);
      $question = $this->t('Are you sure you want to delete all @entity_type_plural of type %bundle?', [
        '@entity_type_plural' => $entity_type->getPluralLabel(),
        '%bundle' => $bundle_entity->label(),
      ]);
    }
    else {
      $question = $this->t('Are you sure you want to delete all @entity_type_plural?', [
        '@entity_type_plural' => $entity_type->getPluralLabel(),
      ]);
    }
    return $question;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);
    if (!empty($this->bundle)) {
      $bundle_entity_type_id = $entity_type->getBundleEntityType();
      $bundle_entity_type = $this->entityTypeManager->getDefinition($bundle_entity_type_id);
      $storage = $this->entityTypeManager->getStorage($bundle_entity_type_id);
      $bundle_entity = $storage->load($this->bundle);
      $text = $this->t('Delete all @entity_type_plural of type %bundle', [
        '@entity_type_plural' => $entity_type->getPluralLabel(),
        '%bundle' => $bundle_entity->label(),
      ]);
    }
    else {
      $text = $this->t('Delete all @entity_type_plural', [
        '@entity_type_plural' => $entity_type->getPluralLabel(),
      ]);
    }
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL) {
    $this->entityTypeId = $entity_type_id;
    $this->bundle = $bundle;
    if (!$this->entityTypeManager->hasDefinition($this->entityTypeId)) {
      throw new NotFoundHttpException();
    }
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $bundle_key = $entity_type->getKey('bundle');

    $form = ConfirmFormBase::buildForm($form, $form_state);
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $query = $storage->getQuery();
    if ($bundle_key && $this->bundle) {
      // If bundle is provided, counts only entities of that bundle.
      $query->condition($bundle_key, $this->bundle);
    }
    $count = $query->count()->execute();
    $form['entity_type_id'] = [
      '#type' => 'value',
      '#value' => $entity_type_id,
    ];
    $form['bundle'] = [
      '#type' => 'value',
      '#value' => $bundle,
    ];

    // Display a list of the 10 entity labels, if possible.
    if ($count == 0) {
      $form['total'] = [
        '#markup' => $this
          ->t('There are 0 @entity_type_plural to delete.', [
          '@entity_type_plural' => $entity_type
            ->getPluralLabel(),
        ]),
      ];
    }
    elseif ($entity_type->hasKey('label')) {
      $query = $storage->getQuery();
      if ($bundle_key && $this->bundle) {
        // If bundle is provided, shows only entities of that bundle.
        $query->condition($bundle_key, $this->bundle);
      }
      $recent_entity_ids = $query->sort($entity_type
        ->getKey('id'), 'DESC')
        ->pager(10)
        ->execute();
      $recent_entities = $storage->loadMultiple($recent_entity_ids);
      $labels = [];
      foreach ($recent_entities as $entity) {
        $labels[] = $entity->label();
      }
      if ($labels) {
        $form['recent_entity_labels'] = [
          '#theme' => 'item_list',
          '#items' => $labels,
        ];
        $more_count = $count - count($labels);
        $form['total'] = [
          '#markup' => $this
            ->formatPlural($more_count, 'And <strong>@count</strong> more @entity_type_singular.', 'And <strong>@count</strong> more @entity_type_plural.', [
            '@entity_type_singular' => $entity_type->getSingularLabel(),
            '@entity_type_plural' => $entity_type->getPluralLabel(),
          ]),
          '#access' => (bool) $more_count,
        ];
      }
    }
    else {
      $form['total'] = [
        '#markup' => $this
          ->formatPlural($count, 'This will delete <strong>@count</strong> @entity_type_singular.', 'This will delete <strong>@count</strong> @entity_type_plural.', [
          '@entity_type_singular' => $entity_type->getSingularLabel(),
          '@entity_type_plural' => $entity_type->getPluralLabel(),
        ]),
      ];
    }
    $form['description']['#prefix'] = '<p>';
    $form['description']['#suffix'] = '</p>';
    $form['description']['#weight'] = 5;

    // Only show the delete button if there are entities to delete.
    $form['actions']['submit']['#access'] = (bool) $count;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type_id = $form_state->getValue('entity_type_id');
    $bundle = $form_state->getValue('bundle');
    $entity_type_plural = $this->entityTypeManager
      ->getDefinition($entity_type_id)
      ->getPluralLabel();
    $batch = [
      'title' => t('Deleting @entity_type_plural', [
        '@entity_type_plural' => $entity_type_plural,
      ]),
      'operations' => [
        [
          [
            __CLASS__,
            'deleteContentEntities',
          ],
          [
            $entity_type_id,
            $bundle,
          ],
        ],
      ],
      'finished' => [
        __CLASS__,
        'moduleBatchFinished',
      ],
      'progress_message' => '',
    ];
    batch_set($batch);
  }

  /**
   * {@inheritdoc}
   */
  public static function deleteContentEntities($entity_type_id, $bundle, &$context) {
    $storage = \Drupal::entityTypeManager()
      ->getStorage($entity_type_id);
    $entity_type = \Drupal::entityTypeManager()
      ->getDefinition($entity_type_id);
    $bundle_key = $entity_type->getKey('bundle');

    // Set the entity type ID in the results array so we can access it in the
    // batch finished callback.
    $context['results']['entity_type_id'] = $entity_type_id;
    $context['results']['bundle'] = $bundle;
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $query = $storage->getQuery();
      if (!empty($bundle)) {
        $query->condition($bundle_key, $bundle);
      }
      $context['sandbox']['max'] = $query->count()->execute();
    }
    $query = $storage->getQuery();
    if (!empty($bundle)) {
      $query->condition($bundle_key, $bundle);
    }
    $entity_ids = $query
      ->sort($entity_type->getKey('id'), 'ASC')
      ->range(0, 10)
      ->execute();
    if ($entities = $storage->loadMultiple($entity_ids)) {
      $storage->delete($entities);
    }

    // Sometimes deletes cause secondary deletes. For example, deleting a
    // taxonomy term can cause it's children to be be deleted too.
    $query = $storage->getQuery();
    if (!empty($bundle)) {
      $query->condition($bundle_key, $bundle);
    }
    $context['sandbox']['progress'] = $context['sandbox']['max'] - $query
      ->count()
      ->execute();

    // Inform the batch engine that we are not finished and provide an
    // estimation of the completion level we reached.
    if (count($entity_ids) > 0 && $context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
      $context['message'] = t('Deleting items... Completed @percentage% (@current of @total).', [
        '@percentage' => round(100 * $context['sandbox']['progress'] / $context['sandbox']['max']),
        '@current' => $context['sandbox']['progress'],
        '@total' => $context['sandbox']['max'],
      ]);
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function moduleBatchFinished($success, $results, $operations) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_type = $entity_type_manager->getDefinition($results['entity_type_id']);
    $entity_type_plural = $entity_type->getPluralLabel();
    if (!empty($results['bundle'])) {
      $bundle_entity_type_id = $entity_type->getBundleEntityType();
      $storage = $entity_type_manager->getStorage($bundle_entity_type_id);
      $bundle_entity = $storage->load($results['bundle']);
      $message = t('All @entity_type_plural of type %bundle have been deleted.', [
        '@entity_type_plural' => $entity_type_plural,
        '%bundle' => $bundle_entity->label(),
      ]);
    }
    else {
      $message = t('All @entity_type_plural have been deleted.', [
        '@entity_type_plural' => $entity_type_plural,
      ]);
    }
    drupal_set_message($message);
    return new RedirectResponse(Url::fromRoute('system.modules_uninstall')
      ->setAbsolute()
      ->toString());
  }

}
