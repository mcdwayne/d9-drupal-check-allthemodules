<?php

namespace Drupal\taxonomy_breadcrumb\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TaxonomyBreadcrumbAdminSettings.
 *
 * @package Drupal\taxonomy_breadcrumb\Form
 */
class TaxonomyBreadcrumbAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_breadcrumb_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['taxonomy_breadcrumb.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Basic settings'),
      '#open' => TRUE,
    ];

    $form['settings']['taxonomy_breadcrumb_home'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Home breadcrumb text'),
      '#default_value' => \Drupal::config('taxonomy_breadcrumb.settings')->get('taxonomy_breadcrumb_home'),
      '#description' => $this->t('Text to display at top of breadcrumb trail. Typically home or your site name. Leave blank to have no home breadcrumb.'),
    ];

    $form['settings']['taxonomy_breadcrumb_page_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show current page title in breadcrumb trail'),
      '#default_value' => \Drupal::config('taxonomy_breadcrumb.settings')->get('taxonomy_breadcrumb_page_title'),
      '#description' => $this->t("If enabled, the page title will be added as the last item in the breadcrumb trail."),
      '#weight' => 30,
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#description' => $this->t('Use these advanced settings to control which node types the taxonomy-based breadcrumbs will be generated for.  This allows the taxonomy breadcrumb module to peacefully coexist with modules that define their own breadcrumbs, such as the book module.'),
      '#title' => $this->t('Advanced settings'),
      '#open' => TRUE,
    ];

    $form['advanced']['taxonomy_breadcrumb_include_nodes'] = [
      '#type' => 'radios',
      '#title' => $this->t('Include or exclude the following node types'),
      '#default_value' => \Drupal::config('taxonomy_breadcrumb.settings')->get('taxonomy_breadcrumb_include_nodes'),
      '#options' => [
        1 => $this->t('Include'),
        0 => $this->t('Exclude'),
      ],
      '#weight' => 10,
    ];

    $tb_types = (array) \Drupal::config('taxonomy_breadcrumb.settings')->get('taxonomy_breadcrumb_node_types');
    $default = [];
    foreach ($tb_types as $index => $value) {
      if ($value) {
        $default[] = $index;
      }
    }

    $form['advanced']['taxonomy_breadcrumb_node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Node types to include or exclude'),
      '#default_value' => $default,
      '#options' => node_type_get_names(),
      '#description' => $this->t('A list of node types to include or exclude when applying taxonomy-based breadcrumbs.'),
      '#weight' => 20,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('taxonomy_breadcrumb.settings');
    $config->set('taxonomy_breadcrumb_home', $form_state->getValue('taxonomy_breadcrumb_home'));
    $config->set('taxonomy_breadcrumb_page_title', $form_state->getValue('taxonomy_breadcrumb_page_title'));
    $config->set('taxonomy_breadcrumb_include_nodes', $form_state->getValue('taxonomy_breadcrumb_include_nodes'));
    $config->set('taxonomy_breadcrumb_node_types', $form_state->getValue('taxonomy_breadcrumb_node_types'));
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

}
