<?php

/**
 * @file
 * Contains \Drupal\hawk_auth\Tests\HawkAuthTestTrait.
 */

namespace Drupal\hawk_auth\Tests;

use Dragooon\Hawk\Client\ClientBuilder;
use Dragooon\Hawk\Credentials\CredentialsInterface;
use Dragooon\Hawk\Header\Header;
use Drupal\Core\Url;
use Drupal\hawk_auth\Entity\HawkCredential;
use Drupal\hawk_auth\Entity\HawkCredentialInterface;
use Drupal\user\Entity\User;

/**
 * Provides basic functions to test Hawk Authentication requests.
 */
trait HawkAuthTestTrait {

  /**
   * Retrieves a Drupal path or an absolute path using Hawk Credentials.
   *
   * @param \Drupal\Core\Url|string $path
   *   The path to send the request to.
   * @param \Drupal\hawk_auth\Entity\HawkCredentialInterface $credentials
   *   The credentials to authenticate the user as.
   * @param array $options
   *   (optional) Additional options for the user.
   *
   * @return string
   *   The retrieved HTML.
   */
  public function hawkAuthGet($path, HawkCredentialInterface $credentials, array $options = []) {
    $header = isset($options['header']) && $options['header'] instanceof Header ? $options['header']
      : $this->getHawkAuthHeader($path, $credentials);
    return $this->drupalGet($path, $options, [$header->fieldName() . ': ' . $header->fieldValue()]);
  }

  /**
   * Returns headers for a hawk auth credential based on a request.
   *
   * @param \Drupal\Core\Url|string $path
   *   The path to send a request to
   * @param \Dragooon\Hawk\Credentials\CredentialsInterface $credentials
   *   The credentials to get the headers for.
   * @param string $method
   *   GET or POST.
   * @param array $options
   *   Additional Hawk options.
   *
   * @return Header
   *   List of headers for this Hawk request
   */
  protected function getHawkAuthHeader($path, CredentialsInterface $credentials, $method = 'GET', array $options = []) {
    if ($path instanceof Url) {
      $path->setAbsolute(TRUE);
      $path = $path->toString();
    }

    $client = ClientBuilder::create()->build();
    $request = $client->createRequest($credentials, $path, $method, $options);
    return $request->header();
  }


  /**
   * Generates hawk credentials for an user.
   *
   * @param \Drupal\user\Entity\User $user
   *   User to create credentials for.
   *
   * @return HawkCredential
   *   Newly created credentials for the user.
   */
  protected function getHawkCredentials(User $user) {
    $credential = HawkCredential::create([
      'key_secret' => user_password(32),
      'key_algo' => 'sha256',
      'uid' => $user->id(),
    ]);
    $credential->save();

    return $credential;
  }
}
