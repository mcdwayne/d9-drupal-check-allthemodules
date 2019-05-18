<?php

namespace Drupal\facebook_mcc\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides Facebook Messenger Customer Chat Block.
 *
 * @Block(
 *   id = "facebook_mcc_block",
 *   admin_label = @Translation("Facebook Messenger Customer Chat"),
 *   category = @Translation("Facebook Messenger Customer Chat"),
 * )
 */
class FacebookMCCBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $facebookMccConfig;

  /**
   * Creates a FacebookMCCBlock object.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key ‘context’ may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->facebookMccConfig = $config_factory->get('facebook_mcc.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Check site language and set plugin language accordingly.
    $current_language = \Drupal::languageManager()->getCurrentLanguage();
    $language_map = $this->facebookMccConfig->get('facebook_mcc_language_map');
    if ($language_map && count($language_map) > 0) {
      $plugin_local = $language_map[$current_language->getId()]['localization'];
    }
    else {
      $plugin_local = 'en_US';
    }

    // Build block.
    $facebook_mcc_rendered_block = [
      '#attributes' => [
        'class' => ['fb-customerchat'],
        'page_id' => [$this->facebookMccConfig->get('facebook_mcc_page_id')],
        'theme_color' => [$this->facebookMccConfig->get('facebook_mcc_theme_color')],
        'logged_in_greeting' => [$this->facebookMccConfig->get('facebook_mcc_logged_in_greeting')],
        'logged_out_greeting' => [$this->facebookMccConfig->get('facebook_mcc_logged_out_greeting')],
        'greeting_dialog_delay' => [$this->facebookMccConfig->get('facebook_mcc_greeting_dialog_delay')],
      ],
      '#attached' => [
        'library' => [
          'facebook_mcc/facebook_mcc',
        ],
        'drupalSettings' => [
          'facebook_mcc_app_id' => $this->facebookMccConfig->get('facebook_mcc_app_id'),
          'facebook_mcc_local' => $plugin_local,
        ],
      ],
    ];

    // Check the mode of greeting display, if it's not default, then add it.
    $greeting_dialog_display = $this->facebookMccConfig->get('facebook_mcc_greeting_dialog_display');
    if ($greeting_dialog_display != 'default') {
      $facebook_mcc_rendered_block['#attributes']['greeting_dialog_display'] = $greeting_dialog_display;
    }

    return $facebook_mcc_rendered_block;
  }

}
