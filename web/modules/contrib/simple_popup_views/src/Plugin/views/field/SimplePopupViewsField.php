<?php

namespace Drupal\simple_popup_views\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler for simple popup views.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("simple_popup_views_field")
 */
class SimplePopupViewsField extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
    $this->field_alias = 'simple_popup_views_' . $this->position;
  }

  /**
   * Define the available options.
   *
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['label']['default'] = NULL;
    $options['trigger_method'] = ['default' => '0'];
    $options['position'] = ['default' => '0'];
    $options['source_field'] = ['default' => ''];
    $options['popup_field'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_popup_views.settings'];
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['relationship']['#access'] = FALSE;
    $previous = $this->getPreviousFieldLabels();
    $fields = ['- ' . $this->t('no field selected') . ' -'];
    foreach ($previous as $id => $label) {
      $field[$id] = $label;
    }
    $fields += $field;

    $form['trigger_method'] = [
      '#type' => 'radios',
      '#title' => $this
        ->t('Trigger method'),
      '#default_value' => $this->options['trigger_method'],
      '#options' => [
        0 => $this
          ->t('On Hover'),
        1 => $this
          ->t('On Click'),
      ],
    ];
    $form['position'] = [
      '#type' => 'radios',
      '#title' => $this
        ->t('Positions'),
      '#default_value' => $this->options['position'],
      '#options' => [
        0 => $this
          ->t('Top'),
        1 => $this
          ->t('Right'),
        2 => $this
          ->t('Bottom'),
        3 => $this
          ->t('Left'),
      ],
    ];
    $form['source_field'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Source value'),
      '#description' => $this->t('It is visible to users. Replacement variables may be used.'),
      '#default_value' => $this->options['source_field'],
    ];
    $form['popup_field'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Popup value'),
      '#description' => $this->t('It will be displayed in popup. Replacement variables may be used.'),
      '#default_value' => $this->options['popup_field'],
    ];
    $form['replacements'] = [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#title' => $this->t('Replacement Variables'),
    ];
    $views_fields = $this->view->display_handler->getHandlers('field');
    foreach ($views_fields as $field => $handler) {
      if ($field == $this->options['id']) {
        break;
      }
      $items[] = "{{ $field }}";
    }
    $form['replacements']['variables'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {

  }

  /**
   * Cleans a variable for handling later.
   */
  public function cleanVar($var) {
    $unparsed = isset($var->last_render) ? $var->last_render : '';
    return trim($unparsed);
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $trigger_method = $this->options['trigger_method'];
    $position = $this->options['position'];
    $source_field = $this->options['source_field'];
    $popup_field = $this->options['popup_field'] ? $this->options['popup_field'] : '';
    $popup_field = $this->spvPopup($source_field, $popup_field, $trigger_method, $position);
    $fields = $this->view->display_handler->getHandlers('field');
    $labels = $this->view->display_handler->getFieldLabels();
    // Search through field information for possible replacement variables.
    foreach ($labels as $key => $var) {
      // If we find a replacement variable, replace it.
      if (strpos($source_field, "{{ $key }}") !== FALSE) {
        $field = $this->cleanVar($fields[$key]);
        $source_field = $this->t(str_replace("{{ $key }}", $field, $source_field));
      }
      if (strpos($popup_field, "{{ $key }}") !== FALSE) {
        $field = $this->cleanVar($fields[$key]);
        $popup_field = $this->t(str_replace("{{ $key }}", $field, $popup_field));
      }
    }
    return $popup_field;
  }

  /**
   * Add popup wrappers.
   */
  public function spvPopup($source_field, $popup_field, $trigger_method, $position) {
    $position_class = '';
    switch ($position) {
      case '0':
        $position_class = 'spv-top-popup';
        break;

      case '1':
        $position_class = 'spv-right-popup';
        break;

      case '2':
        $position_class = 'spv-bottom-popup';
        break;

      case '3':
        $position_class = 'spv-left-popup';
        break;
    }
    $trigger_class = 'spv_on_hover';
    $spv_close = '';
    if ($trigger_method) {
      $trigger_class = 'spv_on_click';
      $spv_close = '<div class="spv_close">x</div>';
    }
    $spv_popup = '<div class="simple-popup-views-global" style="">
      <div class="spv-popup-wrapper">
        <div class="spv-popup-link ' . $trigger_class . '">' . $source_field . '</div>
        <div class="spv-popup-content ' . $position_class . '">' . $spv_close . '
          <div class="spv-inside-popup">' . $popup_field . '</div>
        </div>
      </div>
    </div>';
    return $spv_popup;
  }

}
