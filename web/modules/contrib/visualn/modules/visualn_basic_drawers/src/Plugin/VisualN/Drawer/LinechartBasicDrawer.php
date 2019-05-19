<?php

namespace Drupal\visualn_basic_drawers\Plugin\VisualN\Drawer;

use Drupal\visualn\Core\DrawerWithJsBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\ResourceInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Provides a 'Line Chart' VisualN drawer.
 *
 * @ingroup drawer_plugins
 *
 * @VisualNDrawer(
 *  id = "visualn_linechart_basic",
 *  label = @Translation("Linechart Basic"),
 * )
 */
class LinechartBasicDrawer extends DrawerWithJsBase {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Basic linechart with variable number of series');
  }

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    $default_config = [
      'series_number' => 1,
      'series_labels' => [],
    ];

    return $default_config;
  }

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // ajax wrapper id must be unique
    $ajax_wrapper_id = !empty($form['#array_parents'])
      ? implode('-', $form['#array_parents']) . '--linechart-ajax-wrapper'
      : 'linechart-ajax-wrapper';
    // @todo: check for other special chars
    // the "|" character is added to visualn_style list on visualn style config page
    $ajax_wrapper_id = str_replace('|', '-', $ajax_wrapper_id);

    $form['series_number'] = [
      '#type' => 'number',
      '#title' => t('Number of series'),
      '#default_value' => $this->configuration['series_number'],
      '#min' => 1,
      '#max' => 10,
      '#required' => TRUE,
    ];

    // @todo: also validation should check if update_series was triggered (if number changed)
    $form['update_series'] = [
      // @todo: add a more concise comment
      // use 'button' instead of 'submit' to avoid calling submit handler
      // also #limit_validation_errors setting and views Drawing display style settings
      // do not work (independently) if used 'submit' type
      '#type' => 'button',
      '#value' => t('Update series'),
      //'#prefix' => '<div class="form-item">',
      //'#suffix' => '</div>',
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        'wrapper' => $ajax_wrapper_id,
      ],
      '#limit_validation_errors' => [],
    ];

    if (!empty($form['#parents'])) {
      // Submits must have different names in case multiple instances of the drawer config
      // are shown on a page, otherwise all submits would have an 'op' name with the same 'value'
      // which would cause wrong triggering element at submit.
      $form_parents = $form['#parents'];
      $name = array_shift($form_parents);
      $name .= '[' . implode('][', $form['#parents']) . '][update_series]';
      $form['update_series']['#name'] = $name;
    }

    $form['ajax_container'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Series labels'),
      '#description' => t('Enter label for each series, leave empty to use defaults.'),
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#process' => [[get_called_class(), 'processConfigurationFormSectionsUpdate']],
    ];
    $form['ajax_container']['#configuration'] = $this->configuration;

    return $form;
  }

  /**
   * Attach series labels subform.
   */
  public static function processConfigurationFormSectionsUpdate(array $element, FormStateInterface $form_state, $form) {
    $configuration = $element['#configuration'];
    // Generally $element['#parents'] could be used directly here since 'series_number' element triggers ajax request
    // but leave it as it is for clarity.
    $element_parents = array_slice($element['#parents'], 0, -1);
    $series_number = $form_state->getValue(array_merge($element_parents, ['series_number']));
    for ($i = 1; $i <= $series_number; $i++) {
      $element['series_labels'][$i] = [
        '#type' => 'textfield',
        '#title' => t('Data series @series', ['@series' => $i]),
        '#attributes' => ['placeholder' => '#' . $i],
      ];
      if (isset($configuration['series_labels'][$i])) {
        $element['series_labels'][$i]['#default_value'] = $configuration['series_labels'][$i];
      }
    }

    return $element;
  }

  /**
   * @inheritdoc
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $visualn_style_id = $form_state->getValue($form_state->getTriggeringElement()['#parents']);
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['ajax_container'];
  }

  /**
   * @inheritdoc
   */
  public function prepareJsConfig(array &$drawer_config) {
    $series_number = $drawer_config['series_number'];
    for ($i = 1; $i <= $series_number; $i++) {
      if (empty($drawer_config['series_labels'][$i])) {
        $drawer_config['series_labels'][$i] = '#' . $i;
      }
    }

    // @todo: 'update_series' should be already removed here, check it
    unset($drawer_config['update_series']);
    unset($drawer_config['ajax_container']);
  }

  /**
   * @inheritdoc
   */
  public function extractFormValues($form, FormStateInterface $form_state) {
    // Since it is supposed to be subform_state, get all the values without limiting the scope.
    $clean_values = $form_state->cleanValues()->getValues();

    // the element may be not set, e.g. when called before values mapping,
    // see VisualNFormHelper::processDrawerFields() for example
    if (isset($clean_values['ajax_container'])) {
      $clean_values['series_labels'] = $clean_values['ajax_container']['series_labels'];
      foreach ($clean_values['series_labels'] as $k => $label) {
        // trim series labels
        $clean_values['series_labels'][$k] = trim($label);
      }
      unset($clean_values['ajax_container']);
    }

    return $clean_values;
  }

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn_basic_drawers/linechart-basic-drawer';

    return $resource;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnLinechartBasicDrawer';
  }

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    $data_keys = ['x'];

    // add data key for each series
    for ($i = 1; $i <= $this->configuration['series_number']; $i++) {
      $data_keys[] = 'data' . $i;
    }

    return $data_keys;
  }

}
