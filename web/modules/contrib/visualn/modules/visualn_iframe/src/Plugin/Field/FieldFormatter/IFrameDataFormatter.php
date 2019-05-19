<?php

namespace Drupal\visualn_iframe\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'visualn_iframe_data' formatter.
 *
 * @FieldFormatter(
 *   id = "visualn_iframe_data",
 *   label = @Translation("VisualN IFrame data"),
 *   field_types = {
 *     "visualn_iframe_data"
 *   }
 * )
 */
class IFrameDataFormatter extends FormatterBase {

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
      $elements[$delta] = $this->viewValue($item);
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {

    // @todo: show iframe generated markup
    //   respect view persmissions for the current user

    $iframe_entity = $item->getEntity();

    $handler_key = $iframe_entity->get('handler_key')->value;
    $data = $iframe_entity->getData();
    $settings = $iframe_entity->getSettings();
    $content_provider = \Drupal::service('visualn_iframe.content_provider');
    $render = $content_provider->provideContent($handler_key, $data, $settings);
    // no need to add visualn_iframe::id cache tag here
    // since it will rebuild if changed

    return $render;
  }

}
