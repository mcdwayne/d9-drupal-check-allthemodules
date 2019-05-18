<?php

/**
 * @file
 * Copyright (c) FormatData. All rights reserved.
 *
 * Distribution of RecipeML Processing Software in source and/or binary forms is
 * permitted provided that the following conditions are met:
 * - Distributions in source code must retain the above copyright notice and
 *   this list of conditions.
 * - Distributions in binary form must reproduce the above copyright notice and
 *   this list of conditions in the documentation and/or other materials
 *   provided with the distribution.
 * - All advertising materials and documentation for RecipeML Processing
 *   Software must display the following acknowledgment:
 *   "This product is RecipeML compatible."
 * - Names associated with RecipeML or FormatData must not be used to endorse or
 *   promote RecipeML Processing Software without prior written permission from
 *   FormatData. For written permission, please contact RecipeML@formatdata.com.
 */

namespace Drupal\recipe\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Default style plugin to render RecipeML.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "recipeml",
 *   title = @Translation("RecipeML"),
 *   help = @Translation("Generates RecipeML from a view."),
 *   theme = "recipe_view_recipeml",
 *   display_types = {"recipe"}
 * )
 */
class RecipeML extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  public function attachTo(array &$build, $display_id, Url $url, $title) {
    $url_options = [];
    $input = $this->view->getExposedInput();
    if ($input) {
      $url_options['query'] = $input;
    }
    $url_options['absolute'] = TRUE;

    // Attach a link to the RecipeML, which is an alternate representation.
    $build['#attached']['html_head_link'][][] = [
      'rel' => 'alternate',
      'type' => 'text/xml',
      'title' => $title,
      'href' => $url->setOptions($url_options)->toString(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['title_field'] = ['default' => ''];
    $options['version_field'] = ['default' => ''];
    $options['source_field'] = ['default' => ''];
    $options['time_fields'] = ['default' => []];
    $options['yield_qty_field'] = ['default' => ''];
    $options['yield_unit_field'] = ['default' => ''];
    $options['description_field'] = ['default' => ''];
    $options['ingredients_field'] = ['default' => ''];
    $options['directions_field'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $initial_labels = ['' => $this->t('- None -')];
    $view_fields_labels = $this->displayHandler->getFieldLabels();
    $view_fields_labels = array_merge($initial_labels, $view_fields_labels);

    $form['title_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Title field'),
      '#description' => $this->t('The field that is going to be used as the recipe title for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['title_field'],
      '#required' => TRUE,
    ];
    $form['version_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Version field'),
      '#description' => $this->t('The field that is going to be used as the recipe version for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['version_field'],
    ];
    $form['source_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Source field'),
      '#description' => $this->t('The field that is going to be used as the recipe source for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['source_field'],
    ];
    $form['time_fields'] = [
      '#type' => 'checkboxes',
      '#title' => t('Preptime fields'),
      '#description' => t('Fields that will be used as the recipe preptimes for each row.  Selected fields must be integers.  Note that the RecipeML preptime element may encompass multiple time elements such as the cooking time or total time.  It is not limited to the preparation time.'),
      '#options' => $this->displayHandler->getFieldLabels(),
      '#default_value' => $this->options['time_fields'],
      '#multiple' => TRUE,
    ];
    $form['yield_qty_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Yield quantity field'),
      '#description' => $this->t('The field that is going to be used as the recipe yield quantity for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['yield_qty_field'],
    ];
    $form['yield_unit_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Yield unit field'),
      '#description' => $this->t('The field that is going to be used as the recipe yield unit for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['yield_unit_field'],
    ];
    $form['description_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Description field'),
      '#description' => $this->t('The field that is going to be used as the recipe description for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['description_field'],
    ];
    $form['ingredients_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Ingredients field'),
      '#description' => $this->t('The field that is going to be used as the recipe ingredients for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['ingredients_field'],
      '#required' => TRUE,
    ];
    $form['directions_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Directions field'),
      '#description' => $this->t('The field that is going to be used as the recipe directions for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['directions_field'],
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    $required_options = ['title_field', 'ingredients_field', 'directions_field'];
    foreach ($required_options as $required_option) {
      if (empty($this->options[$required_option])) {
        $errors[] = $this->t('Style plugin requires specifying which views fields to use for recipes.');
        break;
      }
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    $time_fields = $form_state->getValue(['style_options', 'time_fields']);
    $form_state->setValue(['style_options', 'time_fields'], array_filter($time_fields));
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $view_fields_labels = $this->displayHandler->getFieldLabels();
    $rows = [];

    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;

      $time_fields = [];
      $total_time = 0;
      foreach ($this->options['time_fields'] as $time_field) {
        if (empty($time_field)) {
          continue;
        }
        $time_value = $this->getField($row_index, $time_field);
        $time_fields[] = [
          'type' => $view_fields_labels[$time_field],
          'qty' => $time_value,
          'timeunit' => 'minutes',
        ];
        $total_time += (int) $time_value->__toString();
      }

      // Add a total time field if there is more than one time_field.
      if (count($time_fields) > 1) {
        $time_fields[] = [
          'type' => 'Total time',
          'qty' => $total_time,
          'timeunit' => 'minutes',
        ];
      }

      $rows[] = [
        'langcode' => $row->_entity->language()->getId(),
        'title' => $this->getField($row_index, $this->options['title_field']),
        'version' => $this->getField($row_index, $this->options['version_field']),
        'source' => $this->getField($row_index, $this->options['source_field']),
        'time_fields' => $time_fields,
        'yield_qty' => $this->getField($row_index, $this->options['yield_qty_field']),
        'yield_unit' => $this->getField($row_index, $this->options['yield_unit_field']),
        'description' => $this->getField($row_index, $this->options['description_field']),
        'ingredients' => $this->getField($row_index, $this->options['ingredients_field']),
        'directions' => $this->getField($row_index, $this->options['directions_field']),
      ];
    }

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $rows,
    ];
    unset($this->view->row_index);
    return $build;
  }

}
