<?php

namespace Drupal\inmail;

/**
 * A message type corresponding to the RFC 3463 specification.
 *
 * RFC 3463 "Enhanced Mail System Status Codes" defines numerical codes for
 * Delivery Status Notifications (DSN). In short, the code has three parts or
 * sub-codes: class, subject and detail. The class provides a broad
 * classification of the status, which is further specified by the subject and
 * the detail.
 *
 * The RFC defines a basic set of codes, with associated descriptions. The
 * description headings ("labels") of those codes are included in this class.
 *
 * @see https://tools.ietf.org/html/rfc3463
 * @see http://tools.ietf.org/html/rfc3464
 *
 * @ingroup processing
 */
class DSNStatus {

  /**
   * Class sub-code (first part of status code).
   *
   * @var int
   */
  protected $class;

  /**
   * Subject sub-code (second part of status code).
   *
   * @var int
   */
  protected $subject;

  /**
   * Detail sub-code (third part of status code).
   *
   * @var int
   */
  protected $detail;

  /**
   * Labels for the class sub-code, as specified by the RFC.
   *
   * @var array
   */
  private static $classMap = array(
    '2' => 'Success',
    '4' => 'Persistent Transient Failure',
    '5' => 'Permanent Failure',
  );

  /**
   * Labels for the subject sub-codes specified in RFC 3463.
   *
   * These are only used if the subject sub-code is recognized but the detail
   * sub-code is not.
   *
   * @var array
   */
  private static $subjectMap = array(
    '0' => 'Other or Undefined Status',
    '1' => 'Addressing Status',
    '2' => 'Mailbox Status',
    '3' => 'Mail System Status',
    '4' => 'Network and Routing Status',
    '5' => 'Mail Delivery Protocol Status',
    '6' => 'Message Content or Media Status',
    '7' => 'Security or Policy Status',
  );

  /**
   * Labels for the detail sub-codes specified in RFC 3463.
   *
   * @var array
   */
  private static $detailMap = array(
    '0' => array(
      '0' => 'Other undefined status',
    ),
    '1' => array(
      '0' => 'Other address status',
      '1' => 'Bad destination mailbox address',
      '2' => 'Bad destination system address',
      '3' => 'Bad destination mailbox address syntax',
      '4' => 'Destination mailbox address ambiguous',
      '5' => 'Destination address valid',
      '6' => 'Destination mailbox has moved, No forwarding address',
      '7' => 'Bad sender\'s mailbox address syntax',
      '8' => 'Bad sender\'s system address',
    ),
    '2' => array(
      '0' => 'Other or undefined mailbox status',
      '1' => 'Mailbox disabled, not accepting messages',
      '2' => 'Mailbox full',
      '3' => 'Message length exceeds administrative limit',
      '4' => 'Mailing list expansion problem',
    ),
    '3' => array(
      '0' => 'Other or undefined mail system status',
      '1' => 'Mail system full',
      '2' => 'System not accepting network messages',
      '3' => 'System not capable of selected features',
      '4' => 'Message too big for system',
      '5' => 'System incorrectly configured',
    ),
    '4' => array(
      '0' => 'Other or undefined network or routing status',
      '1' => 'No answer from host',
      '2' => 'Bad connection',
      '3' => 'Directory server failure',
      '4' => 'Unable to route',
      '5' => 'Mail system congestion',
      '6' => 'Routing loop detected',
      '7' => 'Delivery time expired',
    ),
    '5' => array(
      '0' => 'Other or undefined protocol status',
      '1' => 'Invalid command',
      '2' => 'Syntax error',
      '3' => 'Too many recipients',
      '4' => 'Invalid command arguments',
      '5' => 'Wrong protocol version',
    ),
    '6' => array(
      '0' => 'Other or undefined media error',
      '1' => 'Media not supported',
      '2' => 'Conversion required and prohibited',
      '3' => 'Conversion required but not supported',
      '4' => 'Conversion with loss performed',
      '5' => 'Conversion Failed',
    ),
    '7' => array(
      '0' => 'Other or undefined security status',
      '1' => 'Delivery not authorized, message refused',
      '2' => 'Mailing list expansion prohibited',
      '3' => 'Security conversion required but not possible',
      '4' => 'Security features not supported',
      '5' => 'Cryptographic failure',
      '6' => 'Cryptographic algorithm not supported',
      '7' => 'Message integrity failure',
    ),
  );

  /**
   * Constructs a DSNStatus object.
   *
   * @param int|string $class
   *   The class sub-code (first number in the status code).
   * @param int|string $subject
   *   The subject sub-code (second number in the status code).
   * @param int|string $detail
   *   The detail sub-code (third number in the status code).
   *
   * @throws \InvalidArgumentException
   *   If the given sub-codes are not in accordance with the RFC.
   */
  public function __construct($class, $subject, $detail) {
    if (!in_array($class, [2, 4, 5])) {
      throw new \InvalidArgumentException("Invalid 'class' part: $class.");
    }
    if ($subject < 0 || 999 < $subject) {
      throw new \InvalidArgumentException("Invalid 'subject' part: $subject");
    }
    if ($detail < 0 || 999 < $detail) {
      throw new \InvalidArgumentException("Invalid 'detail' part: $detail");
    }
    $this->class = intval($class);
    $this->subject = intval($subject);
    $this->detail = intval($detail);
  }

  /**
   * Parses a three-number status code.
   *
   * @param string $code
   *   Three-number status code.
   *
   * @return static
   *   A new DSNStatus object for the given code.
   *
   * @throws \InvalidArgumentException
   *   If the given code is not in accordance with the RFC.
   */
  public static function parse($code) {
    $parts = explode('.', $code);
    if (count($parts) == 3) {
      return new static($parts[0], $parts[1], $parts[2]);
    }
    else {
      throw new \InvalidargumentException("Invalid code: $code");
    }
  }

  /**
   * Returns the status code as a string.
   *
   * @return string
   *   The status code.
   */
  public function getCode() {
    return "$this->class.$this->subject.$this->detail";
  }

  /**
   * Returns the class sub-code.
   *
   * @return int
   *   The class sub-code.
   */
  public function getClass() {
    return $this->class;
  }

  /**
   * Returns the subject sub-code.
   *
   * @return int
   *   The subject sub-code.
   */
  public function getSubject() {
    return $this->subject;
  }

  /**
   * Returns the detail sub-code.
   *
   * @return int
   *   The detail sub-code.
   */
  public function getDetail() {
    return $this->detail;
  }

  /**
   * Returns the label for the class sub-code.
   *
   * The label corresponds to the first line of the description of the class in
   * the RFC.
   *
   * @return string
   *   The class label.
   */
  public function getClassLabel() {
    return static::$classMap[$this->class];
  }

  /**
   * Returns the label for the subject/detail sub-codes.
   *
   * The label corresponds to the first line of the description of the
   * subject/detail in RFC 3463.
   *
   * @return string|null
   *   The detail label, e.g. "Mail system congestion". If the detail sub-code
   *   is not defined in the RFC, this returns the more generic subject
   *   sub-code, e.g. "Security or Policy Status". If that too is not defined in
   *   the RFC, this returns NULL.
   */
  public function getDetailLabel() {
    // A known status code, e.g. 5.1.1
    if (isset(static::$detailMap[$this->subject][$this->detail])) {
      return static::$detailMap[$this->subject][$this->detail];
    }

    // Didn't recognize detail sub-code (last number), e.g. 4.1.162
    if (isset(static::$subjectMap[$this->subject])) {
      return static::$subjectMap[$this->subject];
    }

    // Didn't recognize this code at all.
    return NULL;
  }

  /**
   * Tells whether the class sub-code is 2 (Success).
   *
   * If the status is not a Success, it is known as a bounce.
   *
   * @return bool
   *   TRUE if the sub-code is 2, otherwise FALSE.
   */
  public function isSuccess() {
    return $this->class == 2;
  }

  /**
   * Tells whether the class sub-code is 4 (Persistent Transient Failure).
   *
   * This is also known as a soft bounce.
   *
   * @return bool
   *   TRUE if the sub-code is 4, otherwise FALSE.
   */
  public function isTransientFailure() {
    return $this->class == 4;
  }

  /**
   * Tells whether the class sub-code is 5 (Permanent Failure).
   *
   * This is also known as a hard bounce.
   *
   * @return bool
   *   TRUE if the sub-code is 5, otherwise FALSE.
   */
  public function isPermanentFailure() {
    return $this->class == 5;
  }

  /**
   * Returns a human-readable description of the status code.
   *
   * @return string
   *   A description of the status code. It corresponds to definitions in the
   *   RFC, and is thus in English and untranslatable.
   */
  public function getLabel() {
    return $this->getClassLabel() . ($this->getDetailLabel() ? ': ' . $this->getDetailLabel() : '');
  }
}
