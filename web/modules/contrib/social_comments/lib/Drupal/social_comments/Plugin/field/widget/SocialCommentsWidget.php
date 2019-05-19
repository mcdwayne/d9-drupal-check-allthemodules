<?php

/**
 * @file
 * Contains \Drupal\social_comments\Plugin\field\widget\SocialCommentsWidget.
 */

namespace Drupal\social_comments\Plugin\field\widget;

use Drupal\field\Annotation\FieldWidget;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Field\FieldItemListInterface;
use Drupal\field\Plugin\Type\Widget\WidgetBase;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'social_comments' widget.
 *
 * @FieldWidget(
 *   id = "social_comments",
 *   label = @Translation("Social comments field"),
 *   field_types = {
 *     "social_comments_google",
 *     "social_comments_twitter",
 *     "social_comments_facebook"
 *   },
 *   settings = {
 *     "placeholder" = ""
 *   }
 * )
 */
class SocialCommentsWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $element['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', array('@placeholder' => $placeholder));
    }
    else {
      $summary[] = t('No placeholder');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $element['url'] = array(
      '#type' => 'url',
      '#title' => $element['#title'],
      '#placeholder' => $this->getSetting('placeholder'),
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#maxlength' => 2048,
      '#required' => $element['#required'],
      '#description' => $element['#description'],
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, array &$form_state) {
    return $element['url'];
  }

}
