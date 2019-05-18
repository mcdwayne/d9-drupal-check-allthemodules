<?php

namespace Drupal\inmail\Plugin\inmail\Analyzer;

use Drupal\inmail\DSNStatus;
use Drupal\inmail\MIME\MimeDSNEntity;
use Drupal\inmail\MIME\MimeMessageInterface;
use Drupal\inmail\ProcessorResultInterface;

/**
 * Identifies standard Delivery Status Notification (DSN) messages.
 *
 * This analyzer parses headers and multipart message parts according to the
 * standards defined in
 * @link http://tools.ietf.org/html/rfc3464 RFC 3464 @endlink. It aims to
 * identify:
 *   - whether the message is a bounce message or not,
 *   - the bounce status code reported by the mail server, and
 *   - the indented recipient of the message that bounced.
 *
 * This analyzer will likely fail to identify non-standard messages. This
 * behaviour is intended for the sake of simplicity; other analyzers may be
 * enabled to accomplish more reliable bounce message classification.
 *
 * @ingroup analyzer
 *
 * @Analyzer(
 *   id = "dsn",
 *   label = @Translation("Standard DSN Analyzer")
 * )
 */
class StandardDSNAnalyzer extends AnalyzerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(MimeMessageInterface $message, ProcessorResultInterface $processor_result) {
    if (!$message instanceof MimeDSNEntity) {
      return;
    }

    /** @var \Drupal\inmail\DefaultAnalyzerResult $result */
    $result = $processor_result->getAnalyzerResult();
    $bounce_data = $result->ensureContext('bounce', 'inmail_bounce');

    // @todo Store date for bounces https://www.drupal.org/node/2379923
    // Iterate over per-recipient field groups in the DSN.
    $index = 0;
    while ($fields = $message->getPerRecipientFields($index++)) {
      // Parse the 'Status:' field, having the format X.XXX.XXX.
      $subcodes = explode('.', $fields->getFieldBody('Status'));
      if (count($subcodes) == 3) {
        $bounce_data->setStatusCode(new DSNStatus($subcodes[0], $subcodes[1], $subcodes[2]));
      }

      // Extract address from the 'Final-Recipient:' field, which has the format
      // "type; address".
      $field_parts = preg_split('/;\s*/', $fields->getFieldBody('Final-Recipient'));
      if (count($field_parts) == 2) {
        $bounce_data->setRecipient($field_parts[1]);
      }
    }
  }

}
