<?php

namespace Drupal\ad_entity_adtech\Plugin\ad_entity\AdView;

use Drupal\ad_entity\Entity\AdEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * View handler plugin for AdTech Factory ads in Facebook Instant Articles.
 *
 * @AdView(
 *   id = "adtech_fia",
 *   label = "AdTech Factory tag for Facebook Instant Articles",
 *   container = "fia",
 *   allowedTypes = {
 *     "adtech_factory"
 *   }
 * )
 */
class AdtechFia extends AdtechIframe {

  /**
   * {@inheritdoc}
   */
  public function entityConfigForm(array $form, FormStateInterface $form_state, AdEntityInterface $ad_entity) {
    $element = parent::entityConfigForm($form, $form_state, $ad_entity);

    $settings = $ad_entity->getThirdPartySettings($this->getPluginDefinition()['provider']);

    $element['iframe']['title']['#default_value'] = !empty($settings['iframe']['title']) ? $settings['iframe']['title'] : 'fbinstantarticles';

    $element['targeting_hint'] = [
      '#markup' => $this->stringTranslation->translate("For Facebook Instant Articles, make sure the <strong>default targeting</strong> above contains <strong>website: Your Website name</strong>, <strong>platform: FIA</strong> and  - in case you have no other channel specified - <strong>channel: FIA</strong>."),
      '#weight' => '-10',
    ];

    return $element;
  }

}
