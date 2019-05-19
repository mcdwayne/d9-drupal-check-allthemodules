<?php

/**
 * @file
 * Contains \Drupal\social_profile_field\Plugin\field\formatter\SocialProfileFieldDefaultFormatter.
 */

namespace Drupal\social_profile_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\UriLinkFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'social_profile_field_default' formatter.
 *
 * Shows list of links.
 *
 * @FieldFormatter(
 *   id = "social_profile_field_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "social_profile_url",
 *   }
 * )
 */
class SocialProfileFieldDefaultFormatter extends UriLinkFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'icons_show' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items);
    $path_to_icon = drupal_get_path('module', 'social_profile_field') . '/icons/';
    $show_icons = $this->getSetting('icons_show');

    foreach ($elements as $delta => $element) {
      if ($show_icons) {
        $icon_path = $path_to_icon . 'default.png';
        $domain_icon = $path_to_icon . parse_url(check_url(trim($items[$delta]->value)), PHP_URL_HOST) . '.png';
        if (file_exists($domain_icon)) {
          $icon_path = $domain_icon;
        }

        $elements[$delta]['#title'] = [
          '#theme' => 'image',
          '#uri' => $icon_path,
          '#attributes' => [
            'class' => ['social-profile-icon']
          ],
        ];

        $elements[$delta]['#attached'] = [
          'library' => [
            'social_profile_field/social_profile_field.css',
          ],
        ];
      }

      $elements[$delta]['#attributes'] = ['class' => ['social-link']];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['icons_show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show icons instead urls list.'),
      '#default_value' => $this->getSetting('icons_show'),
      '#description' => $this->t("Outputs Social Network icons linked to user's profiles."),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    if ($this->getSetting('icons_show')) {
      return [$this->t('Show Icons instead urls list')];
    }
    return [];
  }
}
