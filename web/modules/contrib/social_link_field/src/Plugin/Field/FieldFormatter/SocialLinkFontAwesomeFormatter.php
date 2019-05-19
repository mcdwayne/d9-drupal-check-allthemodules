<?php

namespace Drupal\social_link_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'social_link_field' formatter.
 *
 * @FieldFormatter(
 *   id = "font_awesome",
 *   label = @Translation("FonAwesome icons"),
 *   field_types = {
 *     "social_links"
 *   }
 * )
 */
class SocialLinkFontAwesomeFormatter extends SocialLinkBaseFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'icon_type' => 'common',
      'orientation' => 'vertical',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['icon_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select icon type'),
      '#options' => [
        'common' => $this->t('Common icon'),
        'square' => $this->t('Square icon'),
      ],
      '#default_value' => $this->getSetting('icon_type'),
    ];

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

    $configs = $this->configFactory->get('social_link_field.settings');
    $icon_type = $this->getSetting('icon_type');

    $element['#appearance'] = [
      'orientation' => $this->getSetting('orientation'),
    ];

    foreach ($items as $delta => $item) {
      $values = $item->getValue();
      $social = $values['social'];
      $link = $values['link'];

      if ($icon_type == 'square' && $this->platforms[$social]['iconSquare']) {
        $icon = $this->platforms[$social]['iconSquare'];
      }
      else {
        $icon = $this->platforms[$social]['icon'];
      }

      $element['#links'][$delta] = [
        'text' => '',
        'class' => 'fa ' . $icon,
        'url' => $this->platforms[$social]['urlPrefix'] . $link,
        'title' => $this->platforms[$social]['name'],
      ];
    }

    // Attach formatter library.
    $element['#attached']['library'][] = 'social_link_field/social_link_field.font_awesome_formatter';

    // Attach FontAwesome external library.
    if ($configs->get('attached_fa')) {
      $element['#attached']['library'][] = 'social_link_field/fontawesome.component';
    }

    return $element;
  }

}
