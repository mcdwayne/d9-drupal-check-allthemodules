<?php

namespace Drupal\cloudwords\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Renderer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CloudwordsController.
 *
 * @package Drupal\cloudwords\Controller
 */
class CloudwordsController extends ControllerBase {

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
   * Overviewpage.
   *
   * @return string
   *   Return Hello string.
   */
  public function overviewPage() {
    $view = views_embed_view('cloudwords_translatable', 'block_1');
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

  /**
   * Overviewpage.
   *
   * @return string
   *   Return Hello string.
   */
  public function projectsActivePage() {
    // @todo paginate open projects
    $view = views_embed_view('cloudwords_projects', 'block_1');
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

    //return $form;
  }

  /**
   * Overviewpage.
   *
   * @return string
   *   Return Hello string.
   */
  public function projectsClosedPage() {
    $view = views_embed_view('cloudwords_projects', 'block_2');
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
  /**
   * ajax response.
   *
   * @return string
   */
  public function ajax() {
    $id = \Drupal::request()->request->get('id');
    $action = \Drupal::request()->request->get('action');
    $uid = \Drupal::currentUser()->id();
    if ($action == 'remove') {
      cloudwords_project_user_remove($uid, [$id]);
    } else {
      cloudwords_project_user_add($uid, [$id]);
    }

    $count = cloudwords_project_user_count($uid);
    $text = \Drupal::translation()->formatPlural($count, '1 asset in project', '@count assets in project.', ['@count' => $count]);

    $response = new Response();
    $response->setContent(json_encode(['id' => $id, 'text'=>$text]));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

}
