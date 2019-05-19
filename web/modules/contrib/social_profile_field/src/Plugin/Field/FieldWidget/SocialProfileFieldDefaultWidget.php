<?php

/**
 * @file
 * Contains \Drupal\social_profile_field\Plugin\Field\FieldWidget\SocialProfileFieldDefaultWidget.
 */

namespace Drupal\social_profile_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\UriWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'social_profile_field_default' widget.
 *
 * @FieldWidget(
 *   id = "social_profile_field_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "social_profile_url"
 *   }
 * )
 */
class SocialProfileFieldDefaultWidget extends UriWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['value'] = $element['value'] + [
      '#attached' => [
        'library' => [
          'social_profile_field/social_profile_field.css',
          'social_profile_field/social_profile_field.js',
        ],
      ],
      '#attributes' => [
        'class' => ['edit-field-social-profile-url']
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'placeholder' => t('Put your social profile url'),
    ] + parent::defaultSettings();
  }

}
