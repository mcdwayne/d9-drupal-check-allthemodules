<?php

namespace Drupal\amp\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\amp\AmpFormTrait;
use Drupal\amp\Element\AmpSocialPost;

/**
 * Plugin implementation of the 'amp_social_post' formatter.
 *
 * @FieldFormatter(
 *   id = "amp_social_post_formatter",
 *   label = @Translation("AMP SocialPost"),
 *   description = @Translation("Display an amp-social_post post."),
 *   field_types = {
 *     "link",
 *     "string",
 *     "string_long"
 *   }
 * )
 */
class AmpSocialPostFormatter extends FormatterBase {

  use AmpFormTrait;

  /**
   * AMP layouts
   *
   * Expected by AmpFormTrait.
   *
   * @return array
   *   Array of layout options allowed by this component.
   */
  private function getLayouts() {
    $options = $this->allLayouts();
    unset($options['intrinsic']);
    unset($options['container']);
    return $options;
  }

  /**
   * AMP libraries
   *
   * Expected by AmpFormTrait.
   *
   * @return array
   *   The names of the AMP libraries used by this formatter.
   */
  private function getLibraries() {
    return AmpSocialPost::getLibraries();
  }

 /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'layout' => 'responsive',
      'width' => '',
      'height' => '',
      'provider' => '',
      'data-embed-as' => 'post',
      'data-align-center' => '',
      'placeholder' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $provider_selector = str_replace('[layout]', '[provider]', $this->layoutSelector());
    $form['provider'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Provider'),
      '#options' => AmpSocialPost::getProviders(),
      '#default_value' => $this->getSetting('provider'),
      '#description' => $this->t('Select the allowed social providers for the post url in this field. Posts from other providers will not display.'),
      '#multiple' => TRUE,
    ];
    $form['data-embed-as'] = [
      '#type' => 'select',
      '#options' => ['post' => $this->t('Post'), 'video' => $this->t('Video')],
      '#title' => $this->t('Facebook: Embed as'),
      '#default_value' => $this->getSetting('data-embed-as'),
      '#states' => ['visible' => [
        [$provider_selector => ['value' => 'facebook']],
      ]],
    ];
    $form['data-align-center'] = [
      '#type' => 'select',
      '#options' => ['' => $this->t('False'), 'true' => $this->t('True')],
      '#title' => $this->t('Facebook: Center'),
      '#default_value' => $this->getSetting('data-align-center'),
      '#states' => ['visible' => [
        [$provider_selector => ['value' => 'facebook']],
      ]],
    ];
    $form['placeholder'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Twitter: Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => $this->t('Placeholder text to appear until the Tweet is retrieved.'),
      '#states' => ['visible' => [
        [$provider_selector => ['value' => 'twitter']],
      ]],
    ];

    $form['layout'] = $this->layoutElement();
    $form['width'] = $this->widthElement();
    $form['height'] = $this->heightElement();

    $form['#prefix'] = '<div class="description">' . $this->libraryDescription() . '</div>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Provider: ') . implode(', ', $this->getSetting('provider'));
    $settings = [
      'data-align-center' => $this->t('Facebook centered'),
      'data-embed-as' => $this->t('Facebook embed as'),
    ];
    foreach ($settings as $setting => $label) {
      $value = $this->getSetting($setting);
      if (isset($value)) {
        $summary[] = $label . $this->t(': :value', [':value' => $value]);
      }
    }
    $summary[] = $this->t('Twitter placeholder') . ':' . (!empty($this->getSetting('placeholder')) ? $this->t('Yes') : $this->t('No'));
    $summary = $this->addToSummary($summary);
    return [implode('; ', $summary)];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $layout = $this->getSetting('layout');
    $width = $this->validWidth($this->getSetting('width'), $this->getSetting('layout'));
    $height = $this->validHeight($this->getSetting('height'), $this->getSetting('layout'));
    $data_embed_as = $this->getSetting('data-embed-as');
    $data_align_center = $this->getSetting('data-align-center');
    $placeholder = $this->getSetting('placeholder');
    foreach ($items as $delta => $item) {
      $elements[$delta]['#type'] = 'amp_social_post';
      $elements[$delta]['#url'] = !empty($item->value) ? $item->value : $item->uri;;
      $elements[$delta]['#placeholder'] = $placeholder;
      $elements[$delta]['#attributes']['layout'] = $layout;
      $elements[$delta]['#attributes']['width'] = $width;
      $elements[$delta]['#attributes']['height'] = $height;
      $elements[$delta]['#attributes']['data-embed-as'] = $data_embed_as;
      $elements[$delta]['#attributes']['data-align-center'] = $data_align_center;
    }
    return $elements;
  }

}
