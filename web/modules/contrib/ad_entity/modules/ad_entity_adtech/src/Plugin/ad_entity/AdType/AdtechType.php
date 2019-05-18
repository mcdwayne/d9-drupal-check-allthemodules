<?php

namespace Drupal\ad_entity_adtech\Plugin\ad_entity\AdType;

use Drupal\ad_entity\Entity\AdEntityInterface;
use Drupal\ad_entity\Plugin\AdTypeBase;
use Drupal\ad_entity\TargetingCollection;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Type plugin for AdTech Factory advertisement.
 *
 * @AdType(
 *   id = "adtech_factory",
 *   label = "AdTech Factory"
 * )
 */
class AdtechType extends AdTypeBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setConfigFactory($container->get('config.factory'));
    return $instance;
  }

  /**
   * Set the config factory object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory object.
   */
  protected function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function globalSettingsForm(array $form, FormStateInterface $form_state, Config $config) {
    $element = [];

    $settings = $config->get($this->getPluginDefinition()['id']);

    $element['library_source'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate("Library source"),
      '#description' => $this->stringTranslation->translate("The source of the external AdTech Library, which will be embedded inside the HTML head."),
      '#default_value' => !empty($settings['library_source']) ? $settings['library_source'] : '',
      '#field_prefix' => 'src="',
      '#field_suffix' => '"',
    ];

    $targeting = !empty($settings['page_targeting']) ?
      new TargetingCollection($settings['page_targeting']) : NULL;
    $element['page_targeting'] = [
      '#type' => 'textfield',
      '#maxlength' => 2048,
      '#title' => $this->stringTranslation->translate("Default page targeting"),
      '#description' => $this->stringTranslation->translate("Default pairs of key-values for targeting on the page. Example: <strong>pos: top, category: value1, category: value2, ...</strong>"),
      '#default_value' => !empty($targeting) ? $targeting->toUserOutput() : '',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function globalSettingsSubmit(array &$form, FormStateInterface $form_state, Config $config) {
    $id = $this->getPluginDefinition()['id'];
    $values = $form_state->getValue($id);

    if (!empty($values['page_targeting'])) {
      // Convert the targeting to a JSON-encoded string.
      $targeting = new TargetingCollection();
      $targeting->collectFromUserInput($values['page_targeting']);
      $config->set($id . '.page_targeting', $targeting->toArray());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityConfigForm(array $form, FormStateInterface $form_state, AdEntityInterface $ad_entity) {
    $element = [];

    $settings = $ad_entity->getThirdPartySettings($this->getPluginDefinition()['provider']);

    $element['data_atf'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate("Value for the data-atf attribute on the ad tag"),
      '#default_value' => !empty($settings['data_atf']) ? $settings['data_atf'] : 'tag',
      '#field_prefix' => 'data-atf="',
      '#field_suffix' => '"',
      '#size' => 10,
      '#required' => TRUE,
    ];

    $element['data_atf_format'] = [
      '#type' => 'textfield',
      '#title' => $this->stringTranslation->translate("Value for the data-atf-format attribute on the ad tag"),
      '#default_value' => !empty($settings['data_atf_format']) ? $settings['data_atf_format'] : '',
      '#description' => $this->stringTranslation->translate("Examples: <strong>leaderboard, skyscraper, rectangle, special</strong>"),
      '#field_prefix' => 'data-atf-format="',
      '#field_suffix' => '"',
      '#size' => 30,
      '#required' => TRUE,
    ];

    $context = !empty($settings['targeting']) ? $settings['targeting'] : [];
    $targeting = isset($context['targeting']) ?
      new TargetingCollection($context['targeting']) : NULL;
    if (!isset($targeting) && $ad_entity->isNew()) {
      $targeting = $this->defaultTargeting();
    }
    $element['targeting'] = [
      '#type' => 'textfield',
      '#maxlength' => 2048,
      '#title' => $this->stringTranslation->translate("Default targeting"),
      '#description' => $this->stringTranslation->translate("Default pairs of key-values for targeting on the ad tag. Example: <strong>pos: top, category: value1, category: value2, ...</strong>"),
      '#default_value' => !empty($targeting) ? $targeting->toUserOutput() : '',
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

  /**
   * Returns a default targeting collection.
   *
   * @return \Drupal\ad_entity\TargetingCollection
   *   The default targeting collection.
   */
  protected function defaultTargeting() {
    $info = [];
    if ($config = $this->configFactory->get('system.site')) {
      $info['website'] = $config->get('name');
    }
    return new TargetingCollection($info);
  }

}
