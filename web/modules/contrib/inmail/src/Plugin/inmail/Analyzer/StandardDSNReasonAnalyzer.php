<?php

namespace Drupal\inmail\Plugin\inmail\Analyzer;

use Drupal\inmail\MIME\MimeDSNEntity;
use Drupal\inmail\MIME\MimeMessageInterface;
use Drupal\inmail\ProcessorResultInterface;

/**
 * Extracts the human-readable message from a DSN message.
 *
 * @todo Drop standard intro texts https://www.drupal.org/node/2379917
 *
 * @ingroup analyzer
 *
 * @Analyzer(
 *   id = "dsn_reason",
 *   label = @Translation("Standard DSN Reason Analyzer")
 * )
 */
class StandardDSNReasonAnalyzer extends AnalyzerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(MimeMessageInterface $message, ProcessorResultInterface $processor_result) {
    // Ignore messages that are not DSN.
    if (!$message instanceof MimeDSNEntity) {
      return;
    }

    /** @var \Drupal\inmail\DefaultAnalyzerResult $result */
    $result = $processor_result->getAnalyzerResult();
    $bounce_data = $result->ensureContext('bounce', 'inmail_bounce');

    // Save the human-readable bounce reason.
    $bounce_data->setReason(trim($message->getHumanPart()->getDecodedBody()));
  }
}
