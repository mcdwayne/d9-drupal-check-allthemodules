<?php

namespace Drupal\extlink_extra\Controller;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ExtlinkExtraController extends ControllerBase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, Token $token, RequestStack $request_stack, RendererInterface $renderer) {
    $this->configFactory = $config_factory;
    $this->token = $token;
    $this->requestStack = $request_stack;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('token'),
      $container->get('request_stack'),
      $container->get('renderer')
    );
  }

  /**
   * Title callback.
   *
   * @return string
   */
  public function getTitle() {
    // Get configuration.
    $config = $this->configFactory->get('extlink_extra.settings');

    // Prepare token replacement values.
    $extlink_token_data = [
      'extlink' => [
        'external_url' => isset($_COOKIE['external_url']) ? $_COOKIE['external_url'] : NULL,
        'back_url' => isset($_COOKIE['back_url']) ? $_COOKIE['back_url'] : NULL,
      ],
    ];

    // Fetch the page title and return it after replacing the tokens.
    $page_title = $config->get('extlink_page_title') ?: NULL;
    return $this->token->replace($page_title, $extlink_token_data);
  }

  /**
   * Render the markup for the exit modal.
   *
   * @see extlink_extra_preprocess_extlink_extra_leaving()
   *
   * @return array
   */
  public function leave() {
    // Just return the template. Variables will be inserted
    // in extlink_extra_preprocess_extlink_extra_leaving().
    $output = [
      '#theme' => 'extlink_extra_leaving',
    ];

    // If we are using Colorbox we will add colorbox=1 to the query string of the
    // alert page. This causes a premature exit which saves execution time and
    // does not render the rest of the page.
    if ($this->requestStack->getCurrentRequest()->request->get('colorbox')) {
      $html = $this->renderer->render($output);
      return new HtmlResponse($html);
    }

    return $output;
  }
}
