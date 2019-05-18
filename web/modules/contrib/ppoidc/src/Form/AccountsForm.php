<?php

namespace Drupal\pixelpin_openid_connect\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Session\AccountInterface;
use Drupal\pixelpin_openid_connect\Authmap;
use Drupal\pixelpin_openid_connect\Claims;
use Drupal\pixelpin_openid_connect\Plugin\OpenIDConnectClientManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AccountsForm.
 *
 * @package Drupal\pixelpin_openid_connect\Form
 */
class AccountsForm extends FormBase implements ContainerInjectionInterface {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Drupal\pixelpin_openid_connect\Authmap definition.
   *
   * @var \Drupal\pixelpin_openid_connect\Authmap
   */
  protected $authmap;

  /**
   * Drupal\pixelpin_openid_connect\Claims definition.
   *
   * @var \Drupal\pixelpin_openid_connect\Claims
   */
  protected $claims;

  /**
   * Drupal\pixelpin_openid_connect\Plugin\OpenIDConnectClientManager definition.
   *
   * @var \Drupal\pixelpin_openid_connect\Plugin\OpenIDConnectClientManager
   */
  protected $pluginManager;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user account.
   * @param \Drupal\pixelpin_openid_connect\Authmap $authmap
   *   The authmap storage.
   * @param \Drupal\pixelpin_openid_connect\Claims $claims
   *   The OpenID Connect claims.
   * @param \Drupal\pixelpin_openid_connect\Plugin\OpenIDConnectClientManager $plugin_manager
   *   The OpenID Connect client manager.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(
      AccountProxy $current_user,
      Authmap $authmap,
      Claims $claims,
      OpenIDConnectClientManager $plugin_manager,
      ConfigFactory $config_factory
  ) {

    $this->currentUser = $current_user;
    $this->authmap = $authmap;
    $this->claims = $claims;
    $this->pluginManager = $plugin_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('pixelpin_openid_connect.authmap'),
      $container->get('pixelpin_openid_connect.claims'),
      $container->get('plugin.manager.pixelpin_openid_connect_client.processor'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pixelpin_openid_connect_accounts_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL) {
    $settings = $this->configFactory()
      ->getEditable('pixelpin_openid_connect.settings');
      
    $form_state->set('account', $user);

    $clients = $this->pluginManager->getDefinitions();

    $read_only = $this->currentUser->id() != $user->id();

    $form['help'] = array(
      '#prefix' => '<p class="description">',
      '#suffix' => '</p>',
    );

    if (empty($clients)) {
      $form['help']['#markup'] = t('No external account providers are available.');
      return $form;
    }
    elseif ($this->currentUser->id() == $user->id()) {
      $form['help']['#markup'] = t('You can connect your account with these external providers.');
    }

    $connected_accounts = $this->authmap->getConnectedAccounts($user);

    foreach ($clients as $client) {
      $enabled = $this->configFactory
        ->getEditable('pixelpin_openid_connect.settings.' . $client['id'])
        ->get('enabled');
      if (!$enabled) {
        continue;
      }

      $form['#attached'] = array(
            'library' => array(
              'pixelpin_openid_connect/pixelpin_sso',
          ),
      );

      $form[$client['id']] = array(
        '#type' => 'fieldset',
        '#title' => t('Provider: PixelPin', array('@title' => $client['label'])),
      );

      $fieldset = &$form[$client['id']];
      $connected = isset($connected_accounts[$client['id']]);
      $fieldset['status'] = array(
        '#type' => 'item',
        '#title' => t('Status'),
        '#markup' => t('Not connected'),
      );

      $customSsoButton = $settings->get('ppsso');

      if ($connected) {
        $fieldset['status']['#markup'] = t('Connected as %sub', array(
          '%sub' => $connected_accounts[$client['id']],
        ));

        if($customSsoButton['disconnectButton']){
          $ssoDisconnectButton = $customSsoButton['disconnectButton'];
        } else {
          $ssoDisconnectButton = '<button name="pixelpin" id="edit-openid-connect-client-pixelpin-login-front-page" type="submit" class="ppsso-btn">Disconnect from <span class="ppsso-logotype">PixelPin</span></button>';
        }

        $fieldset['pixelpin_openid_connect_client_' . $client['id'] . '_disconnect'] = array(
            '#type' => 'inline_template',
            '#template' => '<div>' . $ssoDisconnectButton . '</div>',
            '#context' => [
              'value' => t('Disconnect From PixelPin', array('@client_title' => $client['label'])),
            ],
          );

        $fieldset['pixelpin_openid_connect_client_' . $client['id'] . '_disconnect_hidden'] = array(
          '#type' => 'submit',
          '#value' => t('Disconnect from PixelPin', array('@client_title' => $client['label'])),
          '#name' => 'disconnect__' . $client['id'],
          '#access' => !$read_only,
        );
      }
      else {
        $fieldset['status']['#markup'] = t('Not connected');

        if($customSsoButton['connectButton']){
          $ssoConnectButton = $customSsoButton['connectButton'];
        } else {
          $ssoConnectButton = '<button name="pixelpin" id="edit-openid-connect-client-pixelpin-login-front-page" type="submit" class="ppsso-btn">Connect to <span class="ppsso-logotype">PixelPin</span></button>';
        }

        $fieldset['pixelpin_openid_connect_client_' . $client_id . '_connect'] = array(
            '#type' => 'inline_template',
            '#template' => '<div>' . $ssoConnectButton . '</div>',
            '#context' => [
              'value' => t('Connect With PixelPin', array('@client_title' => $client['label'])),
            ],
        );

        $fieldset['pixelpin_openid_connect_client_' . $client['id'] . '_connect_hidden'] = array(
          '#type' => 'submit',
          '#value' => t('Connect with PixelPin', array('@client_title' => $client['label'])),
          '#name' => 'connect__' . $client['id'],
          '#access' => !$read_only,
        );
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    list($op, $client_name) = explode('__', $form_state->getTriggeringElement()['#name'], 2);

    if ($op === 'disconnect') {
      $this->authmap->deleteAssociation($form_state->get('account')->id(), $client_name);
      $client = $this->pluginManager->getDefinition($client_name);
      drupal_set_message(t('Account successfully disconnected from PixelPin.', array('@client' => $client['label'])));
      return;
    }

    if ($this->currentUser->id() !== $form_state->get('account')->id()) {
      drupal_set_message(t("You cannot connect another user's account."), 'error');
      return;
    }

    pixelpin_openid_connect_save_destination();

    $configuration = $this->config('pixelpin_openid_connect.settings.' . $client_name)
      ->get('settings');
    $client = $this->pluginManager->createInstance(
      $client_name,
      $configuration
    );
    $scopes = $this->claims->getScopes();
    $_SESSION['pixelpin_openid_connect_op'] = $op;
    $_SESSION['pixelpin_openid_connect_connect_uid'] = $this->currentUser->id();
    $response = $client->authorize($scopes, $form_state);
    $form_state->setResponse($response);
  }

  /**
   * Checks access for the OpenID-Connect accounts form.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user having accounts.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $user) {
    if ($this->currentUser->hasPermission('administer users')) {
      return AccessResult::allowed();
    }

    if ($this->currentUser->id() && $this->currentUser->id() === $user->id()) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
