<?php

namespace Drupal\vex_message\EventSubscriber;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class VexMessageSubscriber.
 *
 * @package Drupal\vex_message\EventSubscriber
 */
class VexMessageSubscriber implements EventSubscriberInterface {

  /**
   * The vexMessage config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Current Request.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $requestStack;

  /**
   * Path matcher services.
   *
   * @var \Drupal\Core\Path\PathMatcher
   */
  protected $pathMatcher;

  /**
   * User account service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * VexMessageSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The vexMessage config.
   * @param null|\Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Current Request.
   * @param \Drupal\Core\Path\PathMatcher $pathMatcher
   *   Path matcher services.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ConfigFactoryInterface $config, RequestStack $requestStack, PathMatcher $pathMatcher, AccountInterface $account, ModuleHandlerInterface $moduleHandler, RendererInterface $renderer) {
    $this->config = $config->get('vex_message.settings');
    $this->request_stack = $requestStack->getCurrentRequest();
    $this->path_matcher = $pathMatcher;
    $this->account = $account;
    $this->moduleHandler = $moduleHandler;
    $this->renderer = $renderer;
  }

  /**
   * Init Vex Message.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   vexMessage event.
   */
  public function showVexMessage(FilterResponseEvent $event) {

    // Check permissions to display message.
    $response = $event->getResponse();

    if (!$response instanceof AttachmentsInterface) {
      return;
    }

    // @todo: add option to enable/disable vex message for existing themes.
    $current_url = $this->request_stack->getRequestUri();
    $system_path = $this->path_matcher->matchPath($current_url, '/admin/*');
    $batch_path = $this->path_matcher->matchPath($current_url, '/batch*');

    if ($this->config->get('status') && !$system_path && !$batch_path) {
      $message_title = Xss::filter($this->config->get('title'));
      $message_body_variable = $this->config->get('body');
      $message_body = check_markup(
        $message_body_variable['value'],
        ($message_body_variable['format'] ? $message_body_variable['format'] : filter_default_format()),
        FALSE
      );

      $content = [];
      $content['wrapper'] = [
        '#type' => 'container',
      ];

      if ($message_title) {
        $content['wrapper']['title'] = [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $message_title,
          '#attributes' => [
            'class' => ['title'],
          ],
        ];
      }
      if (!empty($message_body)) {
        $content['wrapper']['description'] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $message_body,
          '#attributes' => [
            'class' => ['description'],
          ],
        ];
      }

      if ($message_title && $message_body) {
        $attachments = $response->getAttachments();
        $attachments['library'][] = 'vex_message/vex_message_scripts';
        $attachments['library'][] = 'vex_message/vexjs';

        $attachments['drupalSettings']['vexMessage'] = [
          'content' => $this->renderer->renderRoot($content),
          'theme' => $this->config->get('theme'),
          'buttons' => FALSE,
          'close' => TRUE,
          'cookie' => $this->config->get('cookie'),
        ];

        $response->setAttachments($attachments);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['showVexMessage', 20];

    return $events;
  }

}
