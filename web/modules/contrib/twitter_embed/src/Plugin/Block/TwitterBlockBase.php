<?php

namespace Drupal\twitter_embed\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\twitter_embed\TwitterButtonWidget;
use Drupal\twitter_embed\TwitterWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\twitter_embed\TwitterWidgetInterface;

/**
 * TwitterBlockBase class.
 */
abstract class TwitterBlockBase extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\twitter_embed\TwitterWidgetInterface definition.
   *
   * @var \Drupal\twitter_embed\TwitterWidgetInterface
   */
  protected $twitterWidget;

  /**
   * Constructs a new TwitterButtonBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TwitterWidgetInterface $twitter_widget
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->twitterWidget = $twitter_widget;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('twitter_embed.button_widget')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return TwitterButtonWidget::getDefaultSettings()
    + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#default_value' => $this->configuration['username'],
      '#required' => TRUE,
      '#field_prefix' => '@',
      '#maxlength' => TwitterWidgetInterface::USERNAME_MAX_LENGTH,
      '#size' => TwitterWidgetInterface::USERNAME_MAX_LENGTH,
    ];
    $settingsForm = $this->twitterWidget->getSettingsForm($this->configuration);
    // Append javascript states.
    $selector = 'settings';
    $settingsFormWithStates = $this->twitterWidget->setSettingsFormStates($settingsForm, $selector);
    return $form + $settingsFormWithStates;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    foreach ($this->twitterWidget->getAvailableSettings() as $key => $setting) {
      // @todo use recursivity to handle nested arrays
      // @todo could not fit with some values (checkboxes)
      if (is_array($setting)) {
        foreach ($setting as $childKey => $childSetting) {
          $this->configuration[$childKey] = $form_state->getValue([$key, $childKey]);
        }
      }
      else {
        $this->configuration[$key] = $form_state->getValue($key);
      }
    }
    $this->twitterWidget->setDependentConfiguration($this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->twitterWidget->getWidget($this->configuration);
  }

}
