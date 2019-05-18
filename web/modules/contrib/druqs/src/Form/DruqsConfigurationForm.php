<?php

namespace Drupal\druqs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;

/**
 * Configure druqs for this site.
 */
class DruqsConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'druqs_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'druqs.configuration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get the last / default configuration.
    $config = $this->config('druqs.configuration');

    // Add field for the search sources.
    $form['search_sources'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Search sources'),
      '#description' => $this->t('Select what you want the druqs module to search.'),
      '#options' => $this->getSearchSources(),
      '#default_value' => $config->get('search_sources'),
    ];

    // Add field for the amount of results per source.
    $form['results_per_source'] = [
      '#type' => 'number',
      '#title' => $this->t('Results per source'),
      '#description' => $this->t('Amount of results each source will provide.'),
      '#default_value' => $config->get('results_per_source'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 10,
    ];

    // Add field for the total amount of results displayed.
    $form['results_max'] = [
      '#type' => 'number',
      '#title' => $this->t('Total results maximum'),
      '#description' => $this->t('Maximum amount of results displayed.'),
      '#default_value' => $config->get('results_max'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 50,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('druqs.configuration')
      ->set('search_sources', array_filter($values['search_sources']))
      ->set('results_per_source', $values['results_per_source'])
      ->set('results_max', $values['results_max'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Return options array with search destinations for the druqs.
   */
  protected function getSearchSources() {

    // Add users, nodes and taxonomies as default options.
    $options = [
      'user' => $this->t('Users (by name)'),
      'node' => $this->t('Content (by node title)'),
      'taxonomy' => $this->t('Taxonomy terms (by name)'),
    ];

    // Add all available menus as well.
    foreach (Menu::loadMultiple() as $menu_name => $menu) {
      $options['menu_' . $menu_name] = $this->t('Menu: @name', ['@name' => $menu->label()]);
    }

    return $options;
  }

}
