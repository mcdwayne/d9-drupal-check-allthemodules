<?php

namespace Drupal\sms_routesms\Plugin\SmsGateway\RouteSms;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageResultStatus;
use Drupal\sms_gateway_base\Plugin\SmsGateway\ResponseHandlerInterface;

/**
 * Normalizes responses and reports from RouteSMS gateway.
 */
class MessageResponseHandler implements ResponseHandlerInterface {

  /**
   * The recipients of the message response.
   *
   * @var array
   */
  protected $recipients;

  /**
   * Creates a new message response handler for the specified recipients
   *
   * @param array $recipients
   *   The recipients to which the messages were sent.
   */
  public function __construct(array $recipients) {
    $this->recipients = $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function handle($body) {
    $result = new SmsMessageResult();
    if ($body) {
      // Sample response formats.
      // 1701|2348055494143|bc5f7425-c98c-445b-a1f7-4fc5e2acef7e,
      // 1701|2348134496448|5122f879-2ba7-4469-8ae2-4091267ef389,
      // 1701|2349876543|20cef313-1660-4b92-baa5-1b7ba45256a5
      // 1701|2348055494143|3023779f-1722-4b7b-a3c8-d81f9e4bfc32,1706|23405
      // 1704|2348055494143,1704|23405,1704|234509
      // 1701|2348055494143,1706|23405,1706|234509
      // 1706|23405,1707|2348055494143
      // Check for RouteSMS errors.
      $results = explode(',', $body);
      // Assume 4-digit codes.
      $first_code = substr($results[0], 0, 4);
      if (($error = $this->messageError($first_code)) !== FALSE) {
        $result
          ->setError($error['code'])
          ->setErrorMessage($error['description']);
      }
      else {
        // Message Submitted Successfully, in this case response format is:
        // 1701|<CELL_NO>|{<MESSAGE ID>},<ERROR CODE>|<CELL_NO>|{<MESSAGE ID>},...
        $reports = [];
        foreach ($results as $data) {
          $info = explode('|', $data);
          $error = $this->reportError($info[0]);
          $reports[$info[1]] = (new SmsDeliveryReport())
            ->setRecipient($info[1])
            ->setStatus($error ? $error['code'] : SmsMessageReportStatus::QUEUED)
            ->setMessageId(isset($info[2]) ? $info[2] : '')
            ->setStatusMessage($error ? $error['description'] : '')
            ->setStatusTime(REQUEST_TIME);
        }
        // If reports are less than the original numbers, then most likely
        // credits got finished half-way through.
        if (count($reports) < count($this->recipients)) {
          foreach (array_diff($this->recipients, array_keys($reports)) as $recipient) {
            $reports[$recipient] = (new SmsDeliveryReport())
              ->setRecipient($recipient)
              // Still use the last $error and last $info objects.
              ->setStatus($error ? $error['code'] : SmsMessageReportStatus::QUEUED)
              ->setStatusMessage($error ? $error['description'] : '')
              ->setStatusTime(REQUEST_TIME);
          }
        }
        $result
          ->setErrorMessage(new TranslatableMarkup('Message submitted successfully'))
          ->setReports($reports);
      }
    }
    return $result;
  }

  /**
   * Checks if there is a message error based on the response code supplied.
   *
   * @param string $code
   *   The response code.
   *
   * @return array|false
   *   Returns FALSE if there is no error, otherwise it returns an array with the
   *   error code (number or text) and description if there is an error.
   */
  protected function messageError($code) {
    $error_descriptions = self::getMessageErrorCodes();
    return array_key_exists($code, $error_descriptions)
      ? ['code' => self::$messageErrorMap[$code], 'description' => $error_descriptions[$code]]
      : FALSE;
  }

  /**
   * Checks if there is a message report error based on the response code supplied.
   *
   * @param string $code
   *   The response code.
   *
   * @return array|false
   *   Returns FALSE if there is no error, otherwise it returns an array with the
   *   error code (number or text) and description if there is an error.
   */
  protected function reportError($code) {
    $error_descriptions = self::getReportErrorCodes();
    return array_key_exists($code, $error_descriptions)
      ? ['code' => self::$reportErrorMap[$code], 'description' => $error_descriptions[$code]]
      : FALSE;
  }

  /**
   * Returns the possible error codes and messages from the gateway.
   *
   * @return array
   *   An array of the possible error codes and corresponding messages.
   */
  protected static function getMessageErrorCodes() {
    return [
      '1702' => new TranslatableMarkup('Invalid URL Error, This means that one of the parameters was not provided or left blank'),
      '1703' => new TranslatableMarkup('Invalid value in username or password field'),
      '1704' => new TranslatableMarkup('Invalid value in "type" field'),
      '1705' => new TranslatableMarkup('Invalid Message'),
      '1707' => new TranslatableMarkup('Invalid Source (Sender)'),
      '1708' => new TranslatableMarkup('Invalid value for "dlr" field'),
      '1709' => new TranslatableMarkup('User validation failed'),
      '1710' => new TranslatableMarkup('Internal Error'),
    ];
  }

  /**
   * Mapping of RouteSMS's error codes to SMS Framework's error codes.
   *
   * @var array
   */
  protected static $messageErrorMap = [
    '1702' => SmsMessageResultStatus::PARAMETERS,
    '1703' => SmsMessageResultStatus::PARAMETERS,
    '1704' => SmsMessageResultStatus::PARAMETERS,
    '1705' => SmsMessageReportStatus::CONTENT_INVALID,
    '1707' => SmsMessageResultStatus::INVALID_SENDER,
    '1708' => SmsMessageResultStatus::PARAMETERS,
    '1709' => SmsMessageResultStatus::AUTHENTICATION,
    '1710' => SmsMessageResultStatus::ERROR,
  ];

  /**
   * Returns possible delivery report error codes and messages from the gateway.
   *
   * @return array
   *   An array of the possible error codes and corresponding messages for
   *  individual delivery reports.
   */
  protected static function getReportErrorCodes() {
    return [
      '1706' => new TranslatableMarkup('Invalid Destination'),
      '1025' => new TranslatableMarkup('Insufficient Credit'),
    ];
  }

  /**
   * Mapping of RouteSMS's delivery report error codes to SMS Framework's codes.
   *
   * @var array
   */
  protected static $reportErrorMap = [
    '1706' => SmsMessageReportStatus::INVALID_RECIPIENT,
    '1025' => SmsMessageResultStatus::NO_CREDIT,
  ];

}
