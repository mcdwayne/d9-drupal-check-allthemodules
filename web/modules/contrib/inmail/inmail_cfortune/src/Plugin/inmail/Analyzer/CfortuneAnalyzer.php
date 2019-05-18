<?php

namespace Drupal\inmail_cfortune\Plugin\inmail\Analyzer;

use cfortune\PHPBounceHandler\BounceHandler;
use Drupal\inmail\DSNStatus;
use Drupal\inmail\MIME\MimeMessageInterface;
use Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerBase;
use Drupal\inmail\ProcessorResultInterface;

/**
 * MimeMessage Analyzer wrapper for cfortune's BounceHandler class.
 *
 * The BounceHandler class tries to identify a standardized DSN code and the
 * intended recipient of the original message. If the status is not directly
 * deducible, some pattern-matching for well-known notice strings is applied to
 * cover more cases.
 *
 * The class is maintained by Patrick O'Connell (Rambomst) as a fork of the
 * PHP-Bounce-Handler project: https://github.com/Rambomst/PHP-Bounce-Handler
 *
 * @ingroup analyzer
 *
 * @Analyzer(
 *   id = "cfortune",
 *   label = @Translation("Wrapper for BounceHandler")
 * )
 */
class CfortuneAnalyzer extends AnalyzerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(MimeMessageInterface $message, ProcessorResultInterface $processor_result) {
    /** @var \Drupal\inmail\DefaultAnalyzerResult $result */
    $result = $processor_result->getAnalyzerResult();
    $bounce_data = $result->ensureContext('bounce', 'inmail_bounce');

    // All operational code is contained in the BounceHandler class.
    $handler = new BounceHandler();

    // Perform the analysis.
    $handler->parse_email($message->toString());

    // The recipient property possibly contains the target recipient of the
    // message that bounced.
    if ($handler->recipient) {
      $bounce_data->setRecipient(trim($handler->recipient));
    }
    // The status property possibly contains an RFC 3463 status code.
    if ($handler->status) {
      $bounce_data->setStatusCode(DSNStatus::parse($handler->status));
    }
  }

}
