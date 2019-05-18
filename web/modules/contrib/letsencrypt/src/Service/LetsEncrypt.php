<?php

namespace Drupal\letsencrypt\Service;

use Drupal\idna\Service\IdnaConvertInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use LEClient\LEClient;

/**
 * Class LetsEncrypt.
 */
class LetsEncrypt implements LetsEncryptInterface {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Config factory.
   *
   * @var \Drupal\idna\Service\IdnaConvertInterface
   */
  protected $idna;

  /**
   * Creates a new Lescript.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\idna\Service\IdnaConvertInterface $idna
   *   Idna Convert.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
      IdnaConvertInterface $idna) {
    // Services.
    $config = $config_factory->get('letsencrypt.settings');
    $this->configFactory = $config_factory;
    $this->idna = $idna;
    // Configs.
    $log = [
      // Logs no messages or faults, except Runtime Exceptions.
      'LOG_OFF' => 0,
      // Logs only messages and faults.
      'LOG_STATUS' => 1,
      // Logs messages, faults and raw responses from HTTP requests.
      'LOG_DEBUG' => 2,
    ];
    $url = [
      'prod' => 'https://acme-v02.api.letsencrypt.org',
      'stage' => 'https://acme-staging-v02.api.letsencrypt.org',
    ];
    $this->htype = 'http-01';
    $this->email = [$config->get('cert-email')];
    $this->url = $url[$config->get('acme-url')];
    $this->log = $log[$config->get('acme-log')];
    // Cert & Accaunt Dir.
    $cdir = $this->prepareDir($config);
    $this->dir = $cdir;
    $this->acc = [
      "private_key" => "$cdir/_account/private.pem",
      "public_key" => "$cdir/_account/public.pem",
    ];
    // Challenge.
    $this->challenge = DRUPAL_ROOT . '/.well-known/acme-challenge/';
  }

  /**
   * Read Cert.
   */
  public function read($host = FALSE) {
    $result = FALSE;
    $cert = $this->cert($host);
    $fullchain = $cert['fullchain_certificate'];
    if (file_exists($fullchain)) {
      $result = "Exists: $fullchain\n";
      $crt = file_get_contents($fullchain);
      $cert_read = openssl_x509_read($crt);
      $cert_info = openssl_x509_parse($cert_read);
      if ($cert_info) {
        $date = date('c', $cert_info['validTo_time_t']);
        $time_left = $cert_info['validTo_time_t'] - time();
        $days_left = round($time_left / 60 / 60 / 24);
        $result .= "<h5>Existing certificate <i>$host</i>\n";
        $result .= "expires at $date ({$days_left} days left).</h4>";
        $domains = "";
        $dns = str_replace(["DNS:", " "], "", $cert_info['extensions']['subjectAltName']);
        foreach (explode(",", $dns) as $dns_line) {
          $domains .= \Drupal::service('idna')->decode($dns_line) . " \n";
        }
        $result .= "<p>{$domains}</p>";
      }
      else {
        $result .= "Empty result in openssl_x509_read / openssl_x509_parse \n";
      }
    }
    else {
      $result = "MISS: {$fullchain}\n";
    }
    return $result;
  }

  /**
   * Get Cert.
   */
  public function sign($base, array $domains = []) {

    $cert = $this->cert($base);
    $doms = $this->prepareDomains($base, $domains);
    // Client & Acc.
    $this->client = new LEClient($this->email, $this->url, $this->log, $cert, $this->acc);
    $acct = $this->client->getAccount();
    // Order.
    $order = $this->client->getOrCreateOrder($doms['base'], $doms['all']);
    // Check whether there are any authorizations pending.
    // If that is the case, try to verify the pending authorizations.
    if (!$order->allAuthorizationsValid()) {
      // Get the HTTP challenges from the pending authorizations.
      $pending = $order->getPendingAuthorizations($this->htype);
      // Do Acme Challenge.
      if (!empty($pending)) {
        foreach ($pending as $challenge) {
          // Wright challenge.
          $this->acmeChallenge($challenge);
          // Let LetsEncrypt verify this challenge.
          $order->verifyPendingOrderAuthorization($challenge['identifier'], $this->htype);
        }
      }
    }
    // Check once more whether all authorizations are valid
    // before we can finalize the order.
    if ($order->allAuthorizationsValid()) {
      // Finalize the order first, if that is not yet done.
      if (!$order->isFinalized()) {
        $order->finalizeOrder();
      }
      // Check whether the order has been finalized before we can get
      // the certificate. If finalized, get the certificate.
      if ($order->isFinalized()) {
        $order->getCertificate();
        file_put_contents($cert['domains'], implode("\n", $doms['all']));
        $date = time() + 60 * 60 * 24 * 90;
        file_put_contents($cert['expire'], format_date($date, 'custom', 'Y-m-d\TH:i:s'));
      }
    }
    return implode(", \n", $doms['all']);
  }

  /**
   * Acme Challenge.
   */
  private function acmeChallenge($challenge, $drupalroot = TRUE) {
    \Drupal::service('module_handler')->alter('letsencrypt_challenge', $challenge, $drupalroot);
    if ($drupalroot) {
      $this->createDir($this->challenge, FALSE);
      file_put_contents($this->challenge . $challenge['filename'], $challenge['content']);
    }
  }

  /**
   * Prepare cert directory.
   */
  private function prepareDir($config) {
    $cdir = getenv("HOME") . "cert";
    if ($dir = $config->get('cert-dir')) {
      if (substr($dir, 0, '2') == '~/') {
        $dir = getenv("HOME") . substr($dir, 2);
      }
      if (substr($dir, 0, '2') == 'private://') {
        $dir = \Drupal::service('file_system')->realpath($dir);
      }
      $cdir = $dir;
    }
    $this->createDir($cdir);
    $this->createDir("$cdir/_account");
    return $cdir;
  }

  /**
   * Prepare domains.
   */
  private function prepareDomains($base, $domains) {
    $domain = $this->idna->encode($base);
    $all = [$domain];
    if (!empty($domains)) {
      foreach ($domains as $k => $v) {
        $d = trim($v);
        if (strlen($d) > 3) {
          $d = $this->idna->encode($d);
          if (!in_array($d, $all)) {
            $all[] = $this->idna->encode($d);
          }
        }
      }
    }
    return [
      'base' => $domain,
      'all' => $all,
    ];
  }

  /**
   * Cert Path.
   */
  public function cert($base) {
    $ddir = \Drupal::transliteration()->transliterate($this->dir . "/$base");
    $this->createDir($ddir);
    $cert = [
      "public_key" => "{$ddir}/public.pem",
      "private_key" => "{$ddir}/private.pem",
      "certificate" => "{$ddir}/certificate.crt",
      "fullchain_certificate" => "{$ddir}/fullchain.pem",
      "order" => "{$ddir}/order",
      "domains" => "{$ddir}/domains",
      "expire" => "{$ddir}/expire",
    ];
    return $cert;
  }

  /**
   * Create Directory.
   */
  private function createDir($directory, $protect = TRUE) {
    if (!file_exists($directory)) {
      mkdir($directory, 0777, TRUE);
      if ($protect) {
        file_put_contents("$directory/.htaccess", "order deny,allow\ndeny from all");
      }
    }
  }

}
