<?php

namespace Drupal\ad_entity_dfp\Plugin\ad_entity\AdView;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ad_entity\Entity\AdEntityInterface;
use Drupal\ad_entity\Plugin\AdViewBase;

/**
 * View handler plugin for DFP advertisement as iFrames.
 *
 * @AdView(
 *   id = "dfp_iframe",
 *   label = "DFP tag as iFrame",
 *   container = "iframe",
 *   allowedTypes = {
 *     "dfp"
 *   }
 * )
 */
class DFPIframe extends AdViewBase {

  /**
   * {@inheritdoc}
   */
  public function build(AdEntityInterface $entity) {
    return [
      '#theme' => 'dfp_iframe',
      '#ad_entity' => $entity,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function entityConfigForm(array $form, FormStateInterface $form_state, AdEntityInterface $ad_entity) {
    $element = [];

    $settings = $ad_entity->getThirdPartySettings($this->getPluginDefinition()['provider']);

    $element['iframe']['width'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->stringTranslation->translate('iFrame width'),
      '#size' => 10,
      '#field_prefix' => 'width="',
      '#field_suffix' => '"',
      '#default_value' => !empty($settings['iframe']['width']) ? $settings['iframe']['width'] : '',
    ];

    $element['iframe']['height'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->stringTranslation->translate('iFrame height'),
      '#size' => 10,
      '#field_prefix' => 'height="',
      '#field_suffix' => '"',
      '#default_value' => !empty($settings['iframe']['height']) ? $settings['iframe']['height'] : '',
    ];

    return $element;
  }

}
