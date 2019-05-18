<?php

/**
 * @file
 * Contains \Drupal\cas\Form\CasServerSettings.
 */

namespace Drupal\cas_server\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CasServerSettings.
 *
 * @codeCoverageIgnore
 */
class CasServerSettings extends ConfigFormBase {

  /**
   * Constructs a \Drupal\cas_server\Form\CasServerSettings object.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cas_server_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cas_server.settings');

    $form['ticket'] = array(
      '#type' => 'details',
      '#title' => $this->t('Ticket settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    );
    $form['ticket']['service'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Service ticket timeout'),
      '#description' => $this->t('Time in seconds for which a service ticket is valid.'),
      '#size' => 30,
      '#default_value' => $config->get('ticket.service_ticket_timeout'),
    );
    $form['ticket']['proxy'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Proxy ticket timeout'),
      '#description' => $this->t('Time in seconds for which a proxy ticket is valid.'),
      '#size' => 30,
      '#default_value' => $config->get('ticket.proxy_ticket_timeout'),
    );
    $form['ticket']['proxy_granting'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Proxy granting ticket timeout'),
      '#description' => $this->t('Time in seconds for which a proxy granting ticket is valid.'),
      '#size' => 30,
      '#default_value' => $config->get('ticket.proxy_granting_ticket_timeout'),
    );
    $form['ticket']['ticket_granting_auth'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use ticket granting ticket'),
      '#description' => $this->t('When checked, a user will be granted a ticket for login. If a user is logged in to Drupal, but does not have the ticket (or it has expired) then checking this will force them to enter their credentials again.'),
      '#size' => 30,
      '#default_value' => (bool) $config->get('ticket.ticket_granting_ticket_auth'),
    );
    $form['ticket']['ticket_granting'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Ticket granting ticket timeout'),
      '#description' => $this->t('Time in seconds for which a ticket granting ticket is valid.'),
      '#size' => 30,
      '#default_value' => $config->get('ticket.ticket_granting_ticket_timeout'),
      '#states' => array(
        'visible' => array(
          ':input[name="ticket[ticket_granting_auth]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['ticket']['username'] = array(
      '#type' => 'select',
      '#title' => $this->t('Username value'),
      '#description' => $this->t('Which value to use for the username to respond.'),
      '#options' => [
        'name' => $this->t('Username'),
        'mail' => $this->t('Email Address'),
        'uid' => $this->t('UID'),
      ],
      '#default_value' => $config->get('ticket.ticket_username_attribute'),
    );

    $form['messages'] = array(
      '#type' => 'details',
      '#title' => 'Custom Messages',
      '#open' => TRUE,
      '#tree' => TRUE,
    );
    $form['messages']['invalid_service'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Display message for Invalid Service'),
      '#description' => $this->t('Message to display to a user requesting an invalid service.'),
      '#size' => 60,
      '#default_value' => $config->get('messages.invalid_service'),
    );
    $form['messages']['user_logout'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Display message for User Logout'),
      '#description' => $this->t('Message to display to a user logged out of single sign on.'),
      '#size' => 60,
      '#default_value' => $config->get('messages.user_logout'),
    );
    $form['messages']['logged_in'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Display message for Logged In Users'),
      '#description' => $this->t('Message to display to a user already logged in to single sign on.'),
      '#size' => 60,
      '#default_value' => $config->get('messages.logged_in'),
    );

    $form['debugging'] = array(
      '#type' => 'details',
      '#title' => 'Debugging Options',
      '#open' => FALSE,
      '#tree' => TRUE,
    );
    $form['debugging']['log'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Enable debugging log'),
      '#description' => $this->t('Enable debugging output to the Drupal log service.'),
      '#options' => [0 => 'No', 1 => 'Yes'],
      '#default_value' => $config->get('debugging.log'),
    );

    $form['login'] = array(
      '#type' => 'details',
      '#title' => 'Login Options',
      '#open' => FALSE,
      '#tree' => TRUE,
    );
    $form['login']['username'] = array(
      '#type' => 'select',
      '#title' => $this->t('Username field'),
      '#description' => $this->t('Which field to use for user authentication.'),
      '#options' => [
        'name' => $this->t('Username'),
        'mail' => $this->t('Email Address'),
        'both' => $this->t('Username or email address'),
      ],
      '#default_value' => $config->get('login.username_attribute'),
    );
    $form['login']['reset_password'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show reset password link on cas login form'),
      '#default_value' => $config->get('login.reset_password'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check timeouts to make sure they are integers.
    $values = $form_state->getValue('ticket');
    foreach ($values as $key => $value) {
      if ($key == 'username') {
        continue;
      }
      if (!is_numeric($value) || !(round($value) == $value)) {
        $form_state->setErrorByName("ticket][$key", $this->t('Ticket timeouts must be integer-valued'));
      }
    }
    
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cas_server.settings');
    $ticket_data = $form_state->getValue('ticket');
    $config
      ->set('ticket.service_ticket_timeout', (int)$ticket_data['service'])
      ->set('ticket.proxy_ticket_timeout', (int)$ticket_data['proxy'])
      ->set('ticket.proxy_granting_ticket_timeout', (int)$ticket_data['proxy_granting'])
      ->set('ticket.ticket_granting_ticket_auth', (int)$ticket_data['ticket_granting_auth'])
      ->set('ticket.ticket_granting_ticket_timeout', (int)$ticket_data['ticket_granting'])
      ->set('ticket.ticket_username_attribute', $ticket_data['username']);

    $message_data = $form_state->getValue('messages');
    $config
      ->set('messages.invalid_service', $message_data['invalid_service'])
      ->set('messages.user_logout', $message_data['user_logout'])
      ->set('messages.logged_in', $message_data['logged_in']);

    $config->set('debugging.log', (bool)$form_state->getValue(['debugging', 'log']));
    $config->set('login.username_attribute', $form_state->getValue(['login', 'username']));
    $config->set('login.reset_password', $form_state->getValue(['login', 'reset_password']));
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('cas_server.settings');
  }

}
