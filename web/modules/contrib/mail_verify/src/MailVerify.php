<?php

namespace Drupal\mail_verify;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Cache\CacheFactoryInterface;

/**
 * @file
 * Check the email for email_verify module.
 */

/**
 * Mail verification service.
 */
class MailVerify extends \Egulias\EmailValidator\EmailValidator {

  /**
   * Timeout for the stream. Make it short because
   * the user is going to wait this times the amount
   * of MX records available for the host.
   */
  const STREAM_TIMEOUT = 8;

  const ERR_UNKNOWN = 9005;

  /**
   * Sample hostname used to check if port
   * 25 is open.
   */
  const SAMPLE_HOSTNAME = 'drupal.org';

  /**
   * We need this service to output FRIENDLY error messages,
   * not just crappy codes.
   *
   * @var string
   */
  protected $message;

  /**
   * The mail parser.
   *
   * @var \Egulias\EmailValidator\EmailParser
   */
  protected $parser;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;


  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The MX records, if available.
   *
   * @var string[]
   */
  protected $mx;

  /**
   * Key value storage
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $key_value;

  /**
   * The from address used to test the mailboxes.
   *
   * @var string
   */
  protected $from;

  /**
   * The from address domain. Important to ensure
   * that the compain has the correct setup including
   * SPF records and reverse IP.
   *
   * @var string
   */
  protected $sender_domain;

  /**
   * Create an instance of MailVerify.
   *
   * @param CacheFactoryInterface $cache
   * @param LoggerChannelFactory $logger
   * @param KeyValueExpirableFactoryInterface $keyvaluefactory
   */
  public function __construct(CacheFactoryInterface $cache,
    LoggerChannelFactory $logger,
    KeyValueExpirableFactoryInterface $keyvaluefactory) {

    parent::__construct();

    try {
      $sitemail = (string) \Drupal::config('system.site')->get('mail');
      $this->parser->parse($sitemail);
      $this->from = $sitemail;
      $this->sender_domain = $this->parser->getParsedDomainPart();
      // Extract the <...> part, if there is one.
      $match = [];
      if (preg_match('/\<(.*)\>/', $this->from, $match) > 0) {
        $this->from = $match[1];
      }
    }
    catch (\Exception $e) {
      $logger->warning('Unable to get a valid FROM address to verify email inboxes.');
    }

    $this->cache = $cache->get('mail_verify');
    $this->logger = $logger->get('mail_verify');
    $this->key_value = $keyvaluefactory->get('mailverify');
  }

  /**
   * Current status
   *
   * @var bool
   */
  protected $current_status = NULL;

  /**
   * We are dong this not to rely in the module_handler
   * service. Anyways, this file is our own so we know
   *
   */
  protected function includeCompat() {
    $file = __DIR__ . '/../includes/windows_compat.inc';
    require_once $file;
  }

  /**
   * Check the status for mail sending.
   *
   * @param bool $refresh
   *   If we should force verification
   *
   * @return bool
   */
  public function getStatus($refresh = FALSE) {

    $this->current_status = $this->key_value->get('status');

    if ($this->current_status !== NULL && !$refresh) {
      return $this->current_status;
    }

    // This is called while the service container
    // is being built to prevent automatic
    // overriding, so we cannot depend on services
    // or even ourselves...
    $host = self::SAMPLE_HOSTNAME;

    // What SMTP servers should we contact?
    $mx_hosts = array();

    $this->includeCompat();

    // Start with a FALSE
    $this->current_status = FALSE;

    // Check if fsockopen() is disabled.
    // http://www.php.net/manual/en/function.function-exists.php#67947
    if (!function_exists('fsockopen')) {
      $this->logger->warning('Email Verify will test email domains but not mailboxes because your host has disabled the function fsockopen() for security.');
    }
    else {
      if (!getmxrr($host, $mx_hosts)) {
        // When there is no MX record, the host itself should be used.
        $mx_hosts[] = $host;
      }

      // Try to connect to one SMTP server.
      foreach ($mx_hosts as $smtp) {
        $errno = 0;
        $errstr = NULL;
        $connect = @fsockopen($smtp, 25, $errno, $errstr, self::STREAM_TIMEOUT);
        if (!$connect) {
          continue;
        }
        $out = fgets($connect, 1024);
        if (preg_match("/^220/", $out)) {
          $this->current_status = TRUE;
          break;
        }
      }
      if (!$this->current_status) {
        $this->logger->warning('Email verfiy could not connect to e-mail server, port 25 is probably unavailable.');
      }
    }

    $arrContextOptions= [
        "ssl"=> [
            "verify_peer" => false,
            "verify_peer_name" => false,
        ],
    ];

    // Now, for this to properly work we also need proper reverse IP setup for the sender domain
    // and SPF records.
    $ip = file_get_contents('https://api.ipify.org', false, stream_context_create($arrContextOptions));
    $hostbyaddr = gethostbyaddr($ip);
    if (empty($hostbyaddr)) {
      $this->logger->warning('Reverse DNS not found for IP: ' . $ip);
      $this->current_status = FALSE;
    }

    // Check DKIM and/or SPF
    $txt_records = dns_get_record ($this->sender_domain , DNS_TXT);
    $entries = isset($txt_records['entries']) ? $txt_records['entries'] : NULL;
    if ($entries === NULL) {
      $entries = isset($txt_records[0]['entries']) ? $txt_records[0]['entries'] : NULL;
    }
    if (is_array($entries)) {
      foreach ($entries as $entry) {
        if (preg_match("/^v=spf1.*/", $entry)) {
          $parser = new SpfParser($entry);
          // TODO: Implement the parser.
        }
      }
    }

    $ttl = $this->current_status ? 3600 * 24 * 3 : 3600 * 24;
    $this->key_value->setWithExpire('status', $this->current_status, $ttl);
    return $this->current_status;
  }

  /**
   * SMTP Single line read.
   *
   * Reads a single response line from SMTP server and identifies
   * response CODE and Following Character (" " or "-")
   *
   * @param resource $connect
   *
   */
  protected function readLine($connect) {
    $res = fgets($connect, 1024);
    $code = '';

    // Try to get response code: all numeric chars
    // at begginging of string.
    $i = 0;
    for ($i = 0, $len = strlen($res); $i <= $len; $i++) {
      $char = substr($res, $i, 1);
      if (!is_numeric($char)) {
        break;
      }
      $code .= $char;
    }

    // If there is a slash character after the response
    // code then more lines are to be expected
    // https://bugs.php.net/bug.php?id=6537
    $next_char = substr($res, $i, 1);

    return [
      'code' => $code,
      'next_char' => $next_char,
      'line' => $res
    ];
  }

  /**
   * Read response from SMTP server, multiline compatible.
   *
   *  @param resource $connect
   */
  protected function readAllLines($connect) {
    $result = '';
    do {
      $line = $this->readLine($connect);
      $result .= $line['line'];
    }
    while ($line['next_char'] === '-');
    return $result;
  }

  /**
   * Logs a denied e-mail verificaiton.
   *
   * @param string $code
   * @param string $host
   * @param string $from
   * @param string $out
   * @param string $to
   * @param string $mail
   */
  protected function issueError($code, $host, $from, $out, $to, $mail) {
    $this->error = self::ERR_UNKNOWN;
    $this->message = SafeMarkup::format('Rejected e-mail address @mail', ['@mail' => $mail]);
    $this->logger->debug('Rejected email address: "@address" (@code). Reason: <p>out: @out</p><p>from: @from</p><p>to: @to</p>',
      [
        '@code' => $code,
        '@host' => $host,
        '@from' => $from,
        '@out' => $out,
        '@to' => $to,
        '@address' => $mail
      ]);
  }

  /**
   * Warnings are for e-mails that pass-thru
   * but in reality validation is doubtful.
   *
   * @param string $code
   * @param string $host
   * @param string $from
   * @param string $out
   * @param string $to
   * @param string $mail
   */
  protected function issueWarning($code, $host, $from, $out, $to, $mail) {
    $this->logger->debug('Doubtful email address: "@address" (@code). Reason: <p>out: @out</p><p>from: @from</p><p>to: @to</p>',
      [
        '@code' => $code,
        '@host' => $host,
        '@from' => $from,
        '@out' => $out,
        '@to' => $to,
        '@address' => $mail
      ]);
  }

  /**
   * This verification is the last
   * stand once bots have been able to bypass
   * captach + honeypot + others.
   *
   * Yet, it still can be too much, as much
   * as to make our IP temporarily graylisted
   * to e-mail clients.
   *
   * So we are going to cache some results, because some
   * spammers re-use the same e-mail once
   * again and again :(
   */
  public function isValid($mail, $checkDns = FALSE, $strict = FALSE) {

    if ($cache = $this->cache->get($mail)) {
      $this->logger->info('Cached e-mail verification @mail | @valid', ['@mail' => $mail, '@valid' => $cache->data ? 'valid' : 'not valid']);
      return $cache->data;
    }

    if (!$this->getStatus()) {
      return parent::isValid($mail, $checkDns, $strict);
    }

    $valid = $this->_isValid($mail, $checkDns, $strict);
    $this->cache->set($mail, $valid, REQUEST_TIME + 3600);
    return $valid;
  }

  /**
   * Verify an e-mail.
   *
   * @param string $mail
   *   The e-mail address.
   * @return bool
   */
  protected function _isValid($mail, $checkDns = FALSE, $strict = FALSE) {

    $this->includeCompat();

    try {
      $this->parser->parse((string) $mail);
      $this->warnings = $this->parser->getWarnings();
    }
    catch (\Exception $e) {
      $rClass = new \ReflectionClass($this);
      $this->error = $rClass->getConstant($e->getMessage());
      return FALSE;
    }

    if (!$this->checkDNS()) {
      return FALSE;
    }

    $host = $this->parser->getParsedDomainPart();

    // If install found port 25 closed or fsockopen() disabled, we can't test
    // mailboxes.
    // TODO: Keep track of port 25 and fsockopen() or let the user
    // setup this.

    // What SMTP servers should we contact?
    $mx_hosts = array();
    if (!getmxrr($host, $mx_hosts)) {
      // When there is no MX record, the host itself should be used.
      $mx_hosts[] = $host;
    }

    $connection = FALSE;

    // Try to connect to one SMTP server.
    foreach ($mx_hosts as $smtp) {

      $errno = 0;
      $errstr = NULL;

      $connection = @fsockopen($smtp, 25, $errno, $errstr, self::STREAM_TIMEOUT);

      if (!$connection) {
        continue;
      }

      // You don't want to wait too long
      // for a socket open.
      stream_set_timeout($connection, self::STREAM_TIMEOUT);

      if (preg_match("/^220/", $out = fgets($connection, 1024))) {
        // OK, we have a SMTP connection.
        break;
      }
      else {
        // The SMTP server probably does not like us
        // (dynamic/residential IP for aol.com for instance)
        // Be on the safe side and accept the address, at least it has a valid
        // domain part...
        // Connection might have timed-out or some other network-level errors.
        // Details of error can be obtained through stream_get_meta_data()
        $metadata = stream_get_meta_data($connection);
        $timedout = isset($metadata['timed_out']) ? $metadata['timed_out'] : FALSE;
        $this->issueWarning("MX Timed out: $timedout", $host, $this->from, NULL, NULL, $mail);
        return TRUE;
      }
    }

    if (!$connection) {
      $this->issueError("Unable to connect to any host", $host, $this->from, NULL, NULL, $mail);
      return FALSE;
    }

    // Should be good enough for RFC compliant SMTP servers.
    $localhost = $_SERVER["HTTP_HOST"];
    if (!$localhost) {
      $localhost = 'localhost';
    }

    // Let's fake sending an e-mail
    fputs($connection, "HELO $localhost\r\n");
    $out = $this->readAllLines($connection);
    fputs($connection, "MAIL FROM: <{$this->from}>\r\n");
    $from = $this->readAllLines($connection);
    fputs($connection, "RCPT TO: <{$mail}>\r\n");
    $to = $this->readAllLines($connection);
    fputs($connection, "QUIT\r\n");
    fclose($connection);

    // This is empiric.... spammers create fake accounts and servers such as GMAIL
    // will issue graylisting messages because these accounts are flooded. The big providers
    // have custom messages for such situations.

    // Example message 1:

    // 450-4.2.1 The user you are trying to contact is receiving mail at a rate that 450-4.2.1
    // prevents additional messages from being delivered. Please resend your 450-4.2.1
    // message at a later time. If the user is able to receive mail at that 450-4.2.1 time,
    // your message will be delivered. For more information, please 450-4.2.1 visit
    // 450 4.2.1 http://support.google.com/mail/bin/answer.py?answer=6592 b31si8531202qga.66 - gsmtp

    // Example message 2:

    // 450 4.2.1 User is receiving mail too quickly

    $badmessages = [
        'receiving mail at a rate',
        'user is receiving mail too quickly',
        'too many connections from your host'
      ];

    $badmessages = array_map('preg_quote', $badmessages);
    $pattern = "/(" . implode('|', $badmessages) . ")/i";
    $matches = [];
    if (preg_match($pattern, $to, $matches)) {
      $this->issueError('E-mail account flooded.', $host, $this->from, $out, $to, $mail);
      return FALSE;
    }

    if (!preg_match("/^250/", $from)) {
      // Again, something went wrong before we could really test the address.
      // Be on the safe side and accept it.
      $this->issueWarning("Code 250 not found in from.", $host, $this->from, $out, $to, $mail);
      return TRUE;
    }

    $patterns = [
      // This server does not like us (noos.fr behaves like this for instance).
      "(Client host|Helo command) rejected",
      // Any 4xx error also means we couldn't really check except 450, which is
      // explcitely a non-existing mailbox: 450 = "Requested mail action not
      // taken: mailbox unavailable"
      "^4",
    ];

    $pattern = "/(" . implode('|', $patterns) . ")/i";
    $matches = [];
    if (preg_match($pattern, $to, $matches)) {
      $this->issueWarning("E-mail server seems not to like us.", $host, $this->from, $out, $to, $mail);
      $this->logger->debug($this->message);
      return TRUE;
    }

    if(empty($to)) {
      // If networkl-level error such as timeout $to will be empty
      $this->issueWarning("Possible network level error.", $host, $this->from, NULL, NULL, $mail);
      return TRUE;
    }

    if (!preg_match("/^250/", $to)) {
      $this->issueError('Code 250 not found in to.', $host, $this->from, $out, $to, $mail);
      return FALSE;
    }

    // Everything is OK.
    return TRUE;
  }

}
