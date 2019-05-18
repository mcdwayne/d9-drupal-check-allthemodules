<?php

namespace Drupal\migrate_d2d_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Simple wizard step form.
 */
class VocabularySelectForm extends DrupalMigrateForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_d2d_vocabulary_select_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Start clean in case we came here via Previous.
    $cached_values = $form_state->getTemporaryValue('wizard');
    unset($cached_values['vocabularies']);
    $form_state->setTemporaryValue('wizard', $cached_values);

    // @todo: Ideally we'll use the source_provider business, but that's buried
    // inside source plugins (DrupalSqlBase).
    if ($this->connection($form_state)->schema()->tableExists('taxonomy_vocabulary')) {
      $vocab_table = 'taxonomy_vocabulary';
      $term_table = 'taxonomy_term_data';
    }
    elseif ($this->connection($form_state)->schema()->tableExists('vocabulary')) {
      $vocab_table = 'vocabulary';
      $term_table = 'term_data';
    }
    else {
      $vocab_table = '';
      $term_table = '';
    }

    if ($vocab_table) {
      $vocab_count = $this->connection($form_state)->select($vocab_table, 'v')
        ->fields('v', ['vid'])
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    else {
      $vocab_count = 0;
    }
    if (!$vocab_count) {
      $form['description'] = [
        '#markup' => $this->t('There is no vocabulary data to be migrated from the source database.'),
      ];
      return $form;
    }
    $form['#tree'] = TRUE;
    $form['description'] = [
      '#markup' => $this->t('For each vocabulary on the source site, choose the destination site vocabulary to import its terms. You may also choose not to import a given vocabulary.'),
    ];

    if (empty($this->termCounts)) {
      $get_term_counts = TRUE;
    }
    else {
      $get_term_counts = FALSE;
    }
    $base_options = [
      -1 => $this->t('--Do not import--'),
      0 => $this->t('--Create vocabulary--'),
    ];
    $vocab_options = [];
    // Get the available destination vocabularies.
    /** @var \Drupal\taxonomy\VocabularyInterface[] $local_vocabs */
    $local_vocabs = Vocabulary::loadMultiple();
    foreach ($local_vocabs as $vocab) {
      $vocab_options[$vocab->id()] = $vocab->label();
    }
    $result = $this->connection($form_state)->select($vocab_table, 'v')
      ->fields('v', ['vid', 'name'])
      ->execute();
    foreach ($result as $vocab) {
      $options = $base_options + $vocab_options;
      // If we have a match on vocabulary name, default the mapping to that match
      // and remove the option to create a new vocabulary of that name.
      if ($vid = array_search($vocab->name, $vocab_options)) {
        $default_value = $vid;
        unset($options[0]);
      }
      else {
        $default_value = -1;
      }

      if ($get_term_counts) {
        $this->termCounts[$vocab->vid] = $this->connection($form_state)->select($term_table, 't')
          ->condition('vid', $vocab->vid)
          ->countQuery()
          ->execute()
          ->fetchField();
      }
      $title = $this->t('@name (@count)', ['@name' => $vocab->name,
        '@count' => $this->getStringTranslation()->formatPlural($this->termCounts[$vocab->vid], '1 term', '@count terms')]);
      $form['vocabularies'][$vocab->vid] = array(
        '#type' => 'select',
        '#title' => $title,
        '#options' => $options,
        '#default_value' => $default_value,
      );
    }

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $cached_values['vocabularies'] = $form_state->getValue('vocabularies');
    $form_state->setTemporaryValue('wizard', $cached_values);
  }

}
