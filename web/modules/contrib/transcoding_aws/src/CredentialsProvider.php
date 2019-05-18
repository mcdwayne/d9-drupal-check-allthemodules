<?php

namespace Drupal\transcoding_aws;

use Aws\Credentials\Credentials;
use Aws\Exception\CredentialsException;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\RejectedPromise;

class CredentialsProvider {

  /**
   * Return a credential provider.
   *
   * @see https://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/credentials.html#creating-a-custom-provider
   * @return \Closure
   */
  public static function fromKey($keyName = 'aws') {
    return function () use ($keyName) {
      // This is returned as a function so we can't use standard
      // dependency injection.
      /** @var \Drupal\key\KeyRepositoryInterface $keyRepository */
      $keyRepository = \Drupal::service('key.repository');
      $config = $keyRepository->getKey($keyName)->getKeyValues();
      if ($config['key'] && $config['secret']) {
        return Promise\promise_for(
          new Credentials($config['key'], $config['secret'])
        );
      }

      $msg = 'Could not load AWS credentials from Drupal key.';
      return new RejectedPromise(new CredentialsException($msg));
    };
  }

}
