<?php

namespace Drupal\Tests\cognito\Unit;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\Result;
use Drupal\cognito\Aws\Cognito;
use Drupal\cognito\Aws\CognitoResult;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;

/**
 * Test the cognito service.
 *
 * @group cognito
 */
class CognitoTest extends UnitTestCase {

  /**
   * Test the authorize method.
   */
  public function testAuthorize() {
    $client = $this->getMockBuilder(CognitoIdentityProviderClient::class)
      ->setMethods(['adminInitiateAuth'])
      ->disableOriginalConstructor()
      ->getMock();
    $client
      ->method('adminInitiateAuth')
      ->willReturn(new Result([
        'AuthenticationResult' => [
          'AccessToken' => '123-45',
          'IdToken' => '123',
        ],
      ]));

    $cognito = $this->getMockBuilder(Cognito::class)
      ->setConstructorArgs([$client, '', '', new Client()])
      ->setMethods(['validateToken'])
      ->getMock();
    $cognito->method('validateToken')->willReturn(TRUE);

    $result = $cognito->authorize('user', 'pass');
    $this->assertInstanceOf(CognitoResult::class, $result);
    $this->assertFalse($result->hasError());
  }

  /**
   * Test authorize request that throws an error.
   */
  public function testAuthorizeWithError() {
    $client = $this->getMockBuilder(CognitoIdentityProviderClient::class)
      ->setMethods(['adminInitiateAuth'])
      ->disableOriginalConstructor()
      ->getMock();

    $client
      ->method('adminInitiateAuth')
      ->willThrowException(new \Exception('Failed to authorize'));

    $cognito = new Cognito($client, '', '', new Client());
    $result = $cognito->authorize('user', 'pass');

    $this->assertTrue($result->hasError());
    $this->assertEquals('Failed to authorize', $result->getError());
  }

  /**
   * Test authorize with invalid token.
   */
  public function testAuthorizeWithInvalidToken() {
    $client = $this->getMockBuilder(CognitoIdentityProviderClient::class)
      ->setMethods(['adminInitiateAuth'])
      ->disableOriginalConstructor()
      ->getMock();
    $client
      ->method('adminInitiateAuth')
      ->willReturn(new Result([
        'AuthenticationResult' => [
          'AccessToken' => '123-45',
          'IdToken' => '123',
        ],
      ]));

    $cognito = $this->getMockBuilder(Cognito::class)
      ->setConstructorArgs([$client, '', '', new Client()])
      ->setMethods(['validateToken'])
      ->getMock();
    $cognito->method('validateToken')->willReturn(FALSE);

    $result = $cognito->authorize('user', 'pass');

    $this->assertTrue($result->hasError());
    $this->assertEquals('Token failed to validate', $result->getError());
  }

  /**
   * Test admin respond to challenge.
   */
  public function testAdminRespondToChallenge() {
    $client = $this->getMockBuilder(CognitoIdentityProviderClient::class)
      ->setMethods(['adminRespondToAuthChallenge'])
      ->disableOriginalConstructor()
      ->getMock();
    $client
      ->method('adminRespondToAuthChallenge')
      ->willReturn([]);

    $cognito = new Cognito($client, '', '', new Client());
    $result = $cognito->adminRespondToNewPasswordChallenge('user', 'ChallengeType', 'ChallengeAnswer', 'SessionId');

    $this->assertInstanceOf(CognitoResult::class, $result);
  }

  /**
   * Call any method but trigger an exception to ensure it's wrapped correctly.
   */
  public function testWrapPassesThroughException() {
    $client = $this->getMockBuilder(CognitoIdentityProviderClient::class)
      ->setMethods(['signUp'])
      ->disableOriginalConstructor()
      ->getMock();
    $client
      ->method('signUp')
      ->willThrowException(new \Exception('Failed to sign up'));

    $cognito = new Cognito($client, '', '', new Client());
    $result = $cognito->signUp('', '', '');

    $this->assertTrue($result->hasError());
    $this->assertEquals('Failed to sign up', $result->getError());
  }

  /**
   * Test admin initiate auth with a challenge.
   */
  public function testWrapHandlesChallenges() {
    $client = $this->getMockBuilder(CognitoIdentityProviderClient::class)
      ->setMethods(['adminInitiateAuth'])
      ->disableOriginalConstructor()
      ->getMock();
    $client
      ->method('adminInitiateAuth')
      ->willReturn(new Result([
        'ChallengeName' => 'MyChallenge',
      ]));

    // Force validateToken() to always return true.
    $cognito = $this->getMockBuilder(Cognito::class)
      ->setConstructorArgs([$client, '', '', new Client()])
      ->setMethods(['validateToken'])
      ->getMock();
    $cognito->method('validateToken')->willReturn(TRUE);

    $result = $cognito->authorize('user', 'pass');

    $this->assertTrue($result->isChallenge());
  }

}
