<?php

namespace Drupal\simple_oauth_code;

use Drupal\Core\Session\AccountInterface;
use Drupal\simple_oauth\Entities\UserEntity;
use Drupal\simple_oauth\Repositories\ClientRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;
use Defuse\Crypto\Core;
use Drupal\Core\Site\Settings;


/**
 * Class AuthorizationCodeGenerator.
 */
class AuthorizationCodeGenerator extends AuthCodeGrant implements AuthorizationCodeGeneratorInterface {

  /**
   * @var \DateInterval
   */
  private $authCodeTTL;

  public function __construct(ClientRepository $clientRepository, AuthCodeRepositoryInterface $authCodeRepository, RefreshTokenRepositoryInterface $refreshTokenRepository, \DateInterval $authCodeTTL) {
    parent::__construct($authCodeRepository, $refreshTokenRepository, $authCodeTTL);
    $this->setClientRepository($clientRepository);
    $this->authCodeTTL = $authCodeTTL;

    $salt = Settings::getHashSalt();
    // The hash salt must be at least 32 characters long.
    if (Core::ourStrlen($salt) < 32) {
      throw OAuthServerException::serverError('Hash salt must be at least 32 characters long.');
    }

    $this->setEncryptionKey(Core::ourSubstr($salt, 0, 32));
  }

  /**
   * Return the grant identifier that can be used in matching up requests.
   *
   * @return string
   */
  public function getIdentifier() {
    return 'authorization_code_generator';
  }

  /**
   * Respond to an incoming request.
   *
   * @param ServerRequestInterface $request
   * @param ResponseTypeInterface $responseType
   * @param \DateInterval $accessTokenTTL
   *
   * @return ResponseTypeInterface
   */
  public function respondToAccessTokenRequest(
    ServerRequestInterface $request,
    ResponseTypeInterface $responseType,
    \DateInterval $accessTokenTTL
  ) {
    throw new \LogicException('This grant cannot respond to authorization request');
  }

  /**
   * @param $client_id
   * @return AuthorizationRequest
   * @throws OAuthServerException
   */
  public function makeAuthorizationRequest($client_id, AccountInterface $user) {

    $clientId = $client_id;
    if (is_null($clientId)) {
      throw OAuthServerException::invalidRequest('client_id');
    }

    $client = $this->clientRepository->getClientEntity(
      $clientId,
      parent::getIdentifier(),
      null,
      false
    );

    if ($client instanceof ClientEntityInterface === false) {
      throw OAuthServerException::invalidClient();
    }

    $redirectUri = '';

    $scope = '';
    $scopes = $this->validateScopes($this->defaultScope,
      is_array($client->getRedirectUri())
        ? $client->getRedirectUri()[0]
        : $client->getRedirectUri()
    );

    $stateParameter = '';

    $authorizationRequest = new AuthorizationRequest();
    $authorizationRequest->setGrantTypeId(parent::getIdentifier());
    $authorizationRequest->setClient($client);
    $authorizationRequest->setRedirectUri($redirectUri);
    $authorizationRequest->setState($stateParameter);
    $authorizationRequest->setScopes($scopes);

    $user_entity = new UserEntity();
    $user_entity->setIdentifier($user->id());
    $authorizationRequest->setUser($user_entity);

    return $authorizationRequest;
  }

  /**
   * @param $client
   * @param AccountInterface $user
   * @return array
   * @throws OAuthServerException
   * @throws \League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException
   */
  public function generate($client, AccountInterface $user) {

    $authorizationRequest = $this->makeAuthorizationRequest($client, $user);

    $authCode = $this->issueAuthCode(
      $this->authCodeTTL,
      $authorizationRequest->getClient(),
      $authorizationRequest->getUser()->getIdentifier(),
      $authorizationRequest->getRedirectUri(),
      $authorizationRequest->getScopes()
    );

    $payload = [
      'client_id'             => $authCode->getClient()->getIdentifier(),
      'redirect_uri'          => $authCode->getRedirectUri(),
      'auth_code_id'          => $authCode->getIdentifier(),
      'scopes'                => $authCode->getScopes(),
      'user_id'               => $authCode->getUserIdentifier(),
      'expire_time'           => (new \DateTime())->add($this->authCodeTTL)->format('U')
    ];

    return [
      'code'  => $this->encrypt(
        json_encode(
          $payload
        )
      ),
      'state' => $authorizationRequest->getState(),
    ];
  }
}

