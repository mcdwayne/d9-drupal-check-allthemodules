<?php

namespace Drupal\inmail_phpmailerbmh\Plugin\inmail\Analyzer;

use Drupal\inmail\DSNStatus;
use Drupal\inmail\MIME\MimeDSNEntity;
use Drupal\inmail\MIME\MimeMessageInterface;
use Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerBase;
use Drupal\inmail\ProcessorResultInterface;

/**
 * Message Analyzer wrapper for the BounceMailHandler library.
 *
 * The main class included in the library assumes responsibility for the whole
 * processing flow, by connecting to an IMAP provider and moving messages into
 * folders depending on the analysis. The analysis is however (fortunately)
 * delegated to two static functions. This wrapper ignores the processing class
 * and uses only the analysis functions.
 *
 * The library was originally developed by Andy Prevost at WorxWare
 * (http://sourceforge.net/projects/bmh/) but a fork is currently maintained by
 * Anthon Pang on https://github.com/instaclick/PHPMailer-BMH
 *
 * @ingroup analyzer
 *
 * @Analyzer(
 *   id = "phpmailerbmh",
 *   label = @Translation("Wrapper for PHPMailer-BMH")
 * )
 */
class PHPMailerBMHAnalyzer extends AnalyzerBase {

  /**
   * Maps results from the library to appropriate DSN status codes.
   *
   * @var array
   *   An associative array with rule_cat values as keys and DSN status codes
   *   (strings) as keys.
   */
  protected static $rulecatStatusMap = array(
    // Sender blocked.
    'antispam' => '5.7.1',
    // "AutoReply message from...".
    'autoreply' => '2.0.0',
    // Invalid header, invalid structure, etc.
    'content_reject' => '5.6.0',
    // Transaction failed etc.
    'command_reject' => '5.5.0',
    // input/output error, can not open new email file.
    'internal_error' => '4.3.0',
    // System busy.
    'defer' => '4.4.1',
    // E.g. connection timed out.
    'delayed' => '4.0.0',
    // "mail for mta.example.com loops back to myself"
    'dns_loop' => '5.0.0',
    'dns_unknown' => '5.0.0',
    // Mailbox is full.
    'full' => '4.2.2',
    // @todo Cover all rule_cats: https://www.drupal.org/node/2379769
    // Unknown user.
    'unknown' => '5.1.1',
    // Deliberately excluding 'unrecognized'.
  );

  /**
   * {@inheritdoc}
   */
  public function analyze(MimeMessageInterface $message, ProcessorResultInterface $processor_result) {
    /** @var \Drupal\inmail\DefaultAnalyzerResult $result */
    $result = $processor_result->getAnalyzerResult('bounce', 'inmail_bounce');
    $bounce_data = $result->ensureContext('bounce', 'inmail_bounce');

    // The analysis part of the library is in the bmhDSNRules and bmhBodyRules
    // functions.
    require_once $this->getLibraryPath() . '/lib/BounceMailHandler/phpmailer-bmh_rules.php';
    if ($message instanceof MimeDSNEntity) {
      // The bmhDSNRules function takes the two report parts as arguments.
      $bmh_result = bmhDSNRules($message->getHumanPart()->toString(), $message->getStatusPart()->toString());
    }
    else {
      $bmh_result = bmhBodyRules($message->getBody(), NULL, TRUE);
    }

    // The analysis returns an associative array designed for the library to
    // handle. It contains the following keys, of which rule_cat is the most
    // specific and usable:
    //   - remove: indicates that the message should be removed.
    //   - bounce_type: groups rule_cat values.
    //   - rule_cat: a string identifier for the reason for the bounce.
    //   - rule_no: references a single match condition in the code.
    //   - email: the recipient causing the bounce, if identifiable.
    if (isset($bmh_result['email'])) {
      $bounce_data->setRecipient($bmh_result['email']);
    }
    if (isset(static::$rulecatStatusMap[$bmh_result['rule_cat']])) {
      $code = static::$rulecatStatusMap[$bmh_result['rule_cat']];
      if ($code) {
        $bounce_data->setStatusCode(DSNStatus::parse($code));
      }
    }
  }

  /**
   * Returns the path to the library defined by composer_manager.
   *
   * @return string
   *   The path to the PHPMailer-BMH libarary.
   */
  protected function getLibraryPath() {
    $composer_manager_vendor_path = \Drupal::config('composer_manager.settings')->get('vendor_dir');
    return \Drupal::root() . '/' . $composer_manager_vendor_path . '/' . 'instaclick/bounce-mail-handler';
  }

}
