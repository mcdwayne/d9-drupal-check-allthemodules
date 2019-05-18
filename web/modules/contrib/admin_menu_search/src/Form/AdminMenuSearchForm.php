<?php

namespace Drupal\admin_menu_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdminMenuSearchForm.
 */
class AdminMenuSearchForm extends FormBase {

  /**
   * Constructs a new AdminMenuSearchForm object.
   */
  public function __construct() {
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_menu_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['admin_menu_search_keyword'] = [
      '#type' => 'textfield',
      '#autocomplete_route_name' => 'admin_menu_search.autocomplete',
      '#attributes' => ['placeholder' => $this->t('Search here.')],
      '#size' => 30,
    ];
    $form['#attached']['library'][] = 'admin_menu_search/autocomplete';

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
  }

}
