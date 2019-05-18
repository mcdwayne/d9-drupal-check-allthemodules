<?php

namespace Drupal\mail\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\mail\MailMessageProcessorManager;

/**
 * Class MailMessageForm.
 *
 * @package Drupal\mail\Form
 */
class MailMessageForm extends EntityForm {

  /**
   * The plugin manager mail message processor service.
   *
   * @var \Drupal\mail\MailMessageProcessorManager
   */
  protected $pluginManagerMailProcessor;

  /**
   * Creates a MailMessageForm instance.
   *
   * @param \Drupal\mail\MailMessageProcessorManager $plugin_manager_mail_message_processor
   *   The plugin manager mail message processor service.
   */
  public function __construct(MailMessageProcessorManager $plugin_manager_mail_message_processor) {
    $this->pluginManagerMailProcessor = $plugin_manager_mail_message_processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail_message_processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $mail_message = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $mail_message->label(),
      '#description' => $this->t("The administrative label for the mail message. This is not seen in the email."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $mail_message->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\mail\Entity\MailMessage::load',
      ),
      '#disabled' => !$mail_message->isNew(),
    );

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#default_value' => $mail_message->get('subject'),
      '#description' => $this->t('The subject for the email.'),
      '#required' => TRUE,
    ];

    $form['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email body'),
      '#default_value' => $mail_message->get('body'),
      '#description' => $this->t('The body text for the email.'),
      '#required' => TRUE,
    ];

    // Allow the processor plugin to provide help, for example, list tokens.
    $processor_plugin_id = $mail_message->getMailProcessorPluginID();
    if (!empty($processor_plugin_id)) {
      $processor_plugin = $this->pluginManagerMailProcessor->createInstance($processor_plugin_id);
      $processor_help = $processor_plugin->getHelp();
    }
    else {
      $processor_help = [];
    }

    $form['processor_help'] = $processor_help;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $mail_message = $this->entity;
    $status = $mail_message->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label mail message.', [
          '%label' => $mail_message->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label mail message.', [
          '%label' => $mail_message->label(),
        ]));
    }
    $form_state->setRedirectUrl($mail_message->urlInfo('collection'));
  }

}
