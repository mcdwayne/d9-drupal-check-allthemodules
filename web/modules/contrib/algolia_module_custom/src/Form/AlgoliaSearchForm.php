<?php

namespace Drupal\algolia_search_custom\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class AlgoliaSearchForm extends FormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'algolia_search_custom_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['search'] = [
      '#type' => 'textfield',
      '#title' => 'Recherche',
      '#placeholder' => 'Que recherchez-vous?',
      '#attributes' => [
        'class' => ['js-search-input'],
        'id' => 'js-algolia-search',
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'rechercher',
      '#attributes' => ['class' => ['btn']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('algolia_search_custom.algolia_search_page', [
      'q' => $form_state->getValue('search')
    ]);
  }

}
