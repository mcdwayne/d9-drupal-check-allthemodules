<?php

namespace Drupal\location_selector\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'location_selector_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "location_selector_formatter",
 *   label = @Translation("Location Selector"),
 *   field_types = {
 *     "location_selector_type"
 *   }
 * )
 */
class LocationSelectorFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'last_selected' => FALSE,
      'routing' => NULL,
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['last_selected'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show only the last selected value'),
      '#default_value' => $this->getSetting('last_selected'),
      '#description' => $this->t('Shows only the deepest level.'),
    ];

    $element['routing'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link items to a specific route.'),
      '#default_value' => $this->getSetting('routing'),
      '#description' => $this->t('Link the items to a specific view in which this field is inserted as a filter. E.g. view.view_id.display_id'),
      '#size' => 40,
      '#maxlength' => 128,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Shows only the deepest level: @last_selected', [
      '@last_selected' => $this->getSetting('last_selected') ? $this->t('Yes') : $this->t('No'),
    ]);

    if (!empty($basic_parent_id = $this->getSetting('routing'))) {
      $summary[] = $this->t('Linked to route: @routing', ['@routing' => $basic_parent_id]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // Get the form element settings.
    $formElementSettings = $this->getSettings();

    foreach ($items as $delta => $item) {
      $new_string = '';
      if (!empty($item)) {
        $item_string = $item->getString();
        $values = json_decode($item_string, TRUE);
        if (json_last_error() == JSON_ERROR_NONE) {
          $path = $values['path'];
          // Because:
          // @see https://stackoverflow.com/questions/48382457/mysql-json-column-change-array-order-after-saving
          ksort($path);
          $path_values = $path;
          if ($formElementSettings['last_selected']) {
            $path_values = [end($path)];
          }
          if (!empty($formElementSettings['routing'])) {
            $field_name = $item->getFieldDefinition()->getName();
            $field_filter_name = $field_name . '_value';
            // Create arguments and url.
            $options = [
              $field_filter_name => $item_string,
            ];
            $url = Url::fromRoute($formElementSettings['routing'], $options);
          }
          foreach ($path_values as $value) {
            if (!empty($url) && $url instanceof Url) {
              $new_string .= '<span class="ls--format-item"><a href="' . $url->toString() . '">' . $value['text'] . '</a></span>';
            }
            else {
              $new_string .= '<span class="ls--format-item">' . $value['text'] . '</a></span>';

            }
          }
        }
      }
      $elements[$delta] = ['#markup' => $this->viewValue($new_string)];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param string $value
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(string $value) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Xss::filter($value));
  }

}
