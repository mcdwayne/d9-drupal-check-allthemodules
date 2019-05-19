<?php
/**
 * Created by PhpStorm.
 * User: kentkent
 * Date: 2018/6/18
 * Time: 下午2:50
 */

namespace Drupal\simple_oauth_code;


use Drupal\simple_oauth\Repositories\ClientRepository;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class AuthorizationCodeGeneratorFactory {

  public static function create(
    ClientRepository $clientRepository,
    AuthCodeRepositoryInterface $authCodeRepository,
    RefreshTokenRepositoryInterface $refreshTokenRepository) {

    $settings = \Drupal::config('simple_oauth.settings');
    $authCodeExpiration = new \DateInterval(sprintf('PT%dS', $settings->get('access_token_expiration')));

    return new AuthorizationCodeGenerator($clientRepository, $authCodeRepository, $refreshTokenRepository, $authCodeExpiration);

  }

}