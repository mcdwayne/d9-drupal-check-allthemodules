<?php
/**
 * @file
 * Contains \Drupal\pp_graphsearch\Form\PPGraphSearchSearchBarForm.
 */

namespace Drupal\pp_graphsearch\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pp_graphsearch\PPGraphSearch;

/**
 * Add search bar with text field and buttons to an existing Drupal-form.
 */
class PPGraphSearchSearchBarForm extends FormBase {
  protected $graphsearch;
  protected $config;
  protected $config_settings;
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pp_graphsearch_search_bar_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PPGraphSearch $graphsearch = NULL, $search_box_only = FALSE) {
    $this->graphsearch = $graphsearch;
    $this->config = $graphsearch->getConfig();
    $this->config_settings = $this->config->getConfig();

    $form['search_string'] = array(
      '#prefix' => '<div class="pp-graphsearch-' . ($search_box_only ? 'block-' : '') . 'search-bar">',
      '#type' => 'textfield',
      '#title' => !$search_box_only ? ('Search') : NULL,
      '#default_value' => '',
      '#autocomplete_route_name' => 'pp_graphsearch.autocomplete',
      '#autocomplete_route_parameters' => array(
        'graphsearch_config' => $this->config->id(),
        'max_items' => $this->config_settings['ac_max_suggestions'],
      ),
      '#attributes' => array('placeholder' => t($this->config_settings['placeholder'])),
    );

    if (!$search_box_only) {
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Search'),
        '#attributes' => array('class' => array('search-bar-submit')),
      );

      $form['reset'] = array(
        '#suffix' => '</div><div class="clearBoth"></div>',
        '#type' => 'submit',
        '#value' => 'Reset',
        '#name' => 'op',
        '#attributes' => array('class' => array('search-bar-reset')),
      );
    }
    else {
      $form['search_string']['#suffix'] = '</div>';
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
  }
}
?>