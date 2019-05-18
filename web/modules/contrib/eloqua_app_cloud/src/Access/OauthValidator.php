<?php

namespace Drupal\eloqua_app_cloud\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Session\AccountInterface;

/**
 * Validates a request to ensure it actually came from Eloqua. For documentation
 * on how this validation should be performed, see the following...
 *
 * @see http://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAB/Developers/GettingStarted/Authentication/validating-a-call-signature.htm
 */
class OauthValidator implements AccessInterface {

  /**
   * @var RequestContext
   */
  protected $requestContext;

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * OauthValidator constructor.
   * @param RequestContext $request
   * @param ConfigFactoryInterface $config
   */
  public function __construct(RequestContext $request, ConfigFactoryInterface $config) {
    $this->requestContext = $request;
    $this->configFactory = $config;
  }

  /**
   * Returns an access result based on whether or not the request came in with
   * a valid OAuth signature from Eloqua.
   *
   * Note:
   *
   * @param AccountInterface $account
   * @return AccessResult
   */
  public function access(AccountInterface $account = NULL) {
    $params = $this->getParams();

    // This check is only valid for un-authenticated requests. If an authenticated
    // user is trying to hit this, then the Entity Access handler will catch
    // people who do not have access.
    if ($account->isAuthenticated()) {
      return AccessResult::allowed();
    }

    // If we don't even have the params we need, disallow access..
    if (!$this->hasRequisiteParams($params) || !$this->isConfiguredCorrectly()) {
      return AccessResult::forbidden();
    }

    // Otherwise, check all of the pre-conditions.
    $clientIdMatches = $this->matchClientId($params);
    $isValidTimestamp = $this->isValidTimestamp($params);
    $isValidNonce = $this->isValidNonce($params);
    // Hack that allows form POSTs from a form handler to be accepted.
    $hasValidSignatureGet = $this->hasValidSignature($params, 'GET');
    $hasValidSignaturePost = $this->hasValidSignature($params, 'POST');

    // Allow access if all of our pre-conditions check out!
    return AccessResult::allowedIf($clientIdMatches && $isValidTimestamp && $isValidNonce && ($hasValidSignatureGet || $hasValidSignaturePost));
  }

  /**
   * Returns the query parameters for the current request as an associative array.
   * @return array
   */
  protected function getParams() {
    $queryString = $this->requestContext->getQueryString();
    parse_str($queryString, $params);
    return $params;
  }

  /**
   * Returns TRUE if the given array includes all requisite oauth params.
   * @param array $params
   * @return bool
   */
  protected function hasRequisiteParams(array $params) {
    return isset($params['oauth_consumer_key']) &&
      isset($params['oauth_nonce']) &&
      isset($params['oauth_signature_method']) &&
      isset($params['oauth_timestamp']) &&
      isset($params['oauth_version']) &&
      isset($params['oauth_signature']);
  }

  /**
   * Returns TRUE if the site is properly configured with OAuth details.
   * @return bool
   */
  protected function isConfiguredCorrectly() {
    $settings = $this->configFactory->get('eloqua_app_cloud.settings');
    return !empty($settings->get('oauth_client_id')) && !empty($settings->get('oauth_client_id'));
  }

  /**
   * Returns TRUE if the given consumer key matches the configured Client ID.
   * @param array $params
   * @return bool
   */
  protected function matchClientId(array $params) {
    $settings = $this->configFactory->get('eloqua_app_cloud.settings');
    return $params['oauth_consumer_key'] === $settings->get('oauth_client_id');
  }

  /**
   * Returns TRUE if the given OAuth timestamp is within 5 minutes of our own.
   * @param array $params
   * @return bool
   */
  protected function isValidTimestamp(array $params) {
    $now = time();
    return ($now - $params['oauth_timestamp']) < 600;
  }

  /**
   * Returns TRUE if the given nonce value has not been seen in the past 5 mintues.
   * @param array $params
   * @return bool
   */
  protected function isValidNonce(array $params) {
    // @todo Cache nonce values per timestamp for five minutes. Check against cache.
    return TRUE;
  }

  /**
   * Returns TRUE if the current request's OAuth signature can be validated using
   * the configured OAuth Client Secret.
   * @param array $params
   * @return bool
   */
  protected function hasValidSignature(array $params, $overrideMethod = NULL) {
    // Calculate the first "chunk," which is just the request method.
    $chunk1 = $overrideMethod ?: $this->requestContext->getMethod();

    // Calculate the second "chunk," which is the request URL sans params, then
    // URL-encoded.
    $chunk2 = $this->requestContext->getCompleteBaseUrl();
    $chunk2 .= $this->requestContext->getPathInfo();
    $chunk2 = rawurlencode($chunk2);

    // Calculate the third "chunk," which is the query string (excepting the
    // "oauth_signature" param) in alphabetical order (by key), then URL-encoded.
    $cloneParams = $params;
    unset($cloneParams['oauth_signature']);
    ksort($cloneParams);
    // Note: special care here due to the way spaces get encoded vs. how they
    // are actually passed to us by Eloqua (%20 vs. +).
    $paramString = http_build_query($cloneParams, NULL, '%26', PHP_QUERY_RFC3986);
    $chunk3 = str_replace('=', '%3D', $paramString);

    // Calculate the hash message (chunks 1-3 concatenated with ampersands) and
    // hash key (the client secret with an ampersand appended).
    $settings = $this->configFactory->get('eloqua_app_cloud.settings');
    $hashMessage = implode('&', [$chunk1, $chunk2, $chunk3]);
    $hashKey = $settings->get('oauth_client_secret') . '&';

    // Hash the message with the key via sha1 and return the raw output.
    $calculatedSignature = hash_hmac('sha1', $hashMessage, $hashKey, TRUE);

    // Compare the raw signature to the calculated signature.
    return base64_decode($params['oauth_signature']) === $calculatedSignature;
  }

}
