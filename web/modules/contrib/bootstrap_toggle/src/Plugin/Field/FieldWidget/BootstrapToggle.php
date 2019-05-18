<?php

namespace Drupal\bootstrap_toggle\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'bootstrap_toggle_switch' widget.
 *
 * @FieldWidget(
 *   id = "bootstrap_toggle_switch",
 *   module = "bootstrap_toggle",
 *   label = @Translation("Bootstrap Toggle"),
 *   field_types = {
 *     "boolean"
 *   }
 * )
 */
class BootstrapToggle extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'display_label' => 1,
      'bootstraptoggle_data_on' => 'On',
      'bootstraptoggle_data_off' => 'Off',
      'bootstraptoggle_box_size' => 1,
      'bootstraptoggle_on_style' => 0,
      'bootstraptoggle_off_style' => 5,
      'bootstraptoggle_box_height' => '',
      'bootstraptoggle_box_width' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['display_label'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Field label'),
      '#default_value' => $this->getSetting('display_label'),
      '#options' => array(0 => $this->t('Show field label'), 1 => $this->t('Do not show any label')),
      '#weight' => -1,
    );
    $element['bootstraptoggle_data_on'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Use custom label for "On state"'),
      '#default_value' => $this->getSetting('bootstraptoggle_data_on'),
      '#weight' => -1,
    );
    $element['bootstraptoggle_data_off'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Use custom label for "Off state"'),
      '#default_value' => $this->getSetting('bootstraptoggle_data_off'),
      '#weight' => -1,
    );
    $element['bootstraptoggle_box_size'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Size of toggle button'),
      '#default_value' => $this->getSetting('bootstraptoggle_box_size'),
      '#options' => array(
        0 => $this->t('Large'),
        1 => $this->t('Normal'),
        2 => $this->t('Small'),
        3 => $this->t('Mini'),
      ),
      '#weight' => -1,
    );
    $element['bootstraptoggle_on_style'] = array(
      '#type' => 'radios',
      '#title' => $this->t('On state style of toggle button'),
      '#default_value' => $this->getSetting('bootstraptoggle_on_style'),
      '#options' => array(
        0 => $this->t('Primary'),
        1 => $this->t('Success'),
        2 => $this->t('Info'),
        3 => $this->t('Warning'),
        4 => $this->t('Danger'),
        5 => $this->t('Default'),
      ),
      '#weight' => -1,
    );
    $element['bootstraptoggle_off_style'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Off state style of toggle button'),
      '#default_value' => $this->getSetting('bootstraptoggle_off_style'),
      '#options' => array(
        0 => $this->t('Primary'),
        1 => $this->t('Success'),
        2 => $this->t('Info'),
        3 => $this->t('Warning'),
        4 => $this->t('Danger'),
        5 => $this->t('Default'),
      ),
      '#weight' => -1,
    );
    $element['bootstraptoggle_box_height'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Override height of toggle button'),
      '#default_value' => $this->getSetting('bootstraptoggle_box_height'),
      '#weight' => -1,
    );
    $element['bootstraptoggle_box_width'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Override width of toggle button'),
      '#default_value' => $this->getSetting('bootstraptoggle_box_width'),
      '#weight' => -1,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $display_label = $this->getSetting('display_label');
    $summary[] = $this->t('Label: @display_label', array('@display_label' => ($display_label ? 'None' : $this->t('Field label'))));

    $data_on = $this->getSetting('bootstraptoggle_data_on');
    $summary[] = $this->t('On label: @data_on', array('@data_on' => ($data_on ? $data_on : 'Default')));

    $data_off = $this->getSetting('bootstraptoggle_data_off');
    $summary[] = $this->t('Off label: @data_off', array('@data_off' => ($data_off ? $data_off : 'Default')));

    $box_size = $this->getSetting('bootstraptoggle_box_size');
    switch ($box_size) {
      case 0:
        $box_size_name = 'Large';
        break;

      case 1:
        $box_size_name = 'Normal';
        break;

      case 2:
        $box_size_name = 'Small';
        break;

      case 3:
        $box_size_name = 'Mini';
        break;

      default:
        $box_size_name = 'Default';
        break;
    }
    $summary[] = $this->t('Box size: @box_size', array('@box_size' => $box_size_name));

    $box_style_on = $this->getSetting('bootstraptoggle_on_style');
    switch ($box_style_on) {
      case 0:
        $box_style_on_name = 'Primary';
        break;

      case 1:
        $box_style_on_name = 'Success';
        break;

      case 2:
        $box_style_on_name = 'Info';
        break;

      case 3:
        $box_style_on_name = 'Warning';
        break;

      case 4:
        $box_style_on_name = 'Danger';
        break;

      case 5:
        $box_style_on_name = 'Default';
        break;

      default:
        $box_style_on_name = 'None';
        break;
    }
    $summary[] = $this->t('On state style: @box_style', array('@box_style' => $box_style_on_name));

    $box_style_off = $this->getSetting('bootstraptoggle_off_style');
    switch ($box_style_off) {
      case 0:
        $box_style_off_name = 'Primary';
        break;

      case 1:
        $box_style_off_name = 'Success';
        break;

      case 2:
        $box_style_off_name = 'Info';
        break;

      case 3:
        $box_style_off_name = 'Warning';
        break;

      case 4:
        $box_style_off_name = 'Danger';
        break;

      case 5:
        $box_style_off_name = 'Default';
        break;

      default:
        $box_style_off_name = 'None';
        break;
    }
    $summary[] = $this->t('Off state style: @box_style', array('@box_style' => $box_style_off_name));

    $box_height = $this->getSetting('bootstraptoggle_box_height');
    $summary[] = $this->t('Box height: @box_height', array('@box_height' => ($box_height ? $box_height : 'Default')));

    $box_width = $this->getSetting('bootstraptoggle_box_width');
    $summary[] = $this->t('Box width: @box_width', array('@box_width' => ($box_width ? $box_width : 'Default')));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : FALSE;

    $element += array(
      '#type' => 'checkbox',
      '#default_value' => $value,
      '#attributes' => array('data-toggle' => array("toggle")),
    );

    // Override the title from the incoming $element.
    if ($this->getSetting('display_label') == 0) {
      $element['#title'] = $this->fieldDefinition->getLabel();
    }
    elseif ($this->getSetting('display_label') == 1) {
      $element['#title'] = '';
    }

    // Set data-on and data-off attributes in a button.
    if ($this->getSetting('bootstraptoggle_data_on')) {
      $element['#attributes']['data-on'] = $this->getSetting('bootstraptoggle_data_on');
    }
    if ($this->getSetting('bootstraptoggle_data_off')) {
      $element['#attributes']['data-off'] = $this->getSetting('bootstraptoggle_data_off');
    }

    // Set data-size attribute of button.
    $box_size = $this->getSetting('bootstraptoggle_box_size');
    switch ($box_size) {
      case 0:
        $box_size_name = 'large';
        break;

      case 1:
        $box_size_name = 'normal';
        break;

      case 2:
        $box_size_name = 'small';
        break;

      case 3:
        $box_size_name = 'mini';
        break;

      default:
        $box_size_name = '';
        break;
    }
    $element['#attributes']['data-size'] = $box_size_name;

    // Box off style.
    $box_style_off = $this->getSetting('bootstraptoggle_off_style');
    switch ($box_style_off) {
      case 0:
        $box_style_off_name = 'primary';
        break;

      case 1:
        $box_style_off_name = 'success';
        break;

      case 2:
        $box_style_off_name = 'info';
        break;

      case 3:
        $box_style_off_name = 'warning';
        break;

      case 4:
        $box_style_off_name = 'danger';
        break;

      case 5:
        $box_style_off_name = 'default';
        break;

      default:
        $box_style_off_name = '';
        break;
    }
    $element['#attributes']['data-offstyle'] = $box_style_off_name;

    // Box on style.
    $box_style_on = $this->getSetting('bootstraptoggle_on_style');
    switch ($box_style_on) {
      case 0:
        $box_style_on_name = 'primary';
        break;

      case 1:
        $box_style_on_name = 'success';
        break;

      case 2:
        $box_style_on_name = 'info';
        break;

      case 3:
        $box_style_on_name = 'warning';
        break;

      case 4:
        $box_style_on_name = 'danger';
        break;

      case 5:
        $box_style_on_name = 'default';
        break;

      default:
        $box_style_on_name = '';
        break;
    }
    $element['#attributes']['data-onstyle'] = $box_style_on_name;

    // Height of Box.
    if ($box_height = $this->getSetting('bootstraptoggle_box_height')) {
      $element['#attributes']['data-height'] = is_numeric($box_height) ? $box_height : '';
    }
    if ($box_width = $this->getSetting('bootstraptoggle_box_width')) {
      $element['#attributes']['data-width'] = is_numeric($box_width) ? $box_width : '';
    }

    // Add libraries.
    $form['#attached']['library'][] = 'bootstrap_toggle/bootstrap_toggle_css';
    $form['#attached']['library'][] = 'bootstrap_toggle/bootstrap_toggle_js';

    return array('value' => $element);
  }

}
