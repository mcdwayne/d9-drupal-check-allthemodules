<?php

namespace Drupal\cloudwords_config_translation\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Renderer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Return response for manual check translations.
 */
class CloudwordsConfigEntityTranslation extends ControllerBase {

  protected $renderer;
  public function __construct(Renderer $renderer) {
    $this->renderer = $renderer;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }
  /**
   * Shows the string search screen.
   *
   * @return array
   *   The render array for the string search screen.
   */
  public function inventoryOverview() {

    $view = views_embed_view('cloudwords_translatable', 'block_3');
    return [
      '#markup' => $this->renderer->render($view),
      '#attached' => [
        'library' =>  [
          'cloudwords/cloudwords.create_project',
        ],
        'drupalSettings' => [
          'cloudwords' => [
            'token' => \Drupal::csrfToken()->get('cloudwords'),
            'ajaxUrl' => 'admin/cloudwords/ajax'
          ]
        ],
      ],
    ];
  }

}
