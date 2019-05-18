<?php
/**
 * @file
 * Contains \Drupal\powertagging\Form\PowerTaggingTagContentForm.
 */

namespace Drupal\powertagging\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\powertagging\Entity\PowerTaggingConfig;
use Drupal\powertagging\Plugin\Field\FieldType\PowerTaggingTagsItem;
use Drupal\powertagging\PowerTagging;
use Drupal\semantic_connector\SemanticConnector;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PowerTaggingTagContentForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'powertagging_tag_content_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param PowerTaggingConfig $powertagging_config
   *   An associative array containing the structure of the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, PowerTaggingConfig $powertagging_config = NULL) {
    $fields = $powertagging_config->getFields();
    if (!empty($fields)) {
      // Check if the extraction model is up to date.
      $extraction_model_notifications = PowerTagging::checkExtractionModels($powertagging_config, FALSE);
      if (!empty($extraction_model_notifications)) {
        // Fixed values for the formatter.
        $form['extraction_model_refresh_required'] = array(
          '#type' => 'markup',
          '#markup' => '<div class="messages warning">' . $extraction_model_notifications[0] . '</div>',
        );
      }

      // Fixed values for the formatter.
      $form['powertagging_config'] = [
        '#type' => 'value',
        '#value' => $powertagging_config,
      ];

      $form['content_types'] = [
        '#title' => t('Entity types to be included in the batch process'),
        '#type' => 'checkboxes',
        '#options' => $powertagging_config->renderFields('option_list', $fields),
        '#required' => TRUE,
      ];

      $form['skip_tagged_content'] = [
        '#title' => t('Skip already tagged content'),
        '#type' => 'radios',
        '#options' => [
          '1' => t('Yes'),
          '0' => t('No'),
        ],
        '#default_value' => TRUE,
      ];

      $form['entities_per_request'] = [
        '#type' => 'number',
        '#title' => t('Entities per request'),
        '#description' => t('The number of entities, that get processed during one HTTP request. (Allowed value range: 1 - 100)') . '<br />' . t('The higher this number is, the less HTTP requests have to be sent to the server until the batch finished tagging ALL your entities, what results in a shorter duration of the bulk tagging process.') . '<br />' . t('Numbers too high can result in a timeout, which will break the whole bulk tagging process.') . '<br />' . t('If entities are configured to get tagged with uploaded files, a value of 5 or below is recommended.'),
        '#required' => TRUE,
        '#default_value' => '10',
        '#min' => 1,
        '#max' => 100,
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => t('Start process'),
        '#attributes' => ['class' => ['button--primary']],
      ];
      $form['cancel'] = array(
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => Url::fromRoute('entity.powertagging.edit_config_form', ['powertagging' => $powertagging_config->id()]),
        '#suffix' => '</div>',
      );
    }
    else {
      \Drupal::messenger()->addMessage(t('No taggable content types found for PowerTagging configuration "%ptconfname".', array('%ptconfname' => $powertagging_config->getTitle())), 'error');
      return new RedirectResponse(Url::fromRoute('entity.powertagging.edit_config_form', ['powertagging' => $powertagging_config->id()])->toString());
    }

    if (\Drupal::request()->query->has('destination')) {
      $destination = \Drupal::request()->get('destination');
      $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . $destination);
    }
    else {
      $url = Url::fromRoute('entity.powertagging.edit_config_form', ['powertagging' => $powertagging_config->id()]);
    }
    $form['cancel'] = [
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => $url,
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entities_per_request = $form_state->getValue('entities_per_request');
    if (empty($entities_per_request) || !ctype_digit($entities_per_request) || (int) $entities_per_request == 0 || (int) $entities_per_request > 100) {
      $form_state->setErrorByName('entities_per_request', t('Only values in the range of 1 - 100 are allowed for field "Entities per request"'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var PowerTaggingConfig $powertagging_config */
    $powertagging_config = $form_state->getValue('powertagging_config');
    $configuration = $powertagging_config->getConfig();
    $entities_per_request = $form_state->getValue('entities_per_request');
    $content_types = $form_state->getValue('content_types');

    $start_time = time();
    $total = 0;
    $batch = [
      'title' => t('Tagging entities'),
      'operations' => [],
      'init_message' => t('Start with the tagging of the entities.'),
      'progress_message' => t('Process @current out of @total.'),
      'finished' => [$this, 'tagContentBatchFinished'],
    ];

    foreach ($content_types as $content_type) {
      if (empty($content_type)) {
        continue;
      }
      list($entity_type_id, $bundle, $field_type) = explode('|', $content_type);

      // If the entity type is not supported, throw an error and continue.
      if (!in_array($entity_type_id, ['node', 'user', 'taxonomy_term'])) {
        drupal_set_message(t('Entity type "%entitytype" is not supported in bulk tagging.', ['%entitytype' => $entity_type_id]), 'error');
        continue;
      }

      $field_info = [
        'entity_type_id' => $entity_type_id,
        'bundle' => $bundle,
        'field_type' => $field_type,
      ];
      $powertagging = new PowerTagging($powertagging_config);
      $tag_settings = $powertagging->buildTagSettings($field_info, array('skip_tagged_content' => $form_state->getValue('skip_tagged_content')));

      // Remove PowerTagging Config object from the settings for more memory
      // resources during the batch process.
      unset($tag_settings['powertagging_config']);

      // Get all entities for the given content type.
      $entity_ids = [];
      switch ($entity_type_id) {
        case 'node':
          $entity_ids = \Drupal::entityQuery($entity_type_id)
            ->condition('type', $bundle)
            ->execute();
          break;

        case 'user':
          $entity_ids = \Drupal::entityQuery($entity_type_id)
            ->execute();
          // Remove the user with the ID = 0.
          array_shift($entity_ids);
          break;

        case 'taxonomy_term':
          $entity_ids = \Drupal::entityQuery($entity_type_id)
            ->execute();
          break;
      }
      $count = count($entity_ids);

      $total += $count;
      for ($i = 0; $i < $count; $i += $entities_per_request) {
        $entities = array_slice($entity_ids, $i, $entities_per_request);
        $batch['operations'][] = [
          [$this, 'tagContentBatchProcess'],
          [
            $entities,
            $entity_type_id,
            $field_type,
            $tag_settings,
          ],
        ];
      }
    }

    // Add for each operation some batch info data.
    $batch_info = [
      'total' => $total,
      'start_time' => $start_time,
    ];
    foreach ($batch['operations'] as &$operation) {
      $operation[1][] = $batch_info;
    }

    batch_set($batch);
    return TRUE;
  }

  /**
   * Update the powertagging tags of one powertagging field of a single entity.
   *
   * @param array $entity_ids
   *   A single ID or an array of IDs of entities, depending on the entity type
   * @param string $entity_type_id
   *   The entity type ID of the entity (e.g. node, user, ...).
   * @param string $field_type
   *   The field type of the powertagging field.
   * @param array $tag_settings
   *   An array of settings used during the process of extraction.
   * @param array $batch_info
   *   An associative array of information about the batch process.
   * @param array $context
   *   The Batch context to transmit data between different calls.
   */
  public static function tagContentBatchProcess(array $entity_ids, $entity_type_id, $field_type, array $tag_settings, array $batch_info, &$context) {
    if (!isset($context['results']['processed'])) {
      $context['results']['processed'] = 0;
      $context['results']['tagged'] = 0;
      $context['results']['skipped'] = 0;
      $context['results']['error_count'] = 0;
      $context['results']['error'] = array();
      $context['results']['powertagging_id'] = $tag_settings['powertagging_id'];
    }

    // Add the PowerTagging configuration object to the $tag_settings.
    $powertagging_config = PowerTaggingConfig::load($tag_settings['powertagging_id']);
    $tag_settings['powertagging_config'] = $powertagging_config;

    // Load the entities.
    $entities = [];
    try {
      $entities = \Drupal::entityTypeManager()
        ->getStorage($entity_type_id)
        ->loadMultiple($entity_ids);
    }
    catch (\Exception $e) {
      watchdog_exception('PowerTagging Batch Process', $e, 'Unable to load entities with ids: %ids. ' . $e->getMessage(), array('%ids' => print_r($entity_ids, TRUE)));
      $context['results']['processed'] += count($entity_ids);
      $context['results']['error_count'] += count($entity_ids);
      $context['results']['error']['loading'] = array_merge($context['results']['error']['loading'], $entity_ids);
    }

    $powertagging = new PowerTagging($powertagging_config);
    $powertagging->tagEntities($entities, $field_type, $tag_settings, $context);

    // Show the remaining time as a batch message.
    $time_string = '';
    if ($context['results']['processed'] > 0) {
      $remaining_time = floor((time() - $batch_info['start_time']) / $context['results']['processed'] * ($batch_info['total'] - $context['results']['processed']));
      if ($remaining_time > 0) {
        $time_string = (floor($remaining_time / 86400)) . 'd ' . (floor($remaining_time / 3600) % 24) . 'h ' . (floor($remaining_time / 60) % 60) . 'm ' . ($remaining_time % 60) . 's';
      }
      else {
        $time_string = t('Done.');
      }
    }

    $context['message'] = t('Processed entities: %currententities of %totalentities. (Tagged: %taggedentities, Skipped: %skippedentities, errors: %error_entities)', [
      '%currententities' => $context['results']['processed'],
      '%taggedentities' => $context['results']['tagged'],
      '%skippedentities' => $context['results']['skipped'],
      '%error_entities' => $context['results']['error_count'],
      '%totalentities' => $batch_info['total'],
    ]);
    $context['message'] .= '<br />' . t('Remaining time: %remainingtime.', ['%remainingtime' => $time_string]);
  }

  /**
   * Batch 'finished' callback used by PowerTagging Bulk Tagging.
   */
  public static function tagContentBatchFinished($success, $results, $operations) {
    if ($success) {
      $message = t('Successfully finished content tagging of %total_entities entities on %date:', [
          '%total_entities' => $results['processed'],
          '%date' => \Drupal::service('date.formatter')->format($results['end_time'], 'short')
        ]) . '<br />';
      $message .= t('<ul><li>tagged: %tagged_entities</li><li>skipped: %skipped_entities</li><li>errors: %error_entities</li></ul>', [
        '%tagged_entities' => $results['tagged'],
        '%skipped_entities' => $results['skipped'],
        '%error_entities' => self::createErrorList($results['error']),
      ]);
      drupal_set_message(new FormattableMarkup($message, array()));

      if (isset($results['powertagging_id'])) {
        // Update the time of the most recent batch.
        $powertagging_config = PowerTaggingConfig::load($results['powertagging_id']);
        $settings = $powertagging_config->getConfig();
        $settings['last_batch_tagging'] = time();
        $powertagging_config->setConfig($settings);
        $powertagging_config->save();

        // If there are any global notifications and they could be caused by a
        // missing retagging action, refresh the notifications.
        $notifications = \Drupal::config('semantic_connector.settings')->get('global_notifications');
        if (!empty($notifications)) {
          $notification_config = SemanticConnector::getGlobalNotificationConfig();
          if (isset($notification_config['actions']['powertagging_retag_content']) && $notification_config['actions']['powertagging_retag_content']) {
            SemanticConnector::checkGlobalNotifications(TRUE);
          }
        }
      }
    }
    else {
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation on %date', array(
          '%error_operation' => $error_operation[0],
          '%date' => \Drupal::service('date.formatter')->format($results['end_time'], 'short'),
        )) . '<br />';
      $message .= t('<ul><li>arguments: %arguments</li></ul>', array(
        '@arguments' => print_r($error_operation[1], TRUE),
      ));
      drupal_set_message($message, 'error');
    }
  }

  /**
   * Creates an error list with links to entities.
   *
   * @param array $errors
   *   A list of entity-ids grouped by entity type and error type.
   *
   * @return string
   *   An unsorted list of links to entities.
   */
  private static function createErrorList($errors) {
    if (empty($errors)) {
      return '0';
    }
    $item_types = array();
    foreach ($errors as $error_type => $entities) {
      $items = array();
      foreach ($entities as $entity_type => $ids) {
        $current_entities = [];
        try {
          $current_entities = \Drupal::entityTypeManager()
            ->getStorage($entity_type)
            ->loadMultiple($ids);
        }
        catch (\Exception $exception) {}

        /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
        foreach ($current_entities as $entity) {
          $items[] = '<li>' . Link::fromTextAndUrl($entity->label(), $entity->toUrl())->toString() . '</li>';
        }
      }
      $item_types[] = '<li>' . t($error_type) . ': <ul>' . implode('', $items) . '</ul></li>';
    }

    return '<ul>' . implode('', $item_types) . '</ul>';
  }
}