<?php

namespace Drupal\hover_card\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default controller for the hover_card module.
 */
class DefaultController extends ControllerBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a DefaultController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
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
   * {@inheritdoc}
   */
  public function hoverCard(UserInterface $user = NULL) {
    $name = $mail = $roles = $picture = "";
    $name = $user->getAccountName();

    if ($user->getEmail() && $this->config('hover_card.settings')->get('email_display_status_value')) {
      $mail = $user->getEmail();
    }

    $uid = $user->id();
    if ($uid) {
      if (!$user->user_picture->isEmpty()) {
        $picture = $user->user_picture->view('thumbnail');
      }
    }

    foreach ($user->getRoles() as $value) {
      $roles = $value;
    }

    $user_data = [
      'name' => SafeMarkup::checkPlain($name),
      'mail' => SafeMarkup::checkPlain($mail),
      'picture' => $picture,
      'roles' => SafeMarkup::checkPlain($roles),
    ];

    $hover_card_template_build = [
      '#theme' => 'hover_card_template',
      '#details' => $user_data,
    ];

    $hover_card_template = $this->renderer->render($hover_card_template_build);

    $response = new Response();
    $response->setContent($hover_card_template);
    return $response;
  }

}
