<?php

namespace Drupal\simpleads\Plugin\SimpleAds\Type;

use Drupal\simpleads\SimpleAdsTypeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simpleads\Ads;

/**
 * Text Ad type.
 *
 * @SimpleAdsType(
 *   id = "text",
 *   name = @Translation("Text Ad")
 * )
 */
class Text extends SimpleAdsTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL, $id = NULL) {
    $ad = (new Ads())->setId($id)->load();
    $options = $ad->getOptions(TRUE);
    $form['text'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Advertisement Text'),
      '#description'   => $this->t('No HTML allowed'),
      '#default_value' => !empty($options['text']) ? $options['text'] : '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function createFormSubmit($options, FormStateInterface $form_state, $type = NULL) {
    if ($text = $form_state->getValue('text')) {
      $options['text'] = $text;
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function updateFormSubmit($options, FormStateInterface $form_state, $type = NULL, $id = NULL) {
    if ($text = $form_state->getValue('text')) {
      $options['text'] = $text;
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function theme() {
    return [
      'text.simpleads' => [
        'variables' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {

  }

}
