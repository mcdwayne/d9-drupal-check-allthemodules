<?php

namespace Drupal\Tests\lti_tool_provider\Functional;

use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Driver\GoutteDriver;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\lti_tool_provider\Entity\Consumer;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\UserStorageInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use OAuth;
use OAuthException;
use Psr\Http\Message\ResponseInterface;

/**
 * Functional tests for LTI authentication.
 *
 * @group basic_auth
 */
class LTIAuth extends BrowserTestBase
{
    /**
     * @var array
     */
    public static $modules = ['lti_tool_provider'];

    /**
     * @var UserStorageInterface
     */
    public $userStorage;

    /**
     * @var EntityStorageInterface
     */
    public $consumerStorage;

    /**
     * @var EntityStorageInterface
     */
    public $nonceStorage;

    /**
     * @var Consumer
     */
    public $consumer;

    /**
     * @throws EntityStorageException
     */
    protected function setUp()
    {
        parent::setUp();

        if (!class_exists('\Oauth')) {
            $this->markTestSkipped('Missing OAuth PECL extension, skipping test.');
        }

        $this->userStorage = $this->container->get('entity_type.manager')->getStorage('user');
        $this->consumerStorage = $this->container->get('entity_type.manager')->getStorage('lti_tool_provider_consumer');
        $this->nonceStorage = $this->container->get('entity_type.manager')->getStorage('lti_tool_provider_nonce');

        $this->consumer = $this->consumerStorage->create(
            [
                'consumer' => 'consumer',
                'consumer_key' => 'consumer_key',
                'consumer_secret' => 'consumer_secret',
                'name' => 'lis_person_contact_email_primary',
                'mail' => 'lis_person_contact_email_primary',
            ]
        );

        $this->consumer->save();
    }

    /**
     * Test authentication with a missing signature.
     * @throws OAuthException
     * @throws GuzzleException
     */
    public function testMissingOauthSignature()
    {
        $oauth = new OAuth(
            $this->consumer->get('consumer_key')->value,
            $this->consumer->get('consumer_secret')->value,
            OAUTH_SIG_METHOD_HMACSHA1,
            OAUTH_AUTH_TYPE_URI
        );
        $timestamp = time();
        $nonce = md5($timestamp);
        $oauth->setTimestamp($timestamp);
        $oauth->setNonce($nonce);

        $url = Url::fromRoute('lti_tool_provider.lti');
        $params = [
            'oauth_version' => '1.0',
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_consumer_key' => 'consumer_key',
            'oauth_timestamp' => $timestamp,
            'oauth_nonce' => $nonce,
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p0',
            'resource_link_id' => 'resource_link_id',
            'lis_person_contact_email_primary' => '',
        ];

        $response = $this->request('POST', $url, ['form_params' => $params]);

//        $userStorage = $this->container->get('entity_type.manager')->getStorage('user');
        $ids = $this->userStorage->getQuery()
            ->condition('name', 'ltiuser', '=')
            ->condition('mail', 'ltiuser@invalid', '=')
            ->execute();

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(0, count($ids));
    }

    /**
     * Test authentication with outdated timestamp.
     * @throws OAuthException
     * @throws GuzzleException
     */
    public function testOutdatedTimestamp()
    {
        $oauth = new OAuth(
            $this->consumer->get('consumer_key')->value,
            $this->consumer->get('consumer_secret')->value,
            OAUTH_SIG_METHOD_HMACSHA1,
            OAUTH_AUTH_TYPE_URI
        );
        $timestamp = time() - LTI_TOOL_PROVIDER_NONCE_INTERVAL - 300;
        $nonce = md5($timestamp);
        $oauth->setTimestamp($timestamp);
        $oauth->setNonce($nonce);

        $url = Url::fromRoute('lti_tool_provider.lti');
        $params = [
            'oauth_version' => '1.0',
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_consumer_key' => 'consumer_key',
            'oauth_timestamp' => $timestamp,
            'oauth_nonce' => $nonce,
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p0',
            'resource_link_id' => 'resource_link_id',
            'lis_person_contact_email_primary' => '',
        ];

        $signature = $oauth->generateSignature('POST', $url->setAbsolute()->toString(), $params);
        $params['oauth_signature'] = $signature;
        $response = $this->request('POST', $url, ['form_params' => $params]);

//        $userStorage = $this->container->get('entity_type.manager')->getStorage('user');
        $ids = $this->userStorage->getQuery()
            ->condition('name', 'ltiuser', '=')
            ->condition('mail', 'ltiuser@invalid', '=')
            ->execute();

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(0, count($ids));
    }

    /**
     * Test authentication with duplicate nonce.
     * @throws OAuthException
     * @throws EntityStorageException
     * @throws GuzzleException
     */
    public function testDuplicateNonce()
    {
        $oauth = new OAuth(
            $this->consumer->get('consumer_key')->value,
            $this->consumer->get('consumer_secret')->value,
            OAUTH_SIG_METHOD_HMACSHA1,
            OAUTH_AUTH_TYPE_URI
        );
        $timestamp = time();
        $nonce = md5($timestamp);
        $oauth->setTimestamp($timestamp);
        $oauth->setNonce($nonce);

        $this->nonceStorage->create(
            [
                'nonce' => $nonce,
                'consumer_key' => $this->consumer->get('consumer_key')->value,
                'timestamp' => $timestamp,
            ]
        )->save();

        $url = Url::fromRoute('lti_tool_provider.lti');
        $params = [
            'oauth_version' => '1.0',
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_consumer_key' => 'consumer_key',
            'oauth_timestamp' => $timestamp,
            'oauth_nonce' => $nonce,
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p0',
            'resource_link_id' => 'resource_link_id',
            'lis_person_contact_email_primary' => '',
        ];

        $signature = $oauth->generateSignature('POST', $url->setAbsolute()->toString(), $params);
        $params['oauth_signature'] = $signature;
        $response = $this->request('POST', $url, ['form_params' => $params]);

//        $userStorage = $this->container->get('entity_type.manager')->getStorage('user');
        $ids = $this->userStorage->getQuery()
            ->condition('name', 'ltiuser', '=')
            ->condition('mail', 'ltiuser@invalid', '=')
            ->execute();

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(0, count($ids));
    }

    /**
     * Test successful authentication with ltiuser (no email).
     * @throws OAuthException
     * @throws GuzzleException
     */
    public function testSuccessfulAuthenticationLtiUser()
    {
        $oauth = new OAuth(
            $this->consumer->get('consumer_key')->value,
            $this->consumer->get('consumer_secret')->value,
            OAUTH_SIG_METHOD_HMACSHA1,
            OAUTH_AUTH_TYPE_URI
        );
        $timestamp = time();
        $nonce = md5($timestamp);
        $oauth->setTimestamp($timestamp);
        $oauth->setNonce($nonce);

        $url = Url::fromRoute('lti_tool_provider.lti');
        $params = [
            'oauth_version' => '1.0',
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_consumer_key' => 'consumer_key',
            'oauth_timestamp' => $timestamp,
            'oauth_nonce' => $nonce,
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p0',
            'resource_link_id' => 'resource_link_id',
            'lis_person_contact_email_primary' => '',
        ];

        $signature = $oauth->generateSignature('POST', $url->setAbsolute()->toString(), $params);
        $params['oauth_signature'] = $signature;
        $response = $this->request('POST', $url, ['form_params' => $params]);

//        $userStorage = $this->container->get('entity_type.manager')->getStorage('user');
        $ids = $this->userStorage->getQuery()
            ->condition('name', 'ltiuser', '=')
            ->condition('mail', 'ltiuser@invalid', '=')
            ->execute();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, count($ids));
    }

    /**
     * Test successful authentication and account creation with new user.
     * @throws OAuthException
     * @throws GuzzleException
     */
    public function testSuccessfulAuthenticationNewUser()
    {
        $oauth = new OAuth(
            $this->consumer->get('consumer_key')->value,
            $this->consumer->get('consumer_secret')->value,
            OAUTH_SIG_METHOD_HMACSHA1,
            OAUTH_AUTH_TYPE_URI
        );
        $timestamp = time();
        $nonce = md5($timestamp);
        $oauth->setTimestamp($timestamp);
        $oauth->setNonce($nonce);

        $url = Url::fromRoute('lti_tool_provider.lti');
        $params = [
            'oauth_version' => '1.0',
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_consumer_key' => 'consumer_key',
            'oauth_timestamp' => $timestamp,
            'oauth_nonce' => $nonce,
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p0',
            'resource_link_id' => 'resource_link_id',
            'lis_person_contact_email_primary' => 'user@lms.edu',
        ];

        $signature = $oauth->generateSignature('POST', $url->setAbsolute()->toString(), $params);
        $params['oauth_signature'] = $signature;
        $response = $this->request('POST', $url, ['form_params' => $params]);

//        $userStorage = $this->container->get('entity_type.manager')->getStorage('user');
        $ids = $this->userStorage->getQuery()
            ->condition('name', 'user@lms.edu', '=')
            ->condition('mail', 'user@lms.edu', '=')
            ->execute();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, count($ids));
    }

    /**
     * Test successful authentication with existing user.
     * @throws OAuthException
     * @throws EntityStorageException
     * @throws GuzzleException
     */
    public function testSuccessfulAuthenticationExistingUser()
    {
        $oauth = new OAuth(
            $this->consumer->get('consumer_key')->value,
            $this->consumer->get('consumer_secret')->value,
            OAUTH_SIG_METHOD_HMACSHA1,
            OAUTH_AUTH_TYPE_URI
        );
        $timestamp = time();
        $nonce = md5($timestamp);
        $oauth->setTimestamp($timestamp);
        $oauth->setNonce($nonce);

        $mail = 'user@lms.edu';

        $user = User::create();
        $user->setUsername($mail);
        $user->setEmail($mail);
        $user->setPassword(user_password());
        $user->enforceIsNew();
        $user->activate();
        $user->save();

        $url = Url::fromRoute('lti_tool_provider.lti');
        $params = [
            'oauth_version' => '1.0',
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_consumer_key' => 'consumer_key',
            'oauth_timestamp' => $timestamp,
            'oauth_nonce' => $nonce,
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p0',
            'resource_link_id' => 'resource_link_id',
            'lis_person_contact_email_primary' => $mail,
        ];

        $signature = $oauth->generateSignature('POST', $url->setAbsolute()->toString(), $params);
        $params['oauth_signature'] = $signature;
        $response = $this->request('POST', $url, ['form_params' => $params]);

        $ids = $this->userStorage->getQuery()
            ->condition('name', $mail, '=')
            ->condition('mail', $mail, '=')
            ->execute();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, count($ids));
    }

    /**
     * Performs a HTTP request. Wraps the Guzzle HTTP client.
     *
     * Why wrap the Guzzle HTTP client? Because we want to keep the actual test
     * code as simple as possible, and hence not require them to specify the
     * 'http_errors = FALSE' request option, nor do we want them to have to
     * convert Drupal Url objects to strings.
     *
     * We also don't want to follow redirects automatically, to ensure these tests
     * are able to detect when redirects are added or removed.
     *
     * @param string $method
     *   HTTP method.
     * @param Url $url
     *   URL to request.
     * @param array $request_options
     *   Request options to apply.
     *
     * @return ResponseInterface
     *   The response.
     *
     * @throws Exception
     * @throws GuzzleException
     * @see \GuzzleHttp\ClientInterface::request()
     */
    protected function request($method, Url $url, array $request_options)
    {
        $request_options[RequestOptions::HTTP_ERRORS] = false;
        // $request_options[RequestOptions::ALLOW_REDIRECTS] = FALSE;.
        $request_options = $this->decorateWithXdebugCookie($request_options);

        $driver = $this->getSession()->getDriver();
        if ($driver instanceof GoutteDriver) {
            return $driver->getClient()->getClient()->request($method, $url->setAbsolute(true)->toString(), $request_options);
        }

        throw new Exception('Goutte driver missing.');
    }

    /**
     * Adds the Xdebug cookie to the request options.
     *
     * @param array $request_options
     *   The request options.
     *
     * @return array
     *   Request options updated with the Xdebug cookie if present.
     */
    protected function decorateWithXdebugCookie(array $request_options)
    {
        $session = $this->getSession();
        $driver = $session->getDriver();
        if ($driver instanceof BrowserKitDriver) {
            $client = $driver->getClient();
            foreach ($client->getCookieJar()->all() as $cookie) {
                if (isset($request_options[RequestOptions::HEADERS]['Cookie'])) {
                    $request_options[RequestOptions::HEADERS]['Cookie'] .= '; ' . $cookie->getName() . '=' . $cookie->getValue();
                }
                else {
                    $request_options[RequestOptions::HEADERS]['Cookie'] = $cookie->getName() . '=' . $cookie->getValue();
                }
            }
        }

        return $request_options;
    }

}
