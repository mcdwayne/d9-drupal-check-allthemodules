<?php

namespace Drupal\cloudconvert;

use Drupal\cloudconvert\Exceptions\ApiException;

/**
 * CloudConvert Process Wrapper.
 */
class Process extends ApiObject {

  /**
   * The identifier for the process at CloudConvert.com.
   *
   * @var string
   */
  protected $processId;

  /**
   * Construct a new Process instance.
   *
   * @param Api $api
   *   Cloud Convert API.
   * @param string $processId
   *   Cloud Convert Process ID.
   * @param string $url
   *   The Process URL.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function __construct(Api $api, $processId, $url) {
    parent::__construct($api, $url);
    $this->processId = $processId;
    $this->refresh();
  }

  /**
   * Get the Cloud Convert Process ID.
   *
   * @return string
   *   Cloud Convert Process ID.
   */
  public function getProcessId() {
    return $this->processId;
  }

  /**
   * Starts the Process.
   *
   * @param \Drupal\cloudconvert\Parameters $parameters
   *   Parameters for creating the Process.
   *   See https://cloudconvert.com/apidoc#start.
   *
   * @return \Drupal\cloudconvert\Process
   *   Cloud Convert Process.
   *
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function start(Parameters $parameters = NULL) {
    $this->data = $this->api->post($this->url, $parameters->getParameters(), FALSE);
    return $this;
  }

  /**
   * Uploads the input file.
   *
   * See https://cloudconvert.com/apidoc#upload.
   *
   * @param resource $stream
   *   File resource stream.
   * @param string $filename
   *   Filename of the input file.
   *
   * @return \Drupal\cloudconvert\Process
   *   Cloud Convert Process.
   *
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   */
  public function upload($stream, $filename = NULL) {
    if (!isset($this->upload->url)) {
      throw new ApiException('Upload is not allowed in this process state', 400);
    }

    if (NULL === $filename) {
      $metadata = stream_get_meta_data($stream);
      $filename = basename($metadata['uri']);
    }
    $this->api->put($this->upload->url . '/' . rawurlencode($filename), $stream, FALSE);
    return $this;
  }

  /**
   * Waits for the Process to finish (or end with an error).
   *
   * @return \Drupal\cloudconvert\ApiObject
   *   Cloud Convert API Object.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function wait() {
    if ($this->step === 'finished' || $this->step === 'error') {
      return $this;
    }

    $parameters = new Parameters(['wait' => TRUE]);

    return $this->refresh($parameters);
  }

  /**
   * Download all files from cloudconvert.
   *
   * @param string $destinationDirectory
   *   Destination.
   *
   * @return \Drupal\cloudconvert\Process
   *   Cloud Convert Process.
   *
   * @throws \RuntimeException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   */
  public function downloadAllFiles($destinationDirectory) {
    $output = $this->get('output');
    foreach ($output->files as $file) {
      $this->downloadFile($destinationDirectory . '/' . $file, $output->url . '/' . $file);
    }

    return $this;
  }

  /**
   * Download a file from cloudconvert.
   *
   * @param string $fileDestination
   *   File destination.
   * @param string $outputUrl
   *   Output url.
   *
   * @return \Drupal\cloudconvert\Process
   *   Cloud Convert Process.
   *
   * @throws \RuntimeException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   */
  public function downloadFile($fileDestination, $outputUrl) {
    return $this->downloadStream(fopen($fileDestination, 'w'), $outputUrl);
  }

  /**
   * Set up a download stream with cloudconvert.
   *
   * @param resource $stream
   *   File resource stream.
   * @param string $outputUrl
   *   Output url.
   *
   * @return \Drupal\cloudconvert\Process
   *   Cloud Convert Process.
   *
   * @throws \RuntimeException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   */
  public function downloadStream($stream, $outputUrl) {
    $local = \GuzzleHttp\Psr7\stream_for($stream);
    $download = $this->api->get($outputUrl, FALSE, FALSE);
    $local->write($download);
    return $this;
  }

  /**
   * Delete Process from API.
   *
   * @return \Drupal\cloudconvert\Process
   *   Cloud Convert Process.
   *
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   */
  public function delete() {
    $this->api->delete($this->url, FALSE, FALSE);
    return $this;
  }

}
