<?php

namespace Drupal\adsense;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AdsenseAdBase.
 */
abstract class AdsenseAdBase extends PluginBase implements AdsenseAdInterface, ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Creates a new AdsenseAdBase instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed|null $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface|null $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface|null $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountProxyInterface|null $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id = '', $plugin_definition = NULL, ConfigFactoryInterface $config_factory = NULL, ModuleHandlerInterface $module_handler = NULL, AccountProxyInterface $current_user = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory ?: \Drupal::configFactory();
    $this->moduleHandler = $module_handler ?: \Drupal::moduleHandler();
    $this->currentUser = $current_user ?: \Drupal::currentUser();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function createAd(array $args) {
    $is_search = (!empty($args['format']) && ($args['format'] == 'Search Box'));
    $needs_slot = !empty($args['slot']);

    // Search for the AdsenseAd plugins.
    /** @var AdsenseAdManager $manager */
    $manager = \Drupal::service('plugin.manager.adsensead');
    $plugins = $manager->getDefinitions();

    foreach ($plugins as $plugin) {
      if (($plugin['isSearch'] == $is_search) && ($plugin['needsSlot'] == $needs_slot)) {
        // Return an ad created by the compatible plugin.
        return $manager->createInstance($plugin['id'], $args);
      }
    }
    return NULL;
  }

  /**
   * Display ad HTML.
   *
   * @param array $classes
   *   Set of classes to add to the ad HTML.
   *
   * @return array
   *   render array with ad or placeholder depending on current configuration.
   */
  public function display(array $classes = []) {
    $config = $this->configFactory->get('adsense.settings');
    $libraries = ['adsense/adsense.css'];
    $text = '';

    if ($this->isDisabled($text)) {
      return [
        '#type' => 'inline_template',
        '#template' => '<!-- adsense: {{ comment }} -->',
        '#context' => [
          'comment' => $text,
        ],
      ];
    }
    elseif ($config->get('adsense_test_mode') || $this->currentUser->hasPermission('show adsense placeholders')) {
      // Show ad placeholder.
      $content = $this->getAdPlaceholder();
      return [
        '#theme' => 'adsense_ad',
        '#content' => $content['#content'],
        '#width' => isset($content['#width']) ? $content['#width'] : NULL,
        '#height' => isset($content['#height']) ? $content['#height'] : NULL,
        '#format' => $content['#format'],
        '#classes' => array_merge(['adsense-placeholder'], $classes),
        '#attached' => ['library' => $libraries],
      ];
    }
    else {
      // Display ad-block disabling request.
      if ($config->get('adsense_unblock_ads')) {
        $libraries[] = 'adsense/adsense.unblock';
      }
      $content = $this->getAdContent();

      // Show ad.
      return [
        '#theme' => 'adsense_ad',
        '#content' => $content,
        '#width' => isset($content['#width']) ? $content['#width'] : NULL,
        '#height' => isset($content['#height']) ? $content['#height'] : NULL,
        '#format' => $content['#format'],
        '#classes' => $classes,
        '#attached' => ['library' => $libraries],
      ];
    }
  }

  /**
   * Check if ads display is disabled.
   *
   * @param string $text
   *   Reason for the ad display being disabled.
   *
   * @return bool
   *   TRUE if ads are disabled.
   */
  public static function isDisabled(&$text = '') {
    $account = \Drupal::currentUser();
    $config = \Drupal::config('adsense.settings');

    if (!$config->get('adsense_basic_id')) {
      $text = 'no publisher id configured.';
    }
    elseif ($config->get('adsense_disable')) {
      $text = 'adsense disabled.';
    }
    elseif (($account->id() != 1) && ($account->hasPermission('hide adsense'))) {
      $text = 'disabled for current user.';
    }
    else {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * List of available languages.
   *
   * @return array
   *   array of language options with the key used by Google and description.
   */
  public static function adsenseLanguages() {
    return [
      'ar'    => t('Arabic'),
      'bg'    => t('Bulgarian'),
      'ca'    => t('Catalan'),
      'zh-Hans' => t('Chinese Simplified'),
      'zh-TW' => t('Chinese Traditional'),
      'hr'    => t('Croatian'),
      'cs'    => t('Czech'),
      'da'    => t('Danish'),
      'nl'    => t('Dutch'),
      'en'    => t('English'),
      'et'    => t('Estonian'),
      'fi'    => t('Finnish'),
      'fr'    => t('French'),
      'de'    => t('German'),
      'el'    => t('Greek'),
      'iw'    => t('Hebrew'),
      'hi'    => t('Hindi'),
      'hu'    => t('Hungarian'),
      'is'    => t('Icelandic'),
      'in'    => t('Indonesian'),
      'it'    => t('Italian'),
      'ja'    => t('Japanese'),
      'ko'    => t('Korean'),
      'lv'    => t('Latvian'),
      'lt'    => t('Lithuanian'),
      'no'    => t('Norwegian'),
      'pl'    => t('Polish'),
      'pt'    => t('Portuguese'),
      'ro'    => t('Romanian'),
      'ru'    => t('Russian'),
      'sr'    => t('Serbian'),
      'sk'    => t('Slovak'),
      'sl'    => t('Slovenian'),
      'es'    => t('Spanish'),
      'sv'    => t('Swedish'),
      'th'    => t('Thai'),
      'tr'    => t('Turkish'),
      'uk'    => t('Ukrainian'),
      'vi'    => t('Vietnamese'),
    ];
  }

}
