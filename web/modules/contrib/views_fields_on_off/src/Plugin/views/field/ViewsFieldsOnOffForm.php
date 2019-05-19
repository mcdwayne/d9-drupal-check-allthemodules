<?php

namespace Drupal\views_fields_on_off\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a handler that adds the form for Fields On/Off.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_fields_on_off_form")
 */
class ViewsFieldsOnOffForm extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isExposed() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {

    $field_id = $this->options['id'];
    $label = $this->options['label'];
    $selected_options = $this->options['fields'];
    $all_fields = $this->displayHandler->getFieldLabels();
    $options = array_filter($all_fields, function ($key) use ($selected_options) {
      return in_array($key, $selected_options, TRUE);
    }, ARRAY_FILTER_USE_KEY);

    $form[$field_id] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('@value', [
        '@value' => $label,
      ]),
      '#description' => t('Select the fields you want to display.'),
      '#options' => $options,
    ];

    $form['fields_on_off_hidden_submitted'] = [
      '#type' => 'hidden',
      '#default_value' => 1,
    ];

  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['fields'] = ['default' => []];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $all_fields = $this->displayHandler->getFieldLabels();

    // Remove any field that have been excluded from the display from the list.
    foreach ($all_fields as $key => $field) {
      $exclude = $this->view->display_handler->handlers['field'][$key]->options['exclude'];
      if ($exclude) {
        unset($all_fields[$key]);
      }
    }

    // Offer to include only those fields that follow this one.
    $field_options = array_slice($all_fields, 0, array_search($this->options['id'], array_keys($all_fields)));
    $form['fields'] = [
      '#type' => 'checkboxes',
      '#title' => t('Fields'),
      '#description' => t('Fields to be turned on and off.'),
      '#options' => $field_options,
      '#default_value' => $this->options['fields'],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // This is not a real field and it only affects the query by excluding
    // fields from the display. But Views won't render if the query()
    // method is not present. This doesn't do anything, but it has to be here.
    // This function is a void so it doesn't return anything.
  }

}