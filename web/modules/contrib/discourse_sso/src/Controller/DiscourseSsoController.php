<?php

namespace Drupal\discourse_sso\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Discourse SSO controller.
 */
class DiscourseSsoController extends ControllerBase {

  public function discourse_sso() {
    $user = \Drupal::currentUser();
    $payload = isset($_GET['sso']) ? $_GET['sso'] : NULL;
    $sig = isset($_GET['sig']) ? $_GET['sig'] : NULL;
    if (!$payload) {
      $payload = isset($_SESSION['discourse_sso_payload']) ? $_SESSION['discourse_sso_payload'] : NULL;
    }
    if (!$sig) {
      $sig = isset($_SESSION['discourse_sso_sig']) ? $_SESSION['discourse_sso_sig'] : NULL;
    }
    if (!$payload || !$sig) {
      $response = $this->retry();
    }
    else {
      $response = ($user->id())
                ? $this->validate($payload, $sig)
                : $this->login($payload, $sig);
    }

    return $response;
  }

  /**
   * Function is called if a user is authenticated with the primary Drupal
   * website.
   *
   * @param string $payload
   * @param string $sig
   * @return string
   */
  protected function validate($payload, $sig) {
    $user = \Drupal::currentUser();
    $payload = urldecode($payload);

    if (!hash_hmac("sha256", $payload, \Drupal::config('discourse_sso.settings')->get('discourse_sso_secret')) === $sig) {
      drupal_access_denied();
    }

    $query = array();
    parse_str(base64_decode($payload), $query);
    $nonce = isset($query["nonce"]) ? $query['nonce'] : NULL;
    if (!$nonce) {
      drupal_access_denied();
    }

    $account = \Drupal::entityManager()->getStorage('user')->load(\Drupal::currentUser()->id());
    $picture = !empty($account->get('user_picture')->getValue())
             ? $account->get('user_picture')->entity->toUrl()->toString(TRUE)
             : '';

    // Create the payload
    $real_name_field = \Drupal::config('discourse_sso.settings')->get('user_real_name_field');
    $real_name = (!empty($real_name_field) && isset($account->{$real_name_field}))
               ? $account->{$real_name_field}->value
               : '';
    $return_payload = base64_encode(http_build_query(array(
      'username' => $user->getAccountName(),
      'external_id' => $user->id(),
      'name' => $real_name,
      'email' => $user->getEmail(),
      'avatar_force_update' => true,
      'avatar_url' => $picture,
      'nonce' => $nonce,
    )));
    $return_sig = hash_hmac("sha256", $return_payload, \Drupal::config('discourse_sso.settings')->get('discourse_sso_secret'));

    $response = new TrustedRedirectResponse(\Drupal::config('discourse_sso.settings')->get('discourse_server') . '/session/sso_login?sso=' . $return_payload . '&sig=' . $return_sig);
    $response->getCacheableMetadata()->setCacheMaxAge(0);
    return $response;
  }

  /**
   * Function is called if a user is not authenticated with the primary Drupal
   * website.
   *
   * @param string $payload
   * @param string $sig
   * @return string
   */
  protected function login($payload, $sig) {
    $_SESSION['discourse_sso_sig'] = $sig;
    $_SESSION['discourse_sso_payload'] = $payload;

    $options = [
      'query' => ['destination' => 'discourse_sso?sso=' . $payload . '&sig=' . $sig],
      'absolute' => TRUE,
    ];
    return $this->redirect('user.login', [], $options);
  }

  /**
   * Function is called if either payload or sig is not set.
   *
   * @return string
   */
  protected function retry() {
    $response = new TrustedRedirectResponse(\Drupal::config('discourse_sso.settings')->get('discourse_server') . '/session/sso');
    $response->getCacheableMetadata()->setCacheMaxAge(0);
    return $response;
  }
}
