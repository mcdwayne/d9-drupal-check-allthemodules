<?php
/**
 * @file
 * Contains \Drupal\nativo\Controller\AdPageController.
 */
namespace Drupal\nativo\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Returns Nativo HTML as ad's content.
 */
class AdPageController implements ContainerInjectionInterface{

  /**
   * {inheritDoc}.
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Builds page content for ad page.
   *
   * Consists of HTML for ad, robots meta tag, and optional CSS file.
   *
   * @return array
   *   Render-able array for content.
   */
  public function buildContent() {
    $config = \Drupal::config('nativo.settings');
    $content = array(
      '#markup' => $config->get('html'),
    );
    // Prevent robots from crawling the index page.
    $content['#attached']['drupal_add_html_head'] = array(
      array(
        array(
          '#tag' => 'meta',
          '#attributes' => array('name' => 'robots', 'content' => 'noindex, nofollow')
        ),
        'nativo'
      )
    );

    // Allow themes to put nativo.css in their folder to automatically be
    // included.
    $theme_name = \Drupal::theme()->getActiveTheme()->getName();
    $styles = drupal_get_path('theme', $theme_name) . '/nativo.css';
    if (is_file($styles)) {
      $content['#attached']['css'][$styles] = array('every_page' => TRUE);
    }

    return $content;
  }
}
