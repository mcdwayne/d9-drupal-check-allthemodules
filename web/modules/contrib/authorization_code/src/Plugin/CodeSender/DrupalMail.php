<?php

namespace Drupal\authorization_code\Plugin\CodeSender;

use Drupal\authorization_code\Exceptions\FailedToSendCodeException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sends codes to users via the drupal mail manager.
 *
 * @CodeSender(
 *   id = "drupal_mail",
 *   title = @Translation("Drupal Mail")
 * )
 */
class DrupalMail extends CodeSenderBase implements ContainerFactoryPluginInterface {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  private $mailManager;

  /**
   * DrupalMail constructor.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   */
  public function __construct(MailManagerInterface $mail_manager, array $configuration, string $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('plugin.manager.mail'),
      $configuration, $plugin_id, $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return NestedArray::mergeDeep(parent::defaultConfiguration(), [
      'settings' => [
        'subject_template' => '',
      ],
    ]);
  }

  /**
   * The subject template.
   *
   * @return string|null
   *   The subject template, or null if no template was configured.
   */
  protected function subjectTemplate() {
    return NestedArray::getValue($this->configuration,
      ['settings', 'subject_template']);
  }

  /**
   * {@inheritdoc}
   */
  public function sendCode(UserInterface $user, string $code) {
    if (!empty($user->getEmail())) {
      try {
        $this->mailManager->mail(
          'system',
          'authorization_code',
          $user->getEmail(),
          $user->getPreferredLangcode(),
          [
            'context' => [
              'subject' => $this->subjectTemplate(),
              'message' => $this->messageTemplate(),
              'user' => $user,
              'authorization_code' => $code,
            ],
          ]);
      }
      catch (\Exception $e) {
        throw new FailedToSendCodeException($user, $e);
      }
    }
    else {
      throw new FailedToSendCodeException($user);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['subject_template'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Subject template'),
      '#default_value' => $this->subjectTemplate(),
      '#description' => $this->t('The template for the email subject line (use [authorization_code:code] for the authorization code)'),
      '#weight' => -50,
    ];

    return $form;
  }

}
