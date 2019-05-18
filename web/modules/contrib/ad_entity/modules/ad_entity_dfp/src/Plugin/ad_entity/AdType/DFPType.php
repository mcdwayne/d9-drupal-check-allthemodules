<?php

namespace Drupal\ad_entity_dfp\Plugin\ad_entity\AdType;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;
use Drupal\ad_entity\Entity\AdEntityInterface;
use Drupal\ad_entity\Plugin\AdTypeBase;
use Drupal\ad_entity\TargetingCollection;

/**
 * Type plugin for DFP advertisement.
 *
 * @AdType(
 *   id = "dfp",
 *   label = "Doubleclick for Publishers (DFP)"
 * )
 */
class DFPType extends AdTypeBase {

  /**
   * A list of valid named sizes.
   *
   * @var array
   */
  static protected $validNamedSizes = ['fluid'];

  /**
   * {@inheritdoc}
   */
  public function globalSettingsForm(array $form, FormStateInterface $form_state, Config $config) {
    $element = [];

    $settings = $config->get($this->getPluginDefinition()['id']);

    $element['order_info'] = [
      '#type' => 'checkbox',
      '#title' => $this->stringTranslation->translate('Include targeting information about the slot loading order'),
      '#description' => $this->stringTranslation->translate('When enabled, the targeting includes the following: Slot order will be numbered (targeting key <em>slotNumber</em>) and whether the slot was loaded during initial page load (targeting key <em>onPageLoad</em>).'),
      '#default_value' => isset($settings['order_info']) ? (int) $settings['order_info'] : 1,
      '#weight' => 10,
    ];

    $correlator_options = [
      'default' => $this->stringTranslation->translate('Let the GPT service handle on its own (default)'),
      1 => $this->stringTranslation->translate('Always generate a new correlator'),
      0 => $this->stringTranslation->translate('Do not change the correlator'),
    ];
    $element['change_correlator'] = [
      '#type' => 'select',
      '#title' => $this->stringTranslation->translate('Correlator behavior for fetching ads'),
      '#description' => $this->stringTranslation->translate('Whether or not a new correlator is to be generated for fetching ads.'),
      '#default_value' => isset($settings['change_correlator']) ? (int) $settings['change_correlator'] : 'default',
      '#options' => $correlator_options,
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

    if (isset($values['order_info'])) {
      // Make sure the value is being stored as boolean.
      $config->set($id . '.order_info', (bool) $values['order_info']);
    }
    if (isset($values['change_correlator'])) {
      if (($values['change_correlator'] == 'default') || !is_numeric($values['change_correlator'])) {
        $config->clear($id . '.change_correlator');
      }
      else {
        $change_correlator = (int) $values['change_correlator'];
        $config->set($id . '.change_correlator', (bool) $change_correlator);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityConfigForm(array $form, FormStateInterface $form_state, AdEntityInterface $ad_entity) {
    $element = [];

    $settings = $ad_entity->getThirdPartySettings($this->getPluginDefinition()['provider']);

    $element['network_id'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate("DFP Network ID"),
      '#default_value' => !empty($settings['network_id']) ? $settings['network_id'] : '',
      '#size' => 15,
      '#required' => TRUE,
    ];

    $element['unit_id'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate("DFP Ad Unit ID / Pattern"),
      '#default_value' => !empty($settings['unit_id']) ? $settings['unit_id'] : '',
      '#size' => 15,
      '#required' => TRUE,
    ];

    $element['out_of_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->stringTranslation->translate("Define as out of page slot"),
      '#description' => $this->stringTranslation->translate("Out of page slots don't require or use any ad size formats. <strong>Not supported</strong> on Accelerated Mobile Pages. Click <a href='@url' target='_blank' rel='noopener noreferrer'>here</a> to find out more about out of page slots.", ['@url' => 'https://support.google.com/dfp_premium/answer/6088046?hl=en']),
      '#default_value' => !empty($settings['out_of_page']) ? 1 : 0,
    ];

    // Convert sizes settings to user-input format.
    $sizes_default = [];
    if (!empty($settings['sizes'])) {
      $decoded = Json::decode($settings['sizes']);
      if (!empty($decoded)) {
        foreach ($decoded as $size) {
          if (is_array($size)) {
            $sizes_default[] = $size[0] . 'x' . $size[1];
          }
          else {
            $sizes_default[] = $size;
          }
        }
      }
    }
    $sizes_default = implode(',', $sizes_default);
    $element['sizes'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate("Ad size formats"),
      '#description' => $this->stringTranslation->translate("Separate multiple sizes with comma. Example: <strong>300x600,300x250</strong>. Also may include <strong>fluid</strong> to display <a href='@url' target='_blank' rel='noopener noreferrer'>native ads</a> (currently not supported on Accelerated Mobile Pages).", ['@url' => 'https://support.google.com/dfp_premium/answer/6366905']),
      '#default_value' => $sizes_default,
      '#size' => 40,
      '#states' => [
        'disabled' => [
          'input[data-drupal-selector="edit-third-party-settings-ad-entity-dfp-out-of-page"]' => ['checked' => TRUE],
        ],
      ],
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

    $sizes_value = trim($values['sizes']);
    if (!empty($sizes_value)) {
      // Convert the user-input of sizes to a proper format.
      $size_pairs = explode(',', $sizes_value);
      $sizes = [];
      foreach ($size_pairs as $pair) {
        $pair = trim($pair);
        $parts = explode('x', $pair);
        $parts[0] = trim($parts[0]);
        if ((count($parts) === 2) && (is_numeric($parts[0])) && (is_numeric($parts[1]))) {
          $sizes[] = [(int) $parts[0], (int) $parts[1]];
        }
        elseif ((count($parts) === 1) && static::isValidNamedSize($parts[0])) {
          $sizes[] = $parts[0];
        }
      }
      $encoded = Json::encode($sizes);
      $ad_entity->setThirdPartySetting($provider, 'sizes', $encoded);
    }
    else {
      $ad_entity->setThirdPartySetting($provider, 'sizes', NULL);
    }

    $ad_entity->setThirdPartySetting($provider, 'out_of_page', !empty($values['out_of_page']));
  }

  /**
   * Checks whether the given parameter is a valid named size.
   *
   * @param string $size
   *   The named size to check for.
   *
   * @return bool
   *   TRUE if the given parameter is a valid named size, FALSE otherwise.
   */
  public static function isValidNamedSize($size) {
    return in_array($size, static::$validNamedSizes);
  }

}
