<?php

namespace Drupal\social_link_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'social_link_field' formatter.
 *
 * @FieldFormatter(
 *   id = "network_name",
 *   label = @Translation("Network name"),
 *   field_types = {
 *     "social_links"
 *   }
 * )
 */
class SocialLinkNetworkNameFormatter extends SocialLinkBaseFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'orientation' => 'vertical',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Orientation'),
      '#options' => [
        'vertical' => $this->t('Vertical'),
        'horizontal' => $this->t('Horizontal'),
      ],
      '#default_value' => $this->getSetting('orientation'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = parent::viewElements($items, $langcode);

    $element['#appearance'] = [
      'orientation' => $this->getSetting('orientation'),
    ];

    foreach ($items as $delta => $item) {
      $values = $item->getValue();
      $social = $values['social'];
      $link = $values['link'];

      $element['#links'][$delta] = [
        'text' => $this->platforms[$social]['name'],
        'class' => $social,
        'url' => $this->platforms[$social]['urlPrefix'] . $link,
        'title' => $this->platforms[$social]['name'],
      ];
    }

    return $element;
  }

}
