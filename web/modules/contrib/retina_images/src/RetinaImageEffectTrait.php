<?php

/**
 * @file
 * Provides shared helpers for all retina image effects.
 */
namespace Drupal\retina_images;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides shared helpers for all retina image effects.
 *
 *
 * @package Drupal\retina_images
 */
trait RetinaImageEffectTrait {

  /**
   * @var int
   */
  var $defaultMultiplier = 2;


  /**
   * Multiply a dimension by the specified resolution multiplier.
   *
   * Only modifies input if effect has 'retinafy' option enabled.
   *
   * @param $dimension
   *   The dimension to be altered.
   * @param null $multiplier
   *   (optional) Multiplier to use. If not specified, here, the default from
   *   configuration will be used. If configuration is empty, default from
   *   self::$defaultMultiplier will be used.
   *
   * @return int
   *   The new dimensions.
   */
  protected function multiplyDimension($dimension, $multiplier = NULL) {
    if ($this->configuration['retinafy']) {
      if (!$multiplier) {
        $multiplier = $this->getMultiplier();
      }
      return (int) ($dimension * $multiplier);
    }
    else {
      return (int) $dimension;
    }
  }

  /**
   * Get the multiplier for this effect.
   *
   * @return int
   *   The mutiplier to be used for this effect.
   */
  protected function getMultiplier() {
    if (isset($this->configuration['multiplier'])) {
      return $this->configuration['multiplier'];
    }
    else {
      return $this->defaultMultiplier;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $this->prepareForm($form, $this->configuration);
    return $form;
  }

  /**
   * Add the retinafy checkbox to the configuration options form.
   *
   * @param $form
   * @param array $configuration
   */
  protected function prepareForm(&$form, array $configuration) {
    $form['retinafy'] = [
      '#type' => 'checkbox',
      '#default_value' => $configuration['retinafy'],
      '#title' => $this->t('Retinafy'),
      '#description' => $this->t('Scale and output this image with increased resolution. It is recommended to allow upscaling with this option and set image qualtiy to 25.'),
    ];

    $form['retina_multiplier'] = [
      '#type' => 'textfield',
      '#default_value' => $configuration['multiplier'],
      '#title' => $this->t('Resolution multiplier'),
      '#description' => $this->t('Specify a different resolution multiplier to be used when scaling the image.'),
      '#states' => [
        'visible' => [
          ':input[name="data[retinafy]"]' => ['checked' => TRUE],
        ],
      ],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ((!is_numeric($form_state->getValue('retina_multiplier'))) && $form_state->getValue('retinafy')) {
      $form_state->setError($form['data']['retina_multiplier'], $this->t("Multiplier must be a valid number, such as '2' or '1.5'"));
    }
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['retinafy'] = $form_state->getValue('retinafy');
    $this->configuration['multiplier'] = $form_state->getValue('retina_multiplier');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'retinafy' => FALSE,
      'multiplier' => 2,
    ];

  }

}
