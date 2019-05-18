<?php
/**
 * @file
 * Contains \Drupal\powertagging\Form\PowerTaggingUpdateVocabularyForm.
 */

namespace Drupal\powertagging\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\powertagging\Entity\PowerTaggingConfig;
use Drupal\powertagging\PowerTagging;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

class PowerTaggingUpdateVocabularyForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'powertagging_update_vocabulary_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param PowerTaggingConfig $powertagging_config
   *   A Powertagging configuration object.
   */
  public function buildForm(array $form, FormStateInterface $form_state, PowerTaggingConfig $powertagging_config = NULL) {
    // Taxonomy was configured already.
    $config = $powertagging_config->getConfig();
    $vocabulary_name = t('undefined');

    if (!empty($config['project']['taxonomy_id'])) {
      $vocabulary = Vocabulary::load($config['project']['taxonomy_id']);
      if (!is_null($vocabulary)) {
        $vocabulary_name = $vocabulary->label();

        // Fixed values for the formatter.
        $form['powertagging_config'] = [
          '#type' => 'value',
          '#value' => $powertagging_config,
        ];

        $form['vocabulary_name'] = [
          '#type' => 'value',
          '#value' => $vocabulary_name,
        ];

        $form['description'] = [
          '#type' => 'item',
          '#markup' => t('This process updates the concept details of all taxonomy terms of the connected vocabulary used to save the PowerTagging tags (name, altLabels, ...).'),
        ];

        $form['submit'] = [
          '#type' => 'submit',
          '#value' => t('Update taxonomy'),
          '#attributes' => ['class' => ['button--primary']],
        ];
      }
      else {
        $link = Link::createFromRoute('configuration form', 'entity.powertagging.edit_config_form', ['powertagging' => $powertagging_config->id()]);
        $form['error'] = [
          '#type' => 'item',
          '#markup' => '<div class="messages messages--error">' . t('The connected vocabulary does not exist anymore for the PowerTagging configuration "%title". Please check the %link.', [
              '%title' => $powertagging_config->getTitle(),
              '%link' => $link->toString(),
            ]) . '</div>',
        ];
      }
    }
    else {
      $link = Link::createFromRoute('configuration form', 'entity.powertagging.edit_config_form', ['powertagging' => $powertagging_config->id()]);
      $form['error'] = [
        '#type' => 'item',
        '#markup' => '<div class="messages messages--error">' . t('There was no vocabulary created for PowerTagging configuration "%title" yet. Please check the %link.', [
            '%title' => $powertagging_config->getTitle(),
            '%link' => $link->toString(),
          ]) . '</div>',
      ];
    }

    if (\Drupal::request()->query->has('destination')) {
      $destination = \Drupal::request()->get('destination');
      $url = Url::fromUri(\Drupal::request()
          ->getSchemeAndHttpHost() . $destination);
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

    // Set the title of the form
    $form['#title'] = t('Are you sure you want to update the vocabulary "@name"?', ['@name' => $vocabulary_name]);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $powertagging_config = $form_state->getValue('powertagging_config');
    if (empty($powertagging_config)) {
      $form_state->setErrorByName('powertagging_config', t('The PowerTagging configuration has a problem with the connected vocabulary.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $powertagging_config = $form_state->getValue('powertagging_config');
    $config = $powertagging_config->getConfig();

    // Get the term ID of all concepts with URI in the connected vocabulary.
    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $config['project']['taxonomy_id'])
      ->condition('field_uri', '', '!=')
      ->execute();

    $batch_infos = [
      'total' => count($tids),
      'start_time' => time(),
    ];

    $batch_operations = [];
    $tid_chunks = array_chunk($tids, 10);
    foreach ($tid_chunks as $tid_chunk) {
      $batch_operations[] = [
        [$this, 'updateVocabularyBatch'],
        [$tid_chunk, $powertagging_config, $batch_infos],
      ];
    }

    $batch = [
      'title' => t('Updating the vocabulary %name', ['%name' => $form_state->getValue('vocabulary_name')]),
      'operations' => $batch_operations,
      'init_message' => t('Start with updating of the vocabulary.'),
      'progress_message' => t('Process @current out of @total.'),
      'finished' => [$this, 'updateVocabularyBatchFinished'],
    ];

    batch_set($batch);
    return TRUE;
  }

  /**
   * Updates the taxonomy terms with URIs from a PowerTagging configuration.
   *
   * @param array $tids
   *   Array of taxonomy term IDs to update.
   * @param PowerTaggingConfig $powertagging_config
   *   The PowerTagging configuration.
   * @param array $batch_info
   *   An associative array of information about the batch process.
   * @param array $context
   *   The Batch context to transmit data between different calls.
   */
  public static function updateVocabularyBatch(array $tids, PowerTaggingConfig $powertagging_config, array $batch_info, &$context) {
    if (!isset($context['results']['processed'])) {
      $context['results']['processed'] = 0;
      $context['results']['updated'] = 0;
      $context['results']['skipped'] = 0;
      $context['results']['powertagging_id'] = $powertagging_config->id();
    }

    $terms = Term::loadMultiple($tids);
    $powertagging = new PowerTagging($powertagging_config);
    $powertagging->updateVocabulary($terms, $context);

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

    $context['message'] = t('Processed taxonomy terms: %currententities of %totalentities. (Updated: %updatedentities, Skipped: %skippedentities)', [
      '%currententities' => $context['results']['processed'],
      '%updatedentities' => $context['results']['updated'],
      '%skippedentities' => $context['results']['skipped'],
      '%totalentities' => $batch_info['total'],
    ]);
    $context['message'] .= '<br />' . t('Remaining time: %remainingtime.', ['%remainingtime' => $time_string]);
  }

  /**
   * Batch 'finished' callback used by PowerTagging update vocabulary batch.
   */
  public static function updateVocabularyBatchFinished($success, $results, $operations) {
    drupal_set_message(t('Successfully finished updating %totalentities taxonomy terms. (Updated: %updatedentities, Skipped: %skippedentities)', [
      '%totalentities' => $results['processed'],
      '%updatedentities' => $results['updated'],
      '%skippedentities' => $results['skipped'],
    ]));
  }
}