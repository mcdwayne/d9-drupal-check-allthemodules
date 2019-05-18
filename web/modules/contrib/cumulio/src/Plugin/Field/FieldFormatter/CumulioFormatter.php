<?php

namespace Drupal\cumulio\Plugin\Field\FieldFormatter;

use Cumulio\Cumulio;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

/**
 * Plugin implementation of the 'cumulio_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "cumulio_formatter",
 *   label = @Translation("Cumulio formatter"),
 *   field_types = {
 *     "cumulio_field"
 *   }
 * )
 */
class CumulioFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $value = $item->getValue();
      $elements[$delta] = [
        '#type' => 'item',
        '#markup' => $this->viewValue($value['value']),
      ];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue($dashboardId) {
    $config = \Drupal::config('cumulio.settings');
    $token = $config->get('api_token');
    $key = $config->get('api_key');
    $authorization = [
      'id' => $key,
      'token' => $token,
    ];
    $cumulio = Cumulio::initialize($key, $token);
    // Create connection to the api and get the dashboard for the correct id.
    $iframe = $cumulio->iframe($dashboardId, $authorization);
    $markup = '<iframe style="border: none;" width="100%" height="400px" src="' . $iframe . '"></iframe>';
    return Markup::create($markup);;
  }

}
