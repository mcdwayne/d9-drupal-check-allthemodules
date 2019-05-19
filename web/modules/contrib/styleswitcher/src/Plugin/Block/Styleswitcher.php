<?php

namespace Drupal\styleswitcher\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Styleswitcher block.
 *
 * @Block(
 *   id = "styleswitcher_styleswitcher",
 *   admin_label = @Translation("Style Switcher")
 * )
 */
class Styleswitcher extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The Redirect Destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new Styleswitcher.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The Redirect Destination service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ThemeManagerInterface $theme_manager, RedirectDestinationInterface $redirect_destination, ConfigFactoryInterface $config_factory) {
    $this->themeManager = $theme_manager;
    $this->redirectDestination = $redirect_destination;
    $this->configFactory = $config_factory;

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('theme.manager'),
      $container->get('redirect.destination'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [];
    $theme = $this->themeManager->getActiveTheme()->getName();

    // List of styles a user can switch between.
    $styles = styleswitcher_style_load_multiple($theme, ['status' => TRUE]);

    // Do not display block if there is only one style (no alternatives).
    if (count($styles) > 1) {
      uasort($styles, 'styleswitcher_sort');
      $links = [];
      $destination = $this->redirectDestination->getAsArray();

      foreach ($styles as $name => $style) {
        $name_hyphenated = strtr($name, '_', '-');
        $name_parts = explode('/', $name_hyphenated);
        $class = [
          'style-switcher',
          $name_parts[0] . '-style',
          'style-' . $name_parts[1],
        ];

        $route_params = [
          'theme' => $theme,
          'type' => $name_parts[0],
          'style' => $name_parts[1],
        ];
        $options = [
          'query' => $destination,
          'attributes' => [
            'class' => $class,
            'data-rel' => $name,
            'rel' => 'nofollow',
          ],
        ];
        $links[] = Link::fromTextAndUrl($style['label'], Url::fromRoute('styleswitcher.switch', $route_params, $options));

        // Make paths absolute for JS.
        if (isset($style['path'])) {
          $styles[$name]['path'] = file_create_url($style['path']);
        }
        else {
          $styles[$name]['path'] = Url::fromRoute('styleswitcher.css', ['theme' => $theme], ['absolute' => TRUE])->toString();
        }
      }

      $js_settings = [
        'styleSwitcher' => [
          'styles' => $styles,
          'default' => styleswitcher_default_style_key($theme),
          'enableOverlay' => $this->configFactory
            ->get('styleswitcher.settings')
            ->get('enable_overlay'),
          'cookieExpire' => STYLESWITCHER_COOKIE_EXPIRE,
          'theme' => $theme,
        ],
      ];

      $attached['library'][] = 'styleswitcher/styleswitcher';
      $attached['drupalSettings'] = $js_settings;

      $block = [
        '#theme' => 'item_list',
        '#items' => $links,
        '#attached' => $attached,
        '#cache' => [
          // We cannot cache globally, because we use drupal_get_destination()
          // with links in block, which is different from page to page. And we
          // cannot avoid using destination, because in this case site users
          // with JS-disabled browsers won't go back to the same page they were
          // at, but will go to the front page each time. We also cannot rely on
          // $_SERVER['HTTP_REFERER'], because it can be empty.
          'contexts' => ['theme', 'url'],
          'tags' => [
            'config:styleswitcher.settings',
            'config:styleswitcher.custom_styles',
            'config:styleswitcher.styles_settings',
          ],
        ],
      ];
    }
    else {
      $block['#cache'] = [
        'contexts' => ['theme'],
        'tags' => [
          'config:styleswitcher.custom_styles',
          'config:styleswitcher.styles_settings',
        ],
      ];
    }

    return $block;
  }

}
