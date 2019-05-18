<?php

namespace Drupal\micro_site\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\micro_site\Entity\SiteInterface;

/**
 * Plugin implementation of the 'uri_link' formatter.
 *
 * @FieldFormatter(
 *   id = "site_url_link",
 *   label = @Translation("Site URL to link"),
 *   field_types = {
 *     "string",
 *   }
 * )
 */
class SiteUrlFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'target' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['target'] = [
      '#type' => 'checkbox',
      '#title' => t('Open link in new window'),
      '#return_value' => '_blank',
      '#default_value' => $this->getSetting('target'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    if (!empty($settings['target'])) {
      $summary[] = t('Open link in new window');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $options = [];
    $entity = $items->getEntity();
    $settings = $this->getSettings();
    if ($settings['target']) {
      $options['attributes']['target'] = '_blank';
    }

    if ($entity instanceof SiteInterface) {
      $uri = $entity->getSitePath();
      $options['absolute'] = TRUE;
      foreach ($items as $delta => $item) {
        if (!$item->isEmpty()) {
          $elements[$delta] = [
            '#type' => 'link',
            '#url' => Url::fromUri($uri, $options),
            '#title' => $item->value,
          ];
        }
      }
    }

    return $elements;
  }

}
