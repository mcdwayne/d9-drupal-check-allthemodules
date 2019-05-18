<?php

namespace Drupal\cognito\Aws;

use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * Base implementation.
 */
abstract class CognitoBase implements CognitoInterface {

  /**
   * Apply a Cognito function and capture any possible errors uniformly.
   *
   * @param callable $callback
   *   The cognito API calls to make.
   *
   * @return \Drupal\cognito\Aws\CognitoResult
   *   The cognito result.
   */
  protected function wrap(callable $callback) {
    try {
      $result = $callback();

      // If the callback produced a cognito result already then we don't need
      // to do any wrapping, just pass it straight back.
      if ($result instanceof CognitoResult) {
        return $result;
      }

      if (isset($result['ChallengeName'])) {
        return new CognitoResult($result, NULL, TRUE);
      }

      return new CognitoResult($result);
    }
    catch (CognitoIdentityProviderException $e) {

    }
    catch (\Exception $e) {

    }

    return new CognitoResult(NULL, $e);
  }

}
