<?php

namespace Drupal\ad_entity_vi\Plugin\ad_entity\AdType;

use Drupal\ad_entity\Entity\AdEntityInterface;
use Drupal\ad_entity\Plugin\AdTypeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\Config;
use Drupal\ad_entity\TargetingCollection;

/**
 * Type plugin for Video Intelligence advertisement.
 *
 * @AdType(
 *   id = "vi",
 *   label = "Video Intelligence"
 * )
 */
class ViType extends AdTypeBase {

  /**
   * {@inheritdoc}
   */
  public function globalSettingsForm(array $form, FormStateInterface $form_state, Config $config) {
    $element = [];

    $settings = $config->get($this->getPluginDefinition()['id']);

    $element['channel_id'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate('Channel ID'),
      '#default_value' => !empty($settings['channel_id']) ? $settings['channel_id'] : '',
      '#description' => $this->stringTranslation->translate('vi demand channel id.'),
      '#size' => 15,
      '#required' => TRUE,
    ];

    $element['publisher_id'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate('Publisher ID'),
      '#default_value' => !empty($settings['publisher_id']) ? $settings['publisher_id'] : '',
      '#description' => $this->stringTranslation->translate('vi publisher ID.'),
      '#size' => 15,
      '#required' => TRUE,
    ];

    $element['placement_id'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate('Placement ID'),
      '#default_value' => !empty($settings['placement_id']) ? $settings['placement_id'] : '',
      '#description' => $this->stringTranslation->translate('vi placement ID.'),
      '#size' => 15,
      '#required' => TRUE,
    ];

    $element['iab_category'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate('IAB Category'),
      '#default_value' => !empty($settings['iab_category']) ? $settings['iab_category'] : 'IAB18',
      '#size' => 15,
      '#description' => $this->stringTranslation->translate("IAB site content classification category. Tier 1 and tier 2 categories are supported. E.g. 'IAB8' or 'IAB8-1', see https://docs.vi.ai/integrations/list-of-iab-categories/ for list of categories."),
      '#required' => TRUE,
    ];

    $element['ad_unit_type'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate('AdUnit type'),
      '#default_value' => !empty($settings['ad_unit_type']) ? $settings['ad_unit_type'] : '2',
      '#size' => 15,
      '#description' => $this->stringTranslation->translate("Options are from 1 - 2. 1: Outstream; 2: vi stories unit (default)."),
      '#required' => TRUE,
    ];

    $element['maxrun'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate('Max run'),
      '#default_value' => !empty($settings['maxrun']) ? $settings['maxrun'] : 18,
      '#size' => 15,
      '#description' => $this->stringTranslation->translate("Max run value. E.g. '18'."),
      '#required' => TRUE,
    ];

    $element['midrolltime'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate('Midroll time'),
      '#default_value' => !empty($settings['midrolltime']) ? $settings['midrolltime'] : 45,
      '#size' => 15,
      '#description' => $this->stringTranslation->translate("Midroll time value. E.g. '45'."),
      '#required' => TRUE,
    ];

    $element['maximp'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate('Maximp'),
      '#default_value' => !empty($settings['maximp']) ? $settings['maximp'] : 2,
      '#size' => 15,
      '#description' => $this->stringTranslation->translate("Maximp value. E.g. '2'."),
      '#required' => TRUE,
    ];

    $element['vastretry'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate('Vastretry'),
      '#default_value' => !empty($settings['vastretry']) ? $settings['vastretry'] : 5,
      '#size' => 15,
      '#description' => $this->stringTranslation->translate("Vastretry value. E.g. '5'."),
      '#required' => TRUE,
    ];

    $element['bg_color'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate('Background color'),
      '#default_value' => !empty($settings['bg_color']) ? $settings['bg_color'] : '',
      '#size' => 15,
      '#description' => $this->stringTranslation->translate("Background color of the native video unit, HEX Values, e.g. '#faf8f8'."),
    ];

    $element['text_color'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate('Text color'),
      '#default_value' => !empty($settings['text_color']) ? $settings['text_color'] : '',
      '#size' => 15,
      '#description' => $this->stringTranslation->translate("Text color for the video title showing in the native video unit, HEX Values, e.g. '#faf8f8'."),
    ];

    $element['font'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate('Font'),
      '#default_value' => !empty($settings['font']) ? $settings['font'] : '',
      '#size' => 15,
      '#description' => $this->stringTranslation->translate("Font for the video title showing in the native video unit, i.e: 'Arial, Helvetica, Times New Roman', 'Times Roman, Courier New, Courier' etc; use fallback to a known font name."),
    ];

    $element['font_size'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate('Font size'),
      '#default_value' => !empty($settings['font_size']) ? $settings['font_size'] : '',
      '#size' => 15,
      '#description' => $this->stringTranslation->translate("Font size for the video title showing in the native video unit. E.g. '14px'."),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function entityConfigForm(array $form, FormStateInterface $form_state, AdEntityInterface $ad_entity) {
    $element = [];

    $settings = $ad_entity->getThirdPartySettings($this->getPluginDefinition()['provider']);

    $element['keywords'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate('Keywords'),
      '#default_value' => !empty($settings['keywords']) ? $settings['keywords'] : '',
      '#description' => $this->stringTranslation->translate("Comma separated values describing the content of the page e.g. 'cooking, grilling, pulled pork'."),
    ];

    $context = !empty($settings['targeting']) ? $settings['targeting'] : [];
    $targeting = isset($context['targeting']) ?
      new TargetingCollection($context['targeting']) : NULL;
    $element['targeting'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate('Default targeting'),
      '#description' => $this->stringTranslation->translate('Default pairs of key-values for targeting on the ad tag. Example: <strong>pos: top, category: value1, category: value2, ...</strong>'),
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
