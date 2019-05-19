<?php

namespace Drupal\views_semantic_tabs\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ViewsStyle(
 *   id = "views_semantic_tabs",
 *   title = @Translation("Semantic tabs"),
 *   help = @Translation("Configurable semantic tabs for views fields."),
 *   theme = "views_semantic_tabs_format",
 *   display_types = {"normal"}
 * )
 */
class ViewsSemanticTabs extends StylePluginBase {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $options = [
      '' => $this->t('- None -')
     ];
    $field_labels = $this->displayHandler->getFieldLabels(TRUE);
    $options += $field_labels;
    $grouping = $this->options['group'];
    $form['group'] = [
      '#type' => 'select',
      '#title' => $this->t('Grouping field'),
      '#options' => $options,
      '#default_value' => $grouping,
      '#description' => $this->t('You should specify a field by which to group the records.'),
      '#required' => TRUE,
    ];
    $form['advanced'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Advanced options'),
      '#description' => $this->t('Advanced options will override default jQuery tabs options. See http://api.jqueryui.com/tabs for more information.'),
    ];
    $form['advanced']['options'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Your advanced options'),
      '#description' => $this->t('Please enter each values on a new line, using the format: <em>key:value,</em>.'),
      '#default_value' => $this->options['advanced']['options'],
    ];
  }

  /**
   * Set default options
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['group'] = ['default' => []];
    $options['advanced'] = [
      'default' => '',
      'options' => ['default' => '']
    ];
    return $options;
  }
}
