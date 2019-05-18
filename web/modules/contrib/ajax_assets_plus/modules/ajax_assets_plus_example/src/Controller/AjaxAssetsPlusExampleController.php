<?php

namespace Drupal\ajax_assets_plus_example\Controller;

use Drupal\ajax_assets_plus\Ajax\AjaxAssetsPlusResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller providing the resource for the form.
 */
class AjaxAssetsPlusExampleController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a controller object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Returns current date in a json response.
   */
  public function getDatePage() {
    $html = <<<HTML
<div class="ajax-assets-plus-example-date">
  <a href="#" class="ajax-assets-plus-example-date__link">Get date</a>
</div>
HTML;
    $content = [
      [
        '#markup' => $html,
      ],
      '#attached' => [
        'library' => [
          'ajax_assets_plus_example/date',
        ],
      ],
    ];

    return $content;
  }

  /**
   * Returns current date in a json response.
   */
  public function getDate() {
    $content = [
      [
        '#prefix' => '<div class="ajax-assets-plus-example-date__date">',
        '#markup' => 'Current date: ' . date('Y-m-d'),
        '#suffix' => '</div>',
      ],
      '#attached' => [
        'library' => [
          'ajax_assets_plus_example/time',
        ],
      ],
    ];

    $response = new AjaxAssetsPlusResponse();
    $response->setContent($content);

    return $response;
  }

}
