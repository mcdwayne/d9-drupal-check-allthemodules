<?php

namespace Drupal\feeds_s3_fetcher\Feeds\Fetcher;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Drupal\Core\Site\Settings;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Fetcher\HttpFetcher;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\StateInterface;

/**
 * Defines an S3 Bucket fetcher.
 *
 * @FeedsFetcher(
 *   id = "s3bucket",
 *   title = @Translation("Download from S3 Bucket"),
 *   description = @Translation("Downloads data from an S3 Bucket using Drupal's HTTP request handler."),
 *   form = {
 *     "feed" = "Drupal\feeds_s3_fetcher\Feeds\Form\S3BucketFetcherFeedForm",
 *   },
 *   arguments = {"@http_client", "@cache.feeds_download", "@file_system"}
 * )
 */
class S3BucketFetcher extends HttpFetcher implements ClearableInterface, FetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed, StateInterface $state) {

    $feed_config = $feed->getConfigurationFor($this);
    $bucket = $feed_config['bucket'];
    $keyname = $feed_config['keyname'];
    $region = $feed_config['region'];

    $client_config = [];
    $client_config['version'] = 'latest';
    $client_config['region'] = $region;

    $access_key = Settings::get('feeds_s3_fetcher.access_key', $_ENV['AWS_ACCESS_KEY_ID']);
    $secret_key = Settings::get('feeds_s3_fetcher.secret_key',$_ENV['AWS_SECRET_ACCESS_KEY']);

    if ($access_key != '' && $secret_key != '') {
      $client_config['credentials'] = [
        'key' => $access_key,
        'secret' => $secret_key,
      ];
    } else {
      throw new \RuntimeException($this->t('AWS Access credentials not set.'));
    }

    try {
      $s3 = new S3Client($client_config);

      $fileObject = $s3->getObject([
        'Bucket' => $bucket,
        'Key' => $keyname
      ]);
    } catch (S3Exception $e) {
      throw new \RuntimeException($this->t('AWS Exception: @message ', ['@message' => $e->getMessage()]));
    }

    $filePath = $this->fileSystem->tempnam('temporary://', 'feeds_s3_fetcher');
    $filePath = $this->fileSystem->realpath($filePath);

    $fileContent = $fileObject['Body'];

    if (file_put_contents($filePath, $fileContent) === FALSE) {
      throw new \RuntimeException($this->t('Unable to write file to %file.', ['%file' => $filePath]));
    }

    return new FetcherResult($filePath);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultFeedConfiguration() {
    return [
      'region' => '',
      'bucket' => '',
      'keyname' => '',
    ];
  }

}
