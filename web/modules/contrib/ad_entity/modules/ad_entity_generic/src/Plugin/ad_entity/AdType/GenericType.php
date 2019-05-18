<?php

namespace Drupal\ad_entity_generic\Plugin\ad_entity\AdType;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ad_entity\Entity\AdEntityInterface;
use Drupal\ad_entity\Plugin\AdTypeBase;
use Drupal\ad_entity\TargetingCollection;

/**
 * Type plugin for Generic ad slots.
 *
 * @AdType(
 *   id = "generic",
 *   label = "Generic slot"
 * )
 */
class GenericType extends AdTypeBase {

  /**
   * {@inheritdoc}
   */
  public function globalSettingsForm(array $form, FormStateInterface $form_state, Config $config) {
    $element = [];

    $settings = $config->get($this->getPluginDefinition()['id']);

    $element['page_targeting'] = [
      '#type' => 'fieldset',
      '#title' => $this->stringTranslation->translate('Page targeting'),
      '#weight' => 10,
    ];
    $element['page_targeting']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->stringTranslation->translate('Provide page targeting as a global JavaScript variable.'),
      '#description' => $this->stringTranslation->translate('When enabled, applicable targeting for all ad slots will be included as a global JavaScript variable.'),
      '#default_value' => !empty($settings['page_targeting']['enabled']),
      '#weight' => 10,
    ];
    $element['page_targeting']['js_variable'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate('Page targeting JavaScript variable'),
      '#description' => $this->stringTranslation->translate('The name of the JavaScript variable, handled as an array of arbitrary targeting entries.'),
      '#default_value' => !empty($settings['page_targeting']['js_variable']) ? $settings['page_targeting']['js_variable'] : 'dataLayer',
      '#states' => [
        'visible' => [
          'input[name="generic[page_targeting][enabled]"]' => ['checked' => TRUE],
        ],
      ],
      '#weight' => 20,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function globalSettingsSubmit(array &$form, FormStateInterface $form_state, Config $config) {
    $id = $this->getPluginDefinition()['id'];
    $values = $form_state->getValue($id);

    if (!empty($values['page_targeting']['enabled'])) {
      $config->set($id . '.page_targeting.enabled', !empty($values['page_targeting']['enabled']));
      $config->set($id . '.page_targeting.js_variable', preg_replace('/[^a-zA-Z0-9\_]+/', '', $values['page_targeting']['js_variable']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityConfigForm(array $form, FormStateInterface $form_state, AdEntityInterface $ad_entity) {
    $element = [];

    $settings = $ad_entity->getThirdPartySettings($this->getPluginDefinition()['provider']);

    $element['id'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate('Identifier'),
      '#default_value' => !empty($settings['id']) ? $settings['id'] : '',
      '#size' => 20,
      '#required' => TRUE,
    ];

    $element['format'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate("Display format"),
      '#default_value' => !empty($settings['format']) ? $settings['format'] : '',
      '#size' => 20,
      '#required' => TRUE,
    ];

    $context = !empty($settings['targeting']) ? $settings['targeting'] : [];
    $targeting = isset($context['targeting']) ?
      new TargetingCollection($context['targeting']) : NULL;
    $element['targeting'] = [
      '#type' => 'textfield',
      '#maxlength' => 2048,
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
