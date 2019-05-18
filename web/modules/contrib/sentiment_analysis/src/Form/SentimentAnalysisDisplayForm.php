<?php

namespace Drupal\sentiment_analysis\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Url;
/**
 * {@inheritdoc}.
 */
class SentimentAnalysisDisplayForm extends FormBase {
   /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'sentiment_analysis_display_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

  // Write Headers.
  $header = [
     'entity_id' => t('ID'),
     'entity_type' => t('Entity Type'),
     'bundle' => t('Bundle'),
     'uid' => t('User ID'),
     'sentiment' => t('Sentiment Result'),
     'sentence_description' => t('Sentence Description'),
     'score' => 'Score',
     'page_url' => 'Page URL',
     'time' => t('Time'),
  ];
  // Fetch All Sentiment Analysis table Details.
  $query = \Drupal::database()->select('sentiment_analysis_details', 's');
  $query->fields('s');
  $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
  $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
  $results = $pager->execute();
  // Initialize an empty array
  $output = array();
  // Next, loop through the $results array
  foreach ($results as $result) {
    if ($result->uid != 0) {
      $output[] = [
       'entity_id' => $result->entity_id,
       'entity_type' => $result->entity_type,
       'bundle' => $result->bundle,
       'uid' => $result->uid,
       'sentiment' => $result->sentiment,
       'score' => $result->score,
       'sentence_description' => $result->sentence_description,
       'page_url' => $result->page_url,
       'time' => $result->time,
      ];
    }
  }
  $form['list_table'] = [
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $output,
    '#empty' => t('No Data found'),
  ];
  $form['pager'] = array(
  '#type' => 'pager'
  );
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
}