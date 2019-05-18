<?php /**
 * @file
 * Contains \Drupal\addtocal\Plugin\Field\FieldFormatter\AddtocalView.
 */

namespace Drupal\addtocal\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;




/**
 * @FieldFormatter(
 *  id = "addtocal_view",
 *  label = @Translation("Add to Cal"),
 *  field_types = {
 *    "date",
 *    "datestamp",
 *    "datetime",
 *  }
 * )
 */
class AddtocalView extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $bundle_name = $field['bundles'][$instance['entity_type']][0];

    $display = $instance['display'][$view_mode];
    $settings = $display['settings'];

    //$field_list = field_info_instances($instance['entity_type'], $bundle_name);


    $description_options = $location_options = array('-1' => 'None');

    $location_field_types = array(
      'text_textfield',
      'addressfield_default',
      'addressfield_standard',
    );

    foreach ($field_list as $field) {
      if (in_array($field->widget->type, $location_field_types)) {
        $location_options[$field['field_name']] = $field['label'];
      }

      if ($field['widget']['type'] == 'text_textarea' || $field['widget']['type'] == 'text_textarea_with_summary') {
        $description_options[$field['field_name']] = $field['label'];
      }
    }

    $options = array(
      ''  => t('- None -'),
      '.' => t('Decimal point'),
      ',' => t('Comma'),
      ' ' => t('Space'),
      chr(8201) => t('Thin space'),
      "'" => t('Apostrophe'),
    );

    $start_end_options = array(
      'Both Start and End dates',
      'start date only',
      'End date only'
    );

    $elements['thousand_separator'] = array(
      '#type' => 'select',
      '#title' => t('Thousand marker'),
      '#options' => $options,
      '#default_value' => $this->getSetting('thousand_separator'),
      '#weight' => 0,
    );

    $elements['past_events'] = array(
      '#title' => t('Show Add to Cal widget for Past Events'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('prefix_suffix'),
      '#weight' => 2,
    );

    $elements['display_start_end'] = array(
      '#type' => 'select',
      '#title' => t('Display'),
      '#options' => $start_end_options,
      '#default_value' => $this->getSetting('display_start_end'),
      '#weight' => 0,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $settings = $this->getFieldSettings();

    foreach ($items as $delta => $item) {
      $output = $this->numberFormat($item->value);

      // Account for prefix and suffix.
      if ($this->getSetting('prefix_suffix')) {
        $prefixes = isset($settings['prefix']) ? array_map('field_filter_xss', explode('|', $settings['prefix'])) : array('');
        $suffixes = isset($settings['suffix']) ? array_map('field_filter_xss', explode('|', $settings['suffix'])) : array('');
        $prefix = (count($prefixes) > 1) ? format_plural($item->value, $prefixes[0], $prefixes[1]) : $prefixes[0];
        $suffix = (count($suffixes) > 1) ? format_plural($item->value, $suffixes[0], $suffixes[1]) : $suffixes[0];
        $output = $prefix . $output . $suffix;
      }

      $elements[$delta] = array('#markup' => $output);
    }

    return $elements;
  }

}
