<?php

namespace Drupal\cloudconvert;

use Drupal\cloudconvert\Exceptions\InvalidParameterException;

/**
 * Class Parameters.
 *
 * This manages the parameters for use with CloudConvert requests.
 */
class Parameters {

  /**
   * API Key to authenticate with.
   *
   * @var string
   *   (required)
   */
  protected $apiKey;

  /**
   * The format of the original file.
   *
   * @var string
   *   (required)
   */
  protected $inputFormat;

  /**
   * The format to convert to.
   *
   * @var string
   *   Required for all modes, except info.
   */
  protected $outputFormat;

  /**
   * The mode of the process.
   *
   * @var string
   *   (optional) Defaults to convert, can also be info, combine, archive and
   *   extract.
   */
  protected $mode;

  /**
   * Method of providing the input file.
   *
   * @var string
   *   (required)
   */
  protected $input;

  /**
   * Override the input filename.
   *
   * @var string
   *   (optional)
   */
  protected $filename;

  /**
   * A tag to identify the conversion, can also be an array.
   *
   * @var string|array
   *   (optional)
   */
  protected $tag;

  /**
   * Number of seconds before the conversion is cancelled.
   *
   * @var int
   *   (optional)
   */
  protected $timeout;

  /**
   * A preset of settings that is available to the account on Cloudconvert.com.
   *
   * @var string
   *   (optional)
   */
  protected $preset;

  /**
   * Options for the converter, specific to the chosen options.
   *
   * @var array
   *   (optional)
   */
  protected $converteroptions;

  /**
   * Send email notification after processing.
   *
   * @var bool
   *   (optional)
   */
  protected $email;

  /**
   * The output storage to save the file to.
   *
   * @var string
   *   (optional)
   */
  protected $output;

  /**
   * Parameter to wait till the processing is finished.
   *
   * @var bool
   *   (optional)
   */
  protected $wait;

  /**
   * Callback url to call when Cloudconvert.com is done processing.
   *
   * @var string
   *   (optional)
   */
  protected $callback;

  /**
   * Parameter to wait till the file is processed and downloaded.
   *
   * @var bool|string
   *   (optional)
   */
  protected $download;

  /**
   * Parameter to save the files on Cloudconvert.com.
   *
   * @var bool
   *   (optional)
   */
  protected $save;

  /**
   * Array containing the parameters for the process.
   *
   * @var array
   */
  protected $parameterList = [
    'apikey',
    'inputformat',
    'input',
    'file',
    'files',
    'filename',
    'tag',
    'outputformat',
    'converteroptions',
    'preset',
    'mode',
    'timeout',
    'email',
    'output',
    'callback',
    'wait',
    'download',
    'save',
  ];

  /**
   * Parameters constructor.
   *
   * @param array $parameters
   *   List of parameters.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function __construct(array $parameters = []) {
    $parameterList = $this->parameterList;
    foreach ($parameters as $parameter => $value) {
      if (!\in_array($parameter, $parameterList, TRUE)) {
        continue;
      }
      $this->setParam($parameter, $value);
    }
  }

  /**
   * Set the parameter if its valid.
   *
   * @param string $parameterName
   *   Name of the paramater.
   * @param int|string|array|bool $parameterValue
   *   Value of the parameter.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  protected function setParam($parameterName, $parameterValue) {
    $this->validateParameter($parameterName);
    $this->{$parameterName} = $parameterValue;
  }

  /**
   * Validate the given parameter.
   *
   * @param string $parameterName
   *   Name of the parameter.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  protected function validateParameter($parameterName) {
    $parameterList = $this->parameterList;
    if (!\in_array($parameterName, $parameterList, TRUE)) {
      throw new InvalidParameterException('Parameter is not valid.');
    }
  }

  /**
   * Get the API key parameter.
   *
   * @return string
   *   Api Key.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function getApiKey() {
    return $this->getParam('apikey');
  }

  /**
   * Set the API key parameter.
   *
   * @param string $apiKey
   *   Api Key.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function setApiKey($apiKey) {
    $this->setParam('apikey', $apiKey);
  }

  /**
   * Get a parameter if it is valid.
   *
   * @param string $parameterName
   *   Name of the parameter.
   *
   * @return int|string|array|bool
   *   Value of the parameter.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  protected function getParam($parameterName) {
    $this->validateParameter($parameterName);
    return isset($this->{$parameterName}) ? $this->{$parameterName} : NULL;
  }

  /**
   * Get the input format parameter.
   *
   * @return string
   *   Input Format.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function getInputFormat() {
    return $this->getParam('inputformat');
  }

  /**
   * Set the input format parameter.
   *
   * @param string $inputFormat
   *   Input Format.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function setInputFormat($inputFormat) {
    $this->setParam('inputformat', $inputFormat);
  }

  /**
   * Get the output format parameter.
   *
   * @return string
   *   Output Format.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function getOutputFormat() {
    return $this->getParam('outputformat');
  }

  /**
   * Set the output format parameter.
   *
   * @param string $outputFormat
   *   Output Format.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function setOutputFormat($outputFormat) {
    $this->setParam('outputformat', $outputFormat);
  }

  /**
   * Get the mode parameter.
   *
   * @return string
   *   Mode.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function getMode() {
    return $this->getParam('mode');
  }

  /**
   * Set the mode parameter.
   *
   * @param string $mode
   *   Mode.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function setMode($mode) {
    $this->setParam('mode', $mode);
  }

  /**
   * Get the input parameter.
   *
   * @return string
   *   Input.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function getInput() {
    return $this->getParam('input');
  }

  /**
   * Set the input parameter.
   *
   * @param string $input
   *   Input.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function setInput($input) {
    $this->setParam('input', $input);
  }

  /**
   * Get the filename parameter.
   *
   * @return string
   *   File name.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function getFilename() {
    return $this->getParam('filename');
  }

  /**
   * Set the filename parameter.
   *
   * @param string $filename
   *   File name.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function setFilename($filename) {
    $this->setParam('filename', $filename);
  }

  /**
   * Get the tag parameter.
   *
   * @return string
   *   Tag.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function getTag() {
    return $this->getParam('tag');
  }

  /**
   * Set the tag parameter.
   *
   * @param string $tag
   *   Tag.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function setTag($tag) {
    $this->setParam('tag', $tag);
  }

  /**
   * Get the timeout parameter.
   *
   * @return int
   *   Timeout in seconds.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function getTimeout() {
    return (int) $this->getParam('timeout');
  }

  /**
   * Set the timeout parameter.
   *
   * @param int $timeout
   *   Timeout in seconds.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function setTimeout($timeout) {
    $this->setParam('timeout', (int) $timeout);
  }

  /**
   * Get the preset parameter.
   *
   * @return string
   *   Preset.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function getPreset() {
    return $this->getParam('preset');
  }

  /**
   * Set the preset parameter.
   *
   * @param string $preset
   *   Preset.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function setPreset($preset) {
    $this->setParam('preset', $preset);
  }

  /**
   * Get the convertoptions parameter.
   *
   * @return array
   *   Convert options.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function getConverterOptions() {
    return $this->getParam('converteroptions');
  }

  /**
   * Set the convertoptions parameters.
   *
   * @param array $converteroptions
   *   Convert Options.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function setConverterOptions(array $converteroptions) {
    $this->setParam('converteroptions', $converteroptions);
  }

  /**
   * Get a single convert option.
   *
   * @param string $optionName
   *   Single convert option name.
   *
   * @return int|string|bool
   *   Setting for the Option.
   */
  public function getConverterOption($optionName) {
    return $this->converteroptions[$optionName];
  }

  /**
   * Set a single convert option.
   *
   * @param string $optionName
   *   Single convert option name.
   * @param int|string|bool $setting
   *   Setting for the Option.
   */
  public function setConverterOption($optionName, $setting) {
    $this->converteroptions[$optionName] = $setting;
  }

  /**
   * Get the email parameter.
   *
   * @return string
   *   Email.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function getEmail() {
    return $this->getParam('email');
  }

  /**
   * Set the email parameter.
   *
   * @param string $email
   *   Email.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function setEmail($email) {
    $this->setParam('email', $email);
  }

  /**
   * Get the output parameter.
   *
   * @return array
   *   Output.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function getOutput() {
    return $this->getParam('output');
  }

  /**
   * Set the output parameter.
   *
   * @param array $output
   *   Output.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function setOutput($output) {
    $this->setParam('output', $output);
  }

  /**
   * Get the wait parameter.
   *
   * @return bool
   *   TRUE if wait is on.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function getWait() {
    return $this->getParam('wait');
  }

  /**
   * Set the wait parameter.
   *
   * @param bool $wait
   *   TRUE if wait is on.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function setWait($wait) {
    $this->setParam('wait', $wait);
  }

  /**
   * Get the callback parameter.
   *
   * @return string
   *   Callback url.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function getCallback() {
    return $this->getParam('callback');
  }

  /**
   * Set the callback parameter.
   *
   * @param string $callback
   *   Callback url.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function setCallback($callback) {
    $this->setParam('callback', $callback);
  }

  /**
   * Get the download parameter.
   *
   * @return bool|string
   *   Download mode: 'Inline', TRUE or FALSE.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function getDownload() {
    return $this->getParam('download');
  }

  /**
   * Set the download parameter.
   *
   * @param bool|string $download
   *   Download mode: 'Inline', TRUE or FALSE.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function setDownload($download) {
    $this->setParam('download', $download);
  }

  /**
   * Get the save parameter.
   *
   * @return bool
   *   TRUE if saving on Cloud Convert is needed.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function getSave() {
    return $this->getParam('save');
  }

  /**
   * Set the save parameter.
   *
   * @param bool $save
   *   TRUE if saving on Cloud Convert is needed.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function setSave($save) {
    $this->setParam('save', $save);
  }

  /**
   * Get the parameters.
   *
   * @return array
   *   List of parameters.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function getParameters() {
    $parameterList = $this->parameterList;
    $parameters = [];
    foreach ($parameterList as $parameterName) {
      $parameterValue = $this->getParam($parameterName);
      if (NULL === $parameterValue) {
        continue;
      }
      $parameters[$parameterName] = $parameterValue;
    }

    return $parameters;
  }

}
