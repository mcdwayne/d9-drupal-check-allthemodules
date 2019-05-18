<?php

namespace Drupal\cognito\Aws;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use GuzzleHttp\Client;

/**
 * A helper service to signup and authorise users against Cognito.
 */
class Cognito extends CognitoBase {

  /**
   * The Cognito aws-sdk client.
   *
   * @var \Aws\CognitoIdentityProvider\CognitoIdentityProviderClient
   */
  protected $client;

  /**
   * The unique Id for this client.
   *
   * @var string
   */
  protected $clientId;

  /**
   * The unique user pool Id.
   *
   * @var string
   */
  protected $userPoolId;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Cognito constructor.
   *
   * @param \Aws\CognitoIdentityProvider\CognitoIdentityProviderClient $client
   *   The congnito aws client.
   * @param string $clientId
   *   The client Id.
   * @param string $userPoolId
   *   The user pool Id.
   * @param \GuzzleHttp\Client $httpClient
   *   The http client.
   */
  public function __construct(CognitoIdentityProviderClient $client, $clientId, $userPoolId, Client $httpClient) {
    $this->client = $client;
    $this->clientId = $clientId;
    $this->userPoolId = $userPoolId;
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public function authorize($username, $password) {
    return $this->wrap(function () use ($username, $password) {
      $result = $this->adminInitiateAuth($username, $password);

      if ($result->hasError() || $result->isChallenge()) {
        return $result;
      }

      $idToken = $result->getResult()['AuthenticationResult']['IdToken'];
      if (!$this->validateToken($idToken)) {
        throw new \Exception('Token failed to validate');
      }

      return $result;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function signUp($username, $password, $email, array $userAttributes = []) {
    return $this->wrap(function () use ($username, $password, $email, $userAttributes) {
      $userAttributes[] = [
        'Name' => 'email',
        'Value' => $email,
      ];
      return $this->client->signUp([
        'ClientId' => $this->clientId,
        'Password' => $password,
        'UserAttributes' => $userAttributes,
        'Username' => $username,
      ]);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function resendConfirmationCode($username) {
    return $this->wrap(function () use ($username) {
      return $this->client->resendConfirmationCode([
        'ClientId' => $this->clientId,
        'Username' => $username,
      ]);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function confirmSignup($username, $confirmCode) {
    return $this->wrap(function () use ($username, $confirmCode) {
      return $this->client->confirmSignUp([
        'ClientId' => $this->clientId,
        'ConfirmationCode' => trim($confirmCode),
        'Username' => $username,
      ]);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function forgotPassword($username) {
    return $this->wrap(function () use ($username) {
      return $this->client->forgotPassword([
        'ClientId' => $this->clientId,
        'Username' => $username,
      ]);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForgotPassword($username, $password, $confirmationCode) {
    return $this->wrap(function () use ($username, $password, $confirmationCode) {
      return $this->client->confirmForgotPassword([
        'ClientId' => $this->clientId,
        'Username' => $username,
        'Password' => $password,
        'ConfirmationCode' => trim($confirmationCode),
      ]);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function changePassword($accessToken, $oldPassword, $newPassword) {
    return $this->wrap(function () use ($accessToken, $oldPassword, $newPassword) {
      return $this->client->changePassword([
        'AccessToken' => $accessToken,
        'PreviousPassword' => $oldPassword,
        'ProposedPassword' => $newPassword,
      ]);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getUser($accessToken) {
    return $this->wrap(function () use ($accessToken) {
      return $this->client->getUser([
        'AccessToken' => $accessToken,
      ]);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function updateUserAttributes($accessToken, array $userAttributes) {
    return $this->wrap(function () use ($accessToken, $userAttributes) {
      return $this->client->updateUserAttributes([
        'AccessToken' => $accessToken,
        'UserAttributes' => $userAttributes,
      ]);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function adminEnableUser($username) {
    return $this->wrap(function () use ($username) {
      return $this->client->adminEnableUser([
        'UserPoolId' => $this->userPoolId,
        'Username' => $username,
      ]);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function adminDisableUser($username) {
    return $this->wrap(function () use ($username) {
      return $this->client->adminDisableUser([
        'UserPoolId' => $this->userPoolId,
        'Username' => $username,
      ]);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function adminSignup($username, $email, $messageAction = '', array $userAttributes = []) {
    $userAttributes[] = [
      'Name' => 'email',
      'Value' => $email,
    ];
    $payload = [
      'DesiredDeliveryMediums' => ['EMAIL'],
      'UserAttributes' => $userAttributes,
      'UserPoolId' => $this->userPoolId,
      'Username' => $username,
    ];
    if ($messageAction) {
      $payload['MessageAction'] = $messageAction;
    }
    return $this->wrap(function () use ($payload) {
      return $this->client->adminCreateUser($payload);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function adminRespondToNewPasswordChallenge($username, $challengeType, $challengeAnswer, $session) {
    return $this->wrap(function () use ($username, $challengeType, $challengeAnswer, $session) {
      return $this->client->adminRespondToAuthChallenge([
        'ChallengeName' => $challengeType,
        'ChallengeResponses' => [
          'USERNAME' => $username,
          'NEW_PASSWORD' => $challengeAnswer,
        ],
        'Session' => $session,
        'ClientId' => $this->clientId,
        'UserPoolId' => $this->userPoolId,
      ]);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function adminUpdateUserAttributes($username, $attributeName, $attributeValue) {
    return $this->wrap(function () use ($username, $attributeName, $attributeValue) {
      return $this->client->adminUpdateUserAttributes([
        'Username' => $username,
        'UserPoolId' => $this->userPoolId,
        'UserAttributes' => [
          [
            'Name' => $attributeName,
            'Value' => $attributeValue,
          ],
          // @TODO, we should not automatically verify the email. See
          // https://drupal.org/node/2907479 for the fix here.
          [
            'Name' => 'email_verified',
            'Value' => 'true',
          ],
        ],
      ]);
    });
  }

  /**
   * Starts the login process.
   *
   * @param string $username
   *   The username.
   * @param string $password
   *   The password.
   *
   * @return \Drupal\cognito\Aws\CognitoResult
   *   The result includes AccessToken, RefreshToken, IdToken and ExpiresIn.
   */
  protected function adminInitiateAuth($username, $password) {
    return $this->wrap(function () use ($username, $password) {
      return $this->client->adminInitiateAuth([
        'AuthFlow' => 'ADMIN_NO_SRP_AUTH',
        'AuthParameters' => [
          'USERNAME' => $username,
          'PASSWORD' => $password,
        ],
        'ClientId' => $this->clientId,
        'UserPoolId' => $this->userPoolId,
      ]);
    });
  }

  /**
   * Validates a token from the Cognito initiateAuth endpoints.
   *
   * @param string $idToken
   *   The JWT encoded token.
   *
   * @return bool
   *   TRUE if we're authenticated otherwise FALSE.
   */
  protected function validateToken($idToken) {
    $jwt = \JOSE_JWT::decode($idToken);
    $kid = $jwt->header['kid'];

    // @TODO, This could be cached.
    $response = $this->httpClient->get($this->getJwkUrl());
    if ($response->getStatusCode() !== 200) {
      return FALSE;
    }

    if (!$keys = json_decode($response->getBody(), TRUE)) {
      return FALSE;
    }

    // Find the key based on the 'kid' from the token and then validate the
    // entire token.
    foreach ($keys['keys'] as $key) {
      if ($key['kid'] === $kid) {

        // We have to first convert the key into PEM format because AWS sends
        // them in DER.
        $jwk = new \JOSE_JWK($key);
        $public_key = $jwk->toKey();

        $jwt->verify($public_key, 'RS256');
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Gets the URL where we must retrieve the pool public key.
   *
   * @return string
   *   The Cognito JWK url.
   */
  protected function getJwkUrl() {
    return sprintf('https://cognito-idp.%s.amazonaws.com/%s/.well-known/jwks.json', $this->client->getRegion(), $this->userPoolId);
  }

}
