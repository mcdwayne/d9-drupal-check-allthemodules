<?php

namespace Drupal\patreon;

use \Patreon\API;
use \Patreon\OAuth;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use \Drupal\user\UserInterface;

/**
 * Class PatreonService.
 *
 * @package Drupal\patreon
 */
class PatreonService implements PatreonServiceInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new PatreonService object.
   */
  public function __construct() {
    $this->bridge = new PatreonBridge($this->getOauth(), PATREON_URL);
  }

  /**
   * The Patreon Bridge helper object.
   *
   * @var object
   *   A valid Patreon Bridge object.
   */
  public $bridge = '';

  /**
   * {@inheritdoc}
   */
  public function getCallback() {
    return Url::fromRoute('patreon.patreon_controller_oauth_callback', array(), array('absolute' => TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function authoriseAccount($client_id, $redirect = TRUE) {
    $redirect_url = $this->getCallback();
    $url = Url::fromUri($this->bridge->getAuthoriseUrl($client_id, $redirect_url->toString()));

    if ($redirect) {
      return new TrustedRedirectResponse($url->toString());
    }
    else {
      return $url;
    }
  }

  /**
   * {@inheritdoc}
   */
  private function getOauth() {
    $config = \Drupal::config('patreon.settings');
    $key = $config->get('patreon_client_id');
    $secret = $config->get('patreon_client_secret');
    return new \Patreon\OAuth($key, $secret);
  }

  /**
   * {@inheritdoc}
   */
  public function tokensFromCode($code) {
    $url = $this->getCallback();

    try {
      $tokens = $this->bridge->tokensFromCode($code, $url->toString());
    }
    catch (PatreonUnauthorizedException $e) {
      throw new PatreonUnauthorizedException($e->getMessage());
    }
    catch (\Exception $e) {
      throw new PatreonGeneralException($e->getMessage());
    }

    return $tokens;
  }

  /**
   * {@inheritdoc}
   */
  public function storeTokens($tokens, UserInterface $account = NULL) {
    $updated_config = \Drupal::service('config.factory')
      ->getEditable('patreon.settings');
    $updated_config->set('patreon_access_token', $tokens['access_token']);
    $updated_config->set('patreon_refresh_token', $tokens['refresh_token'])->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getStoredTokens(UserInterface $account = NULL) {
    $return = array();
    $config = \Drupal::config('patreon.settings');
    $return['refresh_token'] = $config->get('patreon_refresh_token');
    $return['access_token'] = $config->get('patreon_access_token');

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchUser() {
    return $this->apiFetch('fetch_user');
  }

  /**
   * {@inheritdoc}
   */
  public function fetchCampaign() {
    return $this->apiFetch('fetch_campaign');
  }

  /**
   * {@inheritdoc}
   */
  public function fetchPagePledges($campaign_id, $page_size, $cursor = NULL) {
    return $this->apiFetch('fetch_page_of_pledges', array(
      $campaign_id,
      $page_size,
      $cursor,
    ));
  }

  /**
   * Helper function to query the Patreon API.
   *
   * @param string $function
   *   A valid Patreon API function.
   * @param array $parameters
   *   An array of parameters required for the function call. Defaults to empty.
   *
   * @return null|array
   *   An array of the function callback data, or NULL on error.
   */
  private function apiFetch($function, $parameters = array()) {
    $return = NULL;

    try {
      $client = new \Patreon\API($this->bridge->getToken());
      $return = $this->bridge->apiFetch($client, $function, $parameters);
    }
    catch (PatreonMissingTokenException $e) {
      $message = $this->t('The Patreon API returned the following error: :error', array(
        ':error' => $e->getMessage(),
      ));
      \Drupal::logger('patreon')->error($message);
      drupal_set_message($this->t('A valid API token has not been set. Please visit @link', array(
        '@link' => Url::fromRoute('patreon.settings_form'),
      )), 'error');
    }
    catch (PatreonUnauthorizedException $e) {
      if (!$this->bridge->refreshTried) {
        $tokens = $this->getStoredTokens();
        $token = $tokens['refresh_token'];
        $redirect = $this->getCallback();
        try {
          $this->bridge->refreshTried = TRUE;
          $new_tokens = $this->bridge->getRefreshedTokens($token, $redirect->toString());
          $this->storeTokens($new_tokens);

          // Retry the function callback.
          $this->bridge->setToken($new_tokens['access_token']);
          $return = $this->apiFetch($function, $parameters);
        }
        catch (PatreonUnauthorizedException $error) {
          $message = $this->t('The Patreon API returned the following error: :error', array(
            ':error' => $error->getMessage(),
          ));
          \Drupal::logger('patreon')->error($message);
          drupal_set_message($this->t('Your API token has expired or not been set. Please visit @link', array(
            '@link' => Url::fromRoute('patreon.settings_form')->toString(),
          )), 'error');
        }
        catch (PatreonGeneralException $error) {
          $message = $this->t('The Patreon API returned the following error: :error', array(
            ':error' => $error->getMessage(),
          ));
          \Drupal::logger('patreon')->error($message);
          drupal_set_message($message, 'error');
        }
      }
      else {
        $message = $this->t('The Patreon API returned the following error: :error', array(
          ':error' => $e->getMessage(),
        ));
        \Drupal::logger('patreon')->error($message);
        drupal_set_message($this->t('Your API token has expired or not been set. Please visit @link', array(
          '@link' => Url::fromRoute('patreon.settings_form')->toString(),
        )), 'error');
      }

    }
    catch (PatreonGeneralException $e) {
      $message = $this->t('The Patreon API returned the following error: :error', array(
        ':error' => $e->getMessage(),
      ));
      \Drupal::logger('patreon')->error($message);
      drupal_set_message($message, 'error');
    }

    return $return;
  }

  /**
   * Helper to check if a Patreon user is a patron of the client.
   *
   * @param \Art4\JsonApiClient\Document $patreon_return
   *   Results array from patreon_fetch_user().
   *
   * @return bool
   *   TRUE is user's pledges match creator id. Defaults to FALSE.
   */
  public function isPatron(\Art4\JsonApiClient\Document $patreon_return) {
    $return = FALSE;
    $config = \Drupal::config('patreon.settings');

    if ($creator_id = $config->get('patreon_creator_id')) {
      $return = $this->bridge->isPatron($patreon_return, $creator_id);
    }

    return $return;
  }

}
