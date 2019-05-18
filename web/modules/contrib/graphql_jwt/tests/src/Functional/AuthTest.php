<?php

namespace Drupal\Tests\graphql_jwt\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the authentication via GraphQL with JWT auth token being returned.
 *
 * @group graphql_jwt
 */
class AuthTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'key',
    'jwt',
    'graphql',
    'graphql_core',
    'graphql_jwt_test',
  ];

  /**
   * GraphQL query to get JWT token.
   *
   * @var string
   */
  protected $query = '
query JwtToken($username: String!, $password: String!) {
  JwtToken(username: $username, password: $password) {
    jwt
  }
}';

  /**
   * The JWT Transcoder service.
   *
   * @var \Drupal\jwt\Transcoder\JwtTranscoderInterface
   */
  protected $transcoder;

  /**
   * {@inheritdoc}
   *
   * Set up JWT encryption key and access to GraphQL for anonymous users.
   */
  protected function setUp() {
    parent::setUp();
    // Set the algorithm and JWT key.
    \Drupal::configFactory()
      ->getEditable('jwt.config')
      ->set('algorithm', 'HS256')
      ->set('key_id', 'graphql_jwt_test_hmac')
      ->save();
    // Allow anonymous roles to access GraphQL.
    Role::load('anonymous')
      ->grantPermission('execute graphql requests')
      ->save();
    // Set the transcoder, so we can decode JWT token data.
    $key_repository = $this->container->get('key.repository');
    $key = $key_repository->getKey('graphql_jwt_test_hmac');
    $transcoder = $this->container->get('jwt.transcoder');
    $transcoder->setAlgorithm('HS256');
    $transcoder->setSecret($key->getKeyValue());
    $this->transcoder = $transcoder;
  }

  /**
   * Authenticates user via GraphQL.
   *
   * @param string $username
   *   User name.
   * @param string $password
   *   Password.
   *
   * @return string|bool
   *   JWT authorization token or false.
   */
  protected function authenticate($username, $password) {
    $data = [
      [
        'operationName' => 'JwtToken',
        'variables' => [
          'username' => $username,
          'password' => $password,
        ],
        'query' => $this->query,
      ],
    ];
    /** @var \GuzzleHttp\Client $client */
    $client = $this->getHttpClient();
    $response = $client->post($this->buildUrl('/graphql'), [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'json' => $data,
    ]);
    $data = json_decode((string) $response->getBody(), TRUE);
    return empty($data[0]['data']['JwtToken']['jwt']) ? FALSE : $data[0]['data']['JwtToken']['jwt'];
  }

  /**
   * Tests the authentication via GraphQL.
   *
   * Random user is authenticated via GraphQL and is returned with a JWT auth
   * token (in case of successful authentication). JWT token payload is tested
   * by decoding it and making sure the JWT token belongs to correct user.
   */
  public function testAuth() {
    // Create random user.
    $user = $this->drupalCreateUser();

    // Authenticate as a random user via GraphQL and get JWT token.
    $jwt_token = $this->authenticate($user->getAccountName(), $user->pass_raw);
    // Make sure that correct user was authorized by checking JWT payload.
    $payload = $this->transcoder->decode($jwt_token);
    $uid = $payload->getClaim(['drupal', 'uid']);
    $this->assertEquals($user->id(), $uid);

    // Authenticate again with incorrect password.
    $jwt_token = $this->authenticate($user->getAccountName(), $user->pass_raw . '_make-password-incorrect');
    $this->assertFalse($jwt_token);
  }

}
