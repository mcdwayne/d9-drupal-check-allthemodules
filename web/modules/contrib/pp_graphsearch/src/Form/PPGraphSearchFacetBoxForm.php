<?php
/**
 * @file
 * Contains \Drupal\pp_graphsearch\Form\PPGraphSearchFacetBoxForm.
 */

namespace Drupal\pp_graphsearch\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pp_graphsearch\PPGraphSearch;

/**
 * Add the facet box for the selected concepts and free terms.
 */
class PPGraphSearchFacetBoxForm extends FormBase {
  protected $graphsearch;
  protected $config;
  protected $config_settings;
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pp_graphsearch_facet_box_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PPGraphSearch $graphsearch = NULL) {
    $this->graphsearch = $graphsearch;
    $this->config = $graphsearch->getConfig();
    $this->config_settings = $this->config->getConfig();

    $form['facet_box_form'] = array(
      '#type' => 'container',
      '#prefix' => '<div id="facet-container"><div class="facets-empty">no active filters</div><div class="facet-box"></div>',
      '#suffix' => '<div class="search-filters hidden"></div></div>',
      '#attributes' => array('class' => array('hidden', 'facet-box-form')),
    );

    if (\Drupal::currentUser()->isAuthenticated()) {
      $form['facet_box_form']['title'] = array(
        '#prefix' => '<div class="messages error hidden"></div>',
        '#type' => 'textfield',
        '#title' => t('Title'),
        '#required' => TRUE,
      );
      $options = array_combine(array('', 'daily', 'weekly', 'monthly'), array('', 'daily', 'weekly', 'monthly'));
      $form['facet_box_form']['time_interval'] = array(
        '#type' => 'select',
        '#title' => t('Time interval'),
        '#description' => t('Please select a time interval if you want a personalized email alert to the selected filter; if not then leave it empty.'),
        '#options' => $options,
      );
      $form['facet_box_form']['save'] = array(
        '#type'   => 'submit',
        '#value'  => 'Save search filter',
        '#attributes' => array('class' => array('facet-box-form-submit')),
      );

      $form['filter_buttons'] = array(
        '#markup' => '<div id="filter-operations" class="filter-operations"><a class="add hidden">' . t('Add to my filters') . '</a><a class="show">' . t('Show my filters') . '</a></div>',
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
  }
}
?>