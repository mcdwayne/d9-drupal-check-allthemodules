<?php

namespace Drupal\vk_authentication\Vk;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\vk_authentication\User\UserAuthentication;

/**
 * Class VkAuthentication.
 *
 * @package Drupal\vk_authentication\Vk
 */
class VkAuthentication {

  /**
   * Variable $config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * UserAuthentication class.
   *
   * @var \Drupal\vk_authentication\User\UserAuthentication
   */
  private $userAuthentication;

  /**
   * Constructor.
   *
   * The constructor parameters are passed as arguments in
   * vk_authentication.services.yml file.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   $configFactory object help us to get $config object.
   * @param \Drupal\vk_authentication\User\UserAuthentication $userAuthentication
   *   UserAuthentication object.
   */
  public function __construct(ConfigFactoryInterface $configFactory, UserAuthentication $userAuthentication) {
    $this->config = $configFactory->get('vk_authentication.admin_settings');
    $this->userAuthentication = $userAuthentication;
  }

  /**
   * Lets your browser to redirect to VK authentication API.
   *
   * As example:
   * https://oauth.vk.com/authorize?client_id=someId&display=page&redirect_uri=http://example.com&scope=friends&response_type=code&v=5.78
   *
   * @return string
   *   Return JavaScript code.
   */
  public function redirectToVk() {
    return '
      <script language="JavaScript">
       window.location.href="https://oauth.vk.com/authorize?client_id=' .
       $this->config->get('vk_authentication_application_id') . '&display=' .
       $this->config->get('vk_authentication_display') . '&redirect_uri=' .
       $this->config->get('vk_authentication_redirect') . '&scope=4194304&response_type=code&v=5.78"
      </script>';
  }

  /**
   * Making authentication.
   *
   * @param string $code
   *   Response code from VK.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface|\Drupal\user\Entity\User
   *   Return User object if success or FALSE otherwise.
   *
   * @throws \Exception
   */
  public function makeAuthentication($code) {
    // Making request.
    $result = json_decode(@file_get_contents('https://oauth.vk.com/access_token?client_id=' .
      $this->config->get('vk_authentication_application_id') . '&client_secret=' .
      $this->config->get('vk_authentication_secret_key') . '&redirect_uri=' .
      $this->config->get('vk_authentication_redirect') . '&code=' . $code),
      TRUE);

    // If got token.
    if ($result && array_key_exists('access_token', $result)) {
      // Response should content 'email' parameter.
      if (array_key_exists('email', $result)) {
        /*Check if user registered or blocked. If not registered, making registration*/
        try {
          $userOrBoolean = $this->userAuthentication->userCheck($result['email']);
          if (!is_object($userOrBoolean) && !is_bool($userOrBoolean)) {
            throw new \Exception("Variable should contain User object or boolean.");
          }
          // If got new 'user' object.
          if (!is_bool($userOrBoolean) && is_numeric($userOrBoolean->id())) {
            // Get user avatar url from social network.
            $avatarUri = $this->getAvatarUri($result['user_id'], $result['access_token']);

            if ($avatarUri != FALSE) {
              // Save user avatar.
              $this->userAuthentication->saveUserAvatar($userOrBoolean, $avatarUri);
            }

            return TRUE;
          }
        }
        catch (\Exception $e) {
          echo 'Message: ' . $e->getMessage();
        }
      }
      // If user has no email linked to social network personal page.
      else {
        return $this->userAuthentication->userCheck(FALSE);
      }
    }

    return FALSE;
  }

  /**
   * Make a request to social network to get user avatar url.
   *
   * @param int $user_id
   *   Vk user ID.
   * @param string $access_token
   *   Vk access token.
   *
   * @return bool|mixed
   *   Return picture uri if success or FALSE otherwise.
   */
  private function getAvatarUri($user_id, $access_token) {
    $result = file_get_contents('https://api.vk.com/method/users.get?user_ids=' . $user_id .
            '&fields=photo_100&access_token=' . $access_token . '&v=5.78');

    // Separate picture uri.
    if (preg_match('/https.*?\.jpg/', $result, $result)) {
      $result[0] = str_replace('\\', '', $result[0]);

      return $result[0];
    }

    return FALSE;
  }

}
