<?php

namespace Drupal\ad_entity_smart\Plugin\ad_entity\AdType;

use Drupal\ad_entity\Entity\AdEntityInterface;
use Drupal\ad_entity\Plugin\AdTypeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\Config;
use Drupal\ad_entity\TargetingCollection;

/**
 * Type plugin for Smart advertisement.
 *
 * @AdType(
 *   id = "smart",
 *   label = "Smart AdServer"
 * )
 */
class SmartType extends AdTypeBase {

  /**
   * {@inheritdoc}
   */
  public function globalSettingsForm(array $form, FormStateInterface $form_state, Config $config) {
    $element = [];

    $settings = $config->get($this->getPluginDefinition()['id']);

    $element['site_id'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate("Site ID"),
      '#default_value' => !empty($settings['site_id']) ? $settings['site_id'] : '',
      '#size' => 15,
      '#required' => TRUE,
    ];

    $element['network_id'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate("Network ID"),
      '#default_value' => !empty($settings['network_id']) ? $settings['network_id'] : '',
      '#size' => 15,
      '#required' => TRUE,
    ];

    $element['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate("Domain"),
      '#default_value' => !empty($settings['domain']) ? $settings['domain'] : '',
      '#description' => $this->stringTranslation->translate('No trailing slash is needed. Example: "//ced.sascdn.com".'),
      '#size' => 15,
      '#required' => TRUE,
    ];

    $element['library_url'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate("Smart.js library URL"),
      '#default_value' => !empty($settings['library_url']) ? $settings['library_url'] : '',
      '#description' => $this->stringTranslation->translate('Path to the smart.js library. No trailing slash is needed. Example: //ced.sascdn.com/tag/1003/smart.js". Default value is //ced.sascdn.com/tag/{network_id}/smart.js.'),
      '#size' => 15,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function entityConfigForm(array $form, FormStateInterface $form_state, AdEntityInterface $ad_entity) {
    $element = [];

    $settings = $ad_entity->getThirdPartySettings($this->getPluginDefinition()['provider']);

    $element['format_id'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate("Format ID"),
      '#default_value' => !empty($settings['format_id']) ? $settings['format_id'] : '',
      '#size' => 15,
      '#required' => TRUE,
    ];

    $context = !empty($settings['targeting']) ? $settings['targeting'] : [];
    $targeting = isset($context['targeting']) ?
      new TargetingCollection($context['targeting']) : NULL;
    $element['targeting'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate("Default targeting"),
      '#description' => $this->stringTranslation->translate("Default pairs of key-values for targeting on the ad tag. Example: <strong>pos: top, category: value1, category: value2, ...</strong>"),
      '#default_value' => isset($targeting) ? $targeting->toUserOutput() : '',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function entityConfigSubmit(array &$form, FormStateInterface $form_state, AdEntityInterface $ad_entity) {
    $provider = $this->getPluginDefinition()['provider'];
    $values = $form_state->getValue(['third_party_settings', $provider]);

    $targeting_empty = TRUE;
    $targeting_value = trim($values['targeting']);
    if (!empty($targeting_value)) {
      // Set the default targeting as context settings.
      $targeting = new TargetingCollection();
      $targeting->collectFromUserInput($targeting_value);
      if (!$targeting->isEmpty()) {
        $context_data = ['targeting' => $targeting->toArray()];
        $ad_entity->setThirdPartySetting($provider, 'targeting', $context_data);
        $targeting_empty = FALSE;
      }
    }
    if ($targeting_empty) {
      $ad_entity->setThirdPartySetting($provider, 'targeting', NULL);
    }
  }

}
