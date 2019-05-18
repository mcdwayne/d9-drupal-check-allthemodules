<?php

namespace Drupal\hidden_tab\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\hidden_tab\Entity\HiddenTabMailer;
use Drupal\hidden_tab\Entity\HiddenTabMailerInterface;
use Drupal\hidden_tab\Event\HiddenTabPageFormEvent;
use Drupal\hidden_tab\FUtility;
use Drupal\hidden_tab\Plugable\MailDiscovery\HiddenTabMailDiscoveryPluginManager;
use Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager;
use Drupal\hidden_tab\Plugable\TplContext\HiddenTabTplContextPluginManager;
use Drupal\hidden_tab\Service\MailerSender;

/**
 * To list all mailer entities of a page, on it's edit form.
 *
 *
 *
 * hidden_tab.add_new_mailer_form_subscriber:
 * class:
 * Drupal\hidden_tab\EventSubscriber\HiddenTabPageFormSubscriberForNewMailer
 * arguments:
 * - '#string_translation'
 * - '#messenger'
 * - '#entity_type.manager'
 * - '#hidden_tab.mail_service'
 * tags:
 * - { name: 'event_subscriber' }
 */
class HiddenTabPageFormSubscriberForNewMailer extends ForNewEntityFormBase {

  /**
   * {@inherit}
   */
  protected $prefix = 'hidden_tab_add_new_mailer_subscriber_0__';

  /**
   * {@inherit}
   */
  protected $currentlyTargetEntity = 'node';

  /**
   * {@inherit}
   */
  protected $e_type = 'hidden_tab_mailer';

  /**
   * {@inherit}
   */
  protected $label;

  /**
   * To find the editing entity's entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mailerStorage;

  /**
   * To query for existing entities on validate.
   *
   * @var \Drupal\hidden_tab\Service\MailerSender
   */
  protected $mailService;

  /**
   * {@inheritdoc}
   */
  public function __construct(TranslationInterface $t,
                              MessengerInterface $messenger,
                              EntityTypeManagerInterface $entity_type_manager,
                              MailerSender $mailer_sender) {
    parent::__construct($t, $messenger, $entity_type_manager);
    $this->mailerStorage = $entity_type_manager->getStorage('hidden_tab_mailer');
    $this->mailService = $mailer_sender;
    $this->label = t('Mailer');
  }

  /**
   * {@inheritdoc}
   */
  protected function addForm(HiddenTabPageFormEvent $event): array {
    // Moved here from HiddenTabMailer
    return static::littleForm(
      $event->formState, $this->prefix, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function onValidate0(HiddenTabPageFormEvent $event) {
    // Moved here from HiddenTabMailer
    static::validateForm($event->formState,
      $this->prefix,
      'node',
      TRUE,
      NULL);
  }

  public static function validateForm(FormStateInterface $form_state,
                                      string $prefix,
                                      string $target_entity_type,
                                      bool $validate_targets,
                                      ?string $current_editing_entity_id_if_any): bool {
    if (!$validate_targets) {
      // Nothing to do, for now.
      return TRUE;
    }
    return FUtility::entityCreationValidateTargets($form_state,
      $prefix,
      $target_entity_type,
      $current_editing_entity_id_if_any,
      function (): array {
        return [];
      });
  }


  /**
   * {@inheritdoc}
   */
  protected function onSave0(HiddenTabPageFormEvent $event): array {
    // Moved here from HiddenTabMailer
    return static::extractFormValues($event->formState, $this->prefix, TRUE);
  }

  /**
   * Extract values of a submitted form for credit creation.
   *
   * @param string $prefix
   *   Namespace prefix of form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Submitted form.
   * @param bool $extract_refs
   *   Extract refrencer fields or not.
   *
   * @return array
   *   Extracted values, or sane defaults.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public static function extractFormValues(FormStateInterface $form_state,
                                           string $prefix,
                                           bool $extract_refs): array {
    /** @var \Drupal\hidden_tab\Plugable\MailDiscovery\HiddenTabMailDiscoveryInterface $plugin */

    $schedule = $form_state->getValue($prefix . 'email_schedule');
    if ($schedule === '0') {
      $schedule = 0;
    }
    if ($schedule !== 0 && !$schedule) {
      $schedule = HiddenTabMailerInterface::EMAIL_SCHEDULE_DEFAULT_PERIOD;
    }
    $v = [
      'email_schedule' => $schedule,
      'email_schedule_granularity' => $form_state->getValue('email_schedule_granularity')
        ?: HiddenTabMailerInterface::EMAIL_SCHEDULE_DEFAULT_GRANULARITY,
    ];

    $mail_svc = HiddenTabMailDiscoveryPluginManager::instance();

    $plugin_ = $form_state->getValue($prefix . 'plugin');
    if ($plugin_) {
      $plugin = $mail_svc->plugin($plugin_);
      $f = NULL;
      $config = $plugin->handleConfigFormSubmit($f, $form_state);
      $v += [
        'plugins' => json_encode([
          $plugin->id() => $config,
        ]),
      ];
    }
    else {
      $v += [
        'plugins' => json_encode([

        ]),
      ];
    }
    if ($extract_refs) {
      $v += FUtility::extractRefrencerValues($form_state, $prefix);
    }

    $v += FUtility::extractDefaultEntityValues($form_state, $prefix);
    if ($extract_refs) {
      $v += FUtility::extractRefrencerValues($form_state, $prefix);
    }

    return $v;
  }

  /**
   * Small amount of elements to create a new entity.
   *
   * Those values who usually have sane defaults are omitted. So is the
   * target page as it is usually known.
   *
   * @param \Drupal\Core\Form\FormStateInterface|null $form_state
   *   Submitted form, if any.
   * @param string $prefix
   *   The namespace to prefix form elements with.
   * @param bool $add_targets
   *   Add refrencer fields or not.
   *
   * @return array
   *   The form elements.
   */
  public static function littleForm(?FormStateInterface $form_state,
                                    string $prefix = '',
                                    bool $add_targets = TRUE): array {
    /** @var \Drupal\hidden_tab\Plugable\MailDiscovery\HiddenTabMailDiscoveryInterface $plugin */

    $tpl_svc = HiddenTabTemplatePluginManager::instance();
    $disc_svc = HiddenTabMailDiscoveryPluginManager::instance();
    $x_scv = HiddenTabTplContextPluginManager::instance();

    $form[$prefix . $disc_svc->id() . '_plugin'] = [
      '#type' => 'select',
      '#title' => t('Mail Discovery Plugin'),
      '#description' => t('The plugin used to find the email address.'),
      '#options' => $disc_svc->pluginsForSelectElement(NULL, TRUE),
      '#default_value' => NULL,
    ];
    foreach ($disc_svc->plugins() as $plugin) {
      $plugin->handleConfigForm($form, $form_state, $prefix . 'plugin', NULL);
    }

    $form[$prefix . $x_scv->id() . '_plugin'] = [
      '#type' => 'select',
      '#multivalue' => TRUE,
      '#title' => t('Template Context Provider Plugin'),
      '#options' => $x_scv->pluginsForSelectElement(NULL, TRUE),
      '#default_value' => NULL,
    ];
    foreach ($disc_svc->plugins() as $plugin) {
      $plugin->handleConfigForm($form, $form_state, $prefix . 'plugin', NULL);
    }

    $form[$prefix . 'email_template'] = [
      '#type' => 'textarea',
      '#title' => t('Email template'),
      '#description' => t('Twig template used to render email body. Available variables: email, mailer, page, entity, subject'),
      '#options' => $tpl_svc->pluginsForSelectElement('email', TRUE),
    ];
    foreach ($form[$prefix . 'email_template']['#options'] as $key => $label) {
      $form[$prefix . 'email_template']['#default_value'] = $key;
      break;
    }

    $form[$prefix . 'email_title_template'] = [
      '#type' => 'textarea',
      '#title' => t('Email title template'),
      '#description' => t('Twig template used to render email title. Available variables: email, mailer, page, entity.'),
      '#options' => $tpl_svc->pluginsForSelectElement('email', TRUE),
    ];
    foreach ($form[$prefix . 'email_title_template']['#options'] as $key => $label) {
      $form[$prefix . 'email_title_template']['#default_value'] = $key;
      break;
    }

    $form[$prefix . 'email_schedule'] = [
      '#type' => 'number',
      '#title' => t('Email schedule'),
      '#description' => t('Send every...? Zero disables.'),
      '#default_value' => HiddenTabMailerInterface::EMAIL_SCHEDULE_DEFAULT_PERIOD,
      '#min' => 0,
    ];
    $form[$prefix . 'email_schedule_granul'] = [
      '#type' => 'select',
      '#title' => t('Email schedule granularity'),
      '#default_value' => HiddenTabMailerInterface::EMAIL_SCHEDULE_DEFAULT_GRANULARITY,
      '#options' => [
        'second' => t('Seconds'),
        'minute' => t('minutes'),
        'hour' => t('Hours'),
        'day' => t('days'),
        'month' => t('months'),
        'year' => t('Years'),
        'week' => t('Weeks'),
      ],
    ];
    $form[$prefix . 'next_schedule'] = [
      '#type' => 'timestamp',
      '#title' => t('Upcoming'),
      '#default_value' => -1,
      '#description' => t('The next date email will be sent on.'),
    ];

    if ($add_targets) {
      return $form + FUtility::refrencerEntityFormElements($prefix);
    }
    else {
      return $form;
    }

    static::pluginsForm($form, $form_state, $entity);
  }


  public static function pluginsForm(array &$form,
                                     FormStateInterface $form_state,
                                     ?HiddenTabMailerInterface $entity) {

    $disc_man = HiddenTabMailDiscoveryPluginManager::instance();
    $tpl_man = HiddenTabTplContextPluginManager::instance();

    $d_pid = HiddenTabMailDiscoveryInterface::PID;
    $form[$d_pid] = [
      '#type' => 'fieldset',
      '#title' => t('Email Discovery'),
    ];
    $form[$d_pid]['_active' . $d_pid] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => t('Discovery plugin'),
      '#options' => $disc_man->pluginsForSelectElement(NULL, TRUE),
      '#default_value' => $entity ? $entity->pluginConfiguration('_active', $d_pid) : [],
      '#description' => 'TODO: make orderable.',
    ];
    foreach ($disc_man->plugins() as $discovery_plugin) {
      $discovery_plugin->handleConfigForm($form, $form_state, $d_pid,
        $entity ? $entity->pluginConfiguration($d_pid, $discovery_plugin->id()) : NULL
      );
    }

    $x_pid = HiddenTabTplContextInterface::PID;
    $form[$x_pid] = [
      '#type' => 'fieldset',
      '#title' => t('Template Context Provider'),
    ];
    $form[$x_pid]['_active' . $x_pid] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => t('Template Context Provider Plugin'),
      '#options' => $tpl_man->pluginsForSelectElement(NULL, TRUE),
      '#default_value' => $entity ? $entity->pluginConfiguration('_active', $x_pid) : [],
    ];
    foreach ($tpl_man->plugins() as $context_plugin) {
      $context_plugin->handleConfigForm($form, $form_state, $x_pid,
        $entity ? $entity->pluginConfiguration($x_pid, $context_plugin->id()) : NULL
      );
    }

  }

}
