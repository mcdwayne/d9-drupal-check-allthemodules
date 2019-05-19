<?php

namespace Drupal\stickynav\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for stickynav module routes.
 */
class StickynavController extends ControllerBase {
  protected $theme_handler;

  /**
   * Constructs a \Drupal\stickynav\Controller\StickynavController object.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   * The theme handler.
   */
  public function __construct(ThemeHandlerInterface $theme_handler) {
    $this->theme_handler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler')
    );
  }

  /**
   * Lists links to configuration for stickynav per theme.
   *
   * @return string
   *   Table of all enabled themes where you can set the stickynav settings.
   */
  public function listThemes() {
    $build = [];
    $themes = $this->theme_handler->listInfo();
    $rows = [];
    foreach ($themes as $name => $theme) {
      $row = [$theme->info['name']];
      $links['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('stickynav.set_theme', ['theme' => $name]),
      ];
      $row[] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];
      $rows[] = $row;
    }
    $header = array(
      $this->t('Theme'),
      $this->t('Action'),
    );

    $build['stickynav_themes'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    );

    return $build;
  }

}
