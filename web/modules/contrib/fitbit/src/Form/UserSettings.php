<?php

namespace Drupal\fitbit\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\fitbit\FitbitAccessTokenManager;
use Drupal\fitbit\FitbitClient;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserSettings extends FormBase {

  /**
   * Fitbit client.
   *
   * @var \Drupal\fitbit\FitbitClient
   */
  protected $fitbitClient;

  /**
   * Fitbit access token manager.
   *
   * @var \Drupal\fitbit\FitbitAccessTokenManager
   */
  protected $fitbitAccessTokenManager;

  /**
   * Session storage.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStore;

  /**
   * UserSettings constructor.
   *
   * @param FitbitClient $fitbit_client
   * @param FitbitAccessTokenManager $fitbit_access_token_manager
   * @param PrivateTempStoreFactory $private_temp_store_factory
   */
  public function __construct(FitbitClient $fitbit_client, FitbitAccessTokenManager $fitbit_access_token_manager, PrivateTempStoreFactory $private_temp_store_factory) {
    $this->fitbitClient = $fitbit_client;
    $this->fitbitAccessTokenManager = $fitbit_access_token_manager;
    $this->tempStore = $private_temp_store_factory->get('fitbit');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fitbit.client'),
      $container->get('fitbit.access_token_manager'),
      $container->get('user.private_tempstore'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fitbit_user_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL) {
    // Store the uid on the form object.
    $form['uid'] = [
      '#type' => 'value',
      '#value' => $user->id(),
    ];

    // Attempt to get the Fitibit account. If the account is properly linked,
    // this will return a result which we'll use to present some of the users
    // stats.
    if ($access_token = $this->fitbitAccessTokenManager->loadAccessToken($user->id())) {

      if ($fitbit_user = $this->fitbitClient->getResourceOwner($access_token)) {
        $user_data = $fitbit_user->toArray();

        $form['authenticated'] = [
          '#markup' => $this->t('<p>You\'re authenticated. Welcome @name.</p>', ['@name' => $fitbit_user->getDisplayName()]),
        ];
        if (!empty($user_data['avatar150'])) {
          $form['avatar'] = [
            '#theme' => 'image',
            '#uri' => $user_data['avatar150'],
          ];
        }
        if (!empty($user_data['averageDailySteps'])) {
          $form['avg_steps'] = [
            '#markup' => $this->t('<p><strong>Average daily steps:</strong> @steps</p>', ['@steps' => $user_data['averageDailySteps']]),
          ];
        }
      }
      else {
        $form['authenticated'] = [
          '#markup' => $this->t('<p>You\'re authenticated.</p>'),
        ];
      }

      $form['revoke'] = [
        '#type' => 'submit',
        '#value' => $this->t('Revoke access to my Fitbit account'),
        '#submit' => [
          [$this, 'revokeAccess'],
        ],
      ];
    }
    else {
      $form['connect'] = [
        '#type' => 'submit',
        '#value' => $this->t('Connect to Fitbit'),
        '#submit' => [
          [$this, 'submitForm']
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $authorization_url = $this->fitbitClient->getAuthorizationUrl();
    $this->tempStore->set('state', $this->fitbitClient->getState());
    $form_state->setResponse(new TrustedRedirectResponse($authorization_url, 302));
  }

  /**
   * Form submission handler for revoke access to the users Fitbit account.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function revokeAccess(array &$form, FormStateInterface $form_state) {
    $uid = $form_state->getValue('uid');

    if ($access_token = $this->fitbitAccessTokenManager->loadAccessToken($uid)) {
      try {
        $this->fitbitClient->revoke($access_token);
        $this->fitbitAccessTokenManager->delete($uid);
        drupal_set_message('Access to your Fitbit account has been revoked.');
      }
      catch (\Exception $e) {
        watchdog_exception('fitbit', $e);
        drupal_set_message($this->t('There was an error revoking access to your account: @message. Please try again. If the error persists, please contact the site administrator.', ['@message' => $e->getMessage()]), 'error');
      }
    }
  }

  /**
   * Checks access for a users Fitbit settings page.
   *
   * @param AccountInterface $account
   *   Current user.
   * @param UserInterface $user
   *   User being accessed.
   *
   * @return AccessResult
   */
  public function checkAccess(AccountInterface $account, UserInterface $user = NULL) {
    // Only allow access if user has authorize fitbit account and it's their
    // own page.
    return AccessResult::allowedIf($account->hasPermission('authorize fitbit account') && $account->id() === $user->id());
  }
}
