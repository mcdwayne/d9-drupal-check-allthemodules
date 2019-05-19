<?php
/**
 * @file
 */

namespace Drupal\spectra\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;
use Drupal\spectra\SpectraUtilities\SpectraDataFields;

/**
 * Defines a views field plugin.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("spectra_data_viewer")
 */

class SpectraDataViewerField extends FieldPluginBase {
  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options = SpectraDataFields::DataViewerFormOptions($options);
    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form = SpectraDataFields::DataViewerFormElements($form, $this);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    $render_key = $this->options['render_key'];
    $render_method = $this->options['render_array'];
    $render_max = $this->options['render_array_max'];
    $data_entity = $this->getEntity($values);
    $data_m = $data_entity->get('data_data')->getValue();
    $data_map = isset($data_m[0]['value']) ? json_decode($data_m[0]['value'], TRUE) : FALSE;
    if ($data_map === FALSE) {
      return '';
    }
    else {
      $data_viewer = new SpectraDataFields();
      return $data_viewer->DataViewerMarkup($render_key, $render_method, $data_map, $render_max);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values)
  {
    return array('#markup' => $this->getValue($values));
  }
}