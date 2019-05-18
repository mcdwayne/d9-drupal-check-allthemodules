<?php

namespace Drupal\automatic_updates\Services;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Class EmailNotify.
 */
class Notify implements NotifyInterface {
  use StringTranslationTrait;

  /**
   * Mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The automatic updates service.
   *
   * @var \Drupal\automatic_updates\Services\AutomaticUpdatesPsaInterface
   */
  protected $automaticUpdatesPsa;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * EmailNotify constructor.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\automatic_updates\Services\AutomaticUpdatesPsaInterface $automatic_updates_psa
   *   The automatic updates service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(MailManagerInterface $mail_manager, AutomaticUpdatesPsaInterface $automatic_updates_psa, ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager, StateInterface $state, TimeInterface $time, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->mailManager = $mail_manager;
    $this->automaticUpdatesPsa = $automatic_updates_psa;
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
    $this->state = $state;
    $this->time = $time;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function send() {
    $messages = $this->automaticUpdatesPsa->getPublicServiceMessages();
    if (!$messages) {
      return;
    }
    $notify_list = $this->configFactory->get('update.settings')->get('notification.emails');
    if (!empty($notify_list)) {
      $frequency = $this->configFactory->get('automatic_updates.settings')->get('check_frequency');
      $last_check = $this->state->get('automatic_updates.last_check') ?: 0;
      if (($this->time->getRequestTime() - $last_check) > $frequency) {
        $this->state->set('automatic_updates.last_check', $this->time->getRequestTime());

        $params['subject'] = new PluralTranslatableMarkup(
          count($messages),
          '@count urgent Drupal announcement requires your attention for @site_name',
          '@count urgent Drupal announcements require your attention for @site_name',
          ['@site_name' => $this->configFactory->get('system.site')->get('name')]
        );
        $params['body'] = [
          '#theme' => 'automatic_updates_psa_notify',
          '#messages' => $messages,
        ];
        $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
        $params['langcode'] = $default_langcode;
        foreach ($notify_list as $to) {
          $this->doSend($to, $params);
        }
      }
    }
  }

  /**
   * Composes and send the email message.
   *
   * @param string $to
   *   The email address where the message will be sent.
   * @param array $params
   *   Parameters to build the email.
   */
  protected function doSend($to, array $params) {
    $users = $this->entityTypeManager->getStorage('user')
      ->loadByProperties(['mail' => $to]);
    if ($users) {
      $to_user = reset($users);
      $params['langcode'] = $to_user->getPreferredLangcode();
    }
    $this->mailManager->mail('automatic_updates', 'notify', $to, $params['langcode'], $params);
  }

}
