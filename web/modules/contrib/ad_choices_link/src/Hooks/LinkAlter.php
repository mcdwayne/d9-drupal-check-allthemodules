<?php

namespace Drupal\ad_choices_link\Hooks;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * AdChoicesLinkPreprocessMenu service.
 */
class LinkAlter {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * AdChoices Link Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a ad_choices_linkpreprocessmenu object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('ad_choices_link.settings');
  }

  /**
   * Add library and image to AdChoices link.
   *
   * @param array $variables
   *   The render array.
   */
  public function alter(array &$variables) {
    $menu = $this->config->get('menu');
    if ($variables['menu_name'] != $menu) {
      return;
    }

    if (isset($variables['items'][$menu . '.ad_choices'])) {
      $variables['#attached']['library'][] = 'ad_choices_link/ad_choices_link';
      $variables['#attached']['drupalSettings']['adChoicesLink'] = [
        'pid' => $this->config->get('pid'),
        'ocid' => $this->config->get('ocid'),
        'jsPath' => base_path() . drupal_get_path('module', 'ad_choices_link') . '/js',
      ];
      $title = '<img id="_bapw-icon" alt="Ad Choices Icon" /><span>AdChoices</span>';
      $variables['items'][$menu . '.ad_choices']['title'] = new FormattableMarkup($title, []);
    }
  }

}
