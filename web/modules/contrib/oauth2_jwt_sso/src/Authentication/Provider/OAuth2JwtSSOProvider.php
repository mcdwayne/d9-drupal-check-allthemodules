<?php

namespace Drupal\oauth2_jwt_sso\Authentication\Provider;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\oauth2_jwt_sso\Authentication\OAuth2JwtSSOResourceOwner;
use Drupal\user\Entity\User;
use http\Exception\InvalidArgumentException;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Keychain;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class OAuth2JwtSSOProvider
 *
 * @package Drupal\oauth2_jwt_sso\Authentication\Provider
 */
class OAuth2JwtSSOProvider extends AbstractProvider implements OAuth2JwtSSOProviderInterface {

  use BearerAuthorizationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * OAuth2JwtSSOProvider constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   * @param array $options
   * @param array $collaborators
   */
  public function __construct(ConfigFactoryInterface $configFactory, SessionInterface $session, array $options = [], array $collaborators = []) {
    $this->configFactory = $configFactory;
    $options['clientId'] = $this->configFactory->get('oauth2_jwt_sso.settings')
      ->get('client_id');
    $options['clientSecret'] = $this->configFactory->get('oauth2_jwt_sso.settings')
      ->get('client_secret');
    $this->session = $session;
    parent::__construct($options, $collaborators);
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseAuthorizationUrl() {
    return $this->configFactory->get('oauth2_jwt_sso.settings')
      ->get('authorization_url');
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseAccessTokenUrl(array $params) {
    return $this->configFactory->get('oauth2_jwt_sso.settings')
      ->get('access_token_url');
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceOwnerDetailsUrl(AccessToken $token) {
    // TODO: Implement getResourceOwnerDetailsUrl() method.
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultScopes() {
    if($this->configFactory->get('oauth2_jwt_sso.settings')->get('roles_remote_login')){
      return array_values($this->configFactory->get('oauth2_jwt_sso.settings')->get('roles_remote_login'));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getScopeSeparator() {
    return " ";
  }

  /**
   * Check a provider response for errors.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   * @param array|string $data
   *
   * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
   */
  protected function checkResponse(ResponseInterface $response, $data) {
    if ($response->getStatusCode() >= 400) {
      throw new IdentityProviderException(
        $data['error'] ? : $response->getReasonPhrase(),
        $response->getStatusCode(),
        $response
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createResourceOwner(array $response, AccessToken $token) {
    return new OAuth2JwtSSOResourceOwner($response, $token);
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return $this->hasToken($request);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    $auth_header = trim($request->headers->get('Authorization', '', TRUE));
    try{
        $token_str = substr($auth_header, 7);
        $token = (new Parser())->parse($token_str);
        if ($this->verifyToken($token)) {
          $account = $this->tokenAuthUser($token);
          if ($account->isBlocked() && $account->isAuthenticated()) {
            throw new AccessDeniedHttpException(
              t(
              '%name is blocked or has not been activated yet.',
              ['%name' => $account->getAccountName()]
            ));
          }
          return $account;
        }
        throw new \Exception("The token is invalid： ". $token_str);
    }catch (\Exception $e){
      watchdog_exception("OAuth2 JWT SSO",$e,$e->getMessage());
      return null;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function hasToken(Request $request) {
    $auth_header = trim($request->headers->get('Authorization', '', TRUE));
    return strpos($auth_header, 'Bearer ') !== FALSE;
  }

  /**
   * Checks that the token is valid.
   *
   * @param \Lcobucci\JWT\Token $token
   *
   * @return bool
   */
  public function verifyToken(Token $token){
    $public_key = $this->configFactory->get('oauth2_jwt_sso.settings')
      ->get('auth_public_key');
    $signer = new Sha256();
    $validateData = new ValidationData();
    $validate_signature = $token->verify($signer, $public_key);
    $validate_token = $token->validate($validateData);
    $token_claims = $token->getClaims();
    $validate_claims = TRUE;
    \Drupal::moduleHandler()
      ->alter('SSO_verify_token_alter', $validate_claims, $token_claims);

    return ($validate_signature && $validate_token && $validate_claims);
  }

  /**
   * Create or load an user account from jwt token.
   *
   * @param \Lcobucci\JWT\Token $token
   *
   * @return \Drupal\user\Entity\User
   */
  public function tokenAuthUser(Token $token){
    $username = $token->getClaim('username');
    $roles = $token->getClaim('scopes');
    if (user_load_by_name($username)) {
    $user = user_load_by_name($username);
    }else{
      $user = User::create([
        'name' => $username,
        'mail' => $username . '@' . $username . '.com',
        'pass' => NULL,
        'status' => 1,
      ]);
      foreach($roles as $role) {
        if ($role != 'authenticated') {
          $user->addRole($role);
        }
      }
      $user->save();
    }
    return $user;
  }

}
