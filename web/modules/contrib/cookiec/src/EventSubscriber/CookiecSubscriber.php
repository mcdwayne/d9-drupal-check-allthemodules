<?php

namespace Drupal\cookiec\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Class PopupMessageSubscriber
 *
 * @package Drupal\popup_message\EventSubscriber
 */
class CookiecSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The cookiec config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;


  /**
   * CookiecSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   */
  public function __construct(ConfigFactoryInterface $config, LanguageManagerInterface $languageManager) {
    $this->config = $config->get('cookiec.settings');
    $this->languageManager = $languageManager;
  }

  /**
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   */
  public function showPopupMessage(FilterResponseEvent $event) {

    // Check permissions to display message.
    $response = $event->getResponse();

    if (!$response instanceof AttachmentsInterface) {
      return;
    }
    // Check module has enable popup
    $config = $this->config;
    $language = $this->languageManager->getCurrentLanguage()->getId();

    $variables =  array(
      'message' => $config->get($language."_popup_info"),
    );

    $twig = \Drupal::service('twig');
    $template = $twig->loadTemplate(drupal_get_path('module', 'cookiec') . '/templates/cookiec_info.html.twig');
    $html_info = $template->render($variables);


    $variables =  array(
      'message' => $config->get($language."_popup_info"),
      'more' => 'more',
      'hide' => 'hide',
    );
    $twig = \Drupal::service('twig');
    $template = $twig->loadTemplate(drupal_get_path('module', 'cookiec') . '/templates/cookiec_agreed.html.twig');
    $html_agreed = $template->render($variables);

    $variables = array(
      'popup_enabled' => $config->get('popup_enabled'),
      'popup_agreed_enabled' => $config->get('popup_agreed_enabled'),
      'popup_hide_agreed' => $config->get('popup_hide_agreed'),
      'popup_height' => $config->get('popup_height'),
      'popup_width' => $config->get('popup_width'),
      'popup_delay' => $config->get('popup_delay')*1000,
      'popup_link' => $config->get($language."_link"),
      'popup_position' => $config->get('popup_position'),
      'popup_language' => $language,
      'popup_html_info' => $html_info,
      'popup_html_agreed' =>$html_agreed,
    );

    $attachments = $response->getAttachments();
    $attachments['library'][] = 'cookiec/cookiec_library';
    $attachments['drupalSettings']['cookiec'] = $variables;
    $response->setAttachments($attachments);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('showPopupMessage', 20);

    return $events;
  }
}
