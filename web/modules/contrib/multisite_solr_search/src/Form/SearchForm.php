<?php

namespace Drupal\multisite_solr_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SearchForm.
 */
class SearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multisite_solr_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['search_keyword'] = [
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#size' => 64,
      '#placeholder' => 'Search keyword',
      '#autocomplete_route_name' => 'multisite_solr_search.search_results',
      '#input_group_button' => TRUE,
      '#weight' => '0',
    ];
    $form['#attributes']['class'][] = 'multisitesearch_form';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
