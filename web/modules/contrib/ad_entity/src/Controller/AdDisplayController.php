<?php

namespace Drupal\ad_entity\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\ad_entity\Entity\AdDisplayInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for viewing display configs of Advertisement.
 */
class AdDisplayController implements ContainerInjectionInterface {

  /**
   * The view mode to use.
   *
   * @var string
   */
  static protected $viewMode = 'default';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The system default theme name.
   *
   * @var string
   */
  protected $defaultThemeName;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The theme initialization logic provider.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * AdDisplayController constructor.
   *
   * @param string $default_theme
   *   The system default theme name.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $theme_initialization
   *   The theme initialization logic provider.
   */
  public function __construct($default_theme, EntityTypeManagerInterface $entity_type_manager, ThemeHandlerInterface $theme_handler, ThemeManagerInterface $theme_manager, ThemeInitializationInterface $theme_initialization) {
    $this->defaultThemeName = $default_theme;
    $this->entityTypeManager = $entity_type_manager;
    $this->themeHandler = $theme_handler;
    $this->themeManager = $theme_manager;
    $this->themeInitialization = $theme_initialization;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');
    $default_theme = $config_factory->get('system.theme') ?
      $config_factory->get('system.theme')->get('default') : 'stable';
    return new static(
      $default_theme,
      $container->get('entity_type.manager'),
      $container->get('theme_handler'),
      $container->get('theme.manager'),
      $container->get('theme.initialization')
    );
  }

  /**
   * Controller callback for displaying Advertisement.
   *
   * @param \Drupal\ad_entity\Entity\AdDisplayInterface $ad_display
   *   The display config.
   *
   * @return array
   *   The render array for viewing the advertisement.
   */
  public function view(AdDisplayInterface $ad_display) {
    $this->switchThemeFor($ad_display);

    $entity_type_id = $ad_display->getEntityTypeId();
    $view_builder = $this->entityTypeManager->getViewBuilder($entity_type_id);
    $page = $view_builder->view($ad_display, static::$viewMode);
    $page['#entity_type'] = $entity_type_id;
    $page['#' . $entity_type_id] = $ad_display;
    $page['#attached']['html_head'][] = [
      [
        '#attributes' => [
          'name' => 'robots',
          'content' => 'noindex,follow',
        ],
        '#tag' => 'meta',
      ],
      'robots',
    ];

    return $page;
  }

  /**
   * Switches the currently active theme for the given display.
   *
   * @param \Drupal\ad_entity\Entity\AdDisplayInterface $ad_display
   *   The display config to switch the theme for.
   */
  protected function switchThemeFor(AdDisplayInterface $ad_display) {
    $switch_theme_name = !empty($ad_display->get('theme_canonical')) ? $ad_display->get('theme_canonical') : $this->defaultThemeName;
    if ($this->themeHandler->themeExists($switch_theme_name)) {
      $already_active = FALSE;
      if ($active = $this->themeManager->getActiveTheme()) {
        if ($active->getName() === $switch_theme_name) {
          $already_active = TRUE;
        }
      }
      if (!$already_active) {
        $theme = $this->themeInitialization->initTheme($switch_theme_name);
        $this->themeManager->setActiveTheme($theme);
      }
    }
  }

}
