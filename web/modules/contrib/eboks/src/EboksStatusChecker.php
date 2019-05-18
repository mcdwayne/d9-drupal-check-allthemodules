<?php
/**
 * @file
 * Contains e-Boks status checker definition.
 */

namespace Drupal\eboks;

use Drupal\eboks\Entity\EboksMessage;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use phpseclib\Net\SFTP;
use phpseclib\Crypt\RSA;

/**
 * Class e-Boks status checker.
 *
 * @package Drupal\eboks
 */
class EboksStatusChecker {

  /**
   * EboksSender configuration.
   *
   * @var array $config
   */
  protected $config;

  /**
   * Logger interface.
   *
   * @var \Psr\Log\LoggerInterface $logger
   */
  protected $logger;

  /**
   * SFTP connection object.
   *
   * @var object $sftp
   */
  protected $sftp;

  /**
   * Temp dir path.
   *
   * @var string $dirPath
   */
  protected $dirPath;

  /**
   * Remote dir path.
   *
   * @var string $remoteDirPath
   */
  protected $remoteDirPath;

  /**
   * Valid config flag.
   *
   * @var bool $configValid
   */
  protected $configValid = TRUE;

  /**
   * Sender construct method.
   */
  public function __construct() {
    $this->logger = \Drupal::logger('eboks');
    $this->config = \Drupal::service('config.factory')->get('eboks.nets');
    // Validate sender config.
    $this->validateConfig();
    $this->dirPath = 'public://eboks_receipts/';
    if (!file_exists($this->dirPath)) {
      mkdir($this->dirPath, 0777, TRUE);
    }
    $this->remoteDirPath = '/Outbound/';
  }

  /**
   * Checker e-Boks message callback.
   */
  public function check($id = FALSE) {
    if (!$this->configValid) {
      return FALSE;
    }
    $updated = 0;
    $query = \Drupal::entityQuery('eboks_message')->condition('status', 'sent');
    if ($id) {
      $query->condition('id', $id);
    }
    $messages_ids = $query->execute();
    if (empty($messages_ids)) {
      $this->logger(t('No messages to update.'), 'status');
    }

    $files = $this->list();
    foreach (EboksMessage::loadMultiple($messages_ids) as $eboks_message) {
      $file_key = EBOKS_FILE_PREFIX . $eboks_message->generateShipmentId();
      if (isset($files[$file_key])
        && $res = $this->processReceipt($files[$file_key], $eboks_message)
      ) {
        $updated++;
      }
      elseif (time() - $eboks_message->timestamp->value > 1800) {
        $eboks_message->set('status', 'receipt not found');
        $eboks_message->save();
        $updated++;
      }
    }
    if ($updated) {
      $this->logger(t('@count messages updated.', [
        '@count' => $updated,
      ]), 'status');
    }

    return $updated;
  }

  /**
   * Validation get function.
   */
  public function isValid() {
    return $this->configValid;
  }

  /**
   * Configuration validate function.
   */
  private function validateConfig() {
    $required = [
      'corporateId',
      'country',
      'eBoksId',
      'documentType',
      'sftp_host',
      'sftp_username',
      'sftp_private_key',
      'sftp_passphrase',
    ];
    foreach ($required as $value) {
      if (empty($this->config->get($value))) {
        $this->configValid = FALSE;
        // Logging an error of validation.
        $this->logger->error('eBoks sender configuration is not valid. Key @name is missing.', [
          '@name' => $value,
        ]);
        return;
      }
    }

    if (!$this->sftpLogin()) {
      // Error logged in sftpLogin() method.
      $this->configValid = FALSE;
    }
  }

  /**
   * Process receipt from NETs share sFTP folder.
   */
  private function processReceipt($filename, $entity) {
    if (!$sftp = $this->sftpLogin()) {
      return FALSE;
    }

    $receipt_xml = file_get_contents($this->dirPath . $filename);
    if (empty($receipt_xml)) {
      $this->logger->error('Failed loading receipt file @file from local filesystem.', [
        '@file' => $filename,
      ]);
      return FALSE;
    }
    $xml_encoder = new XmlEncoder('receipt');
    $receipt_array = $xml_encoder->decode($receipt_xml, 'xml');
    if (!isset($receipt_array['status'])) {
      $entity->set('status', 'failed responce');
    }

    if ((int) $receipt_array['status'] == 200) {
      $entity->set('status', 'received');
    }

    if ((int) $receipt_array['status'] >= 400) {
      $entity->set('status', 'failed');
    }

    $entity->set('response', serialize($receipt_array));
    $entity->save();

    unlink($this->dirPath . $filename);
    return TRUE;
  }

  /**
   * Download receipts from NETs share sFTP folder.
   */
  private function list() {
    $result = [];

    // Load files from Net share sFTP folder.
    if ($new_files = $this->fetch()) {
      $items = [
        '#theme' => 'item_list',
        '#items' => $new_files,
      ];
      $this->logger(t('Downloaded @count new receipts: @files', [
        '@count' => count($new_files),
        '@files' => render($items),
      ]), 'status');
    }
    else {
      $this->logger(t('New receipts not found'), 'status');
    }

    if ($files = scandir($this->dirPath)) {
      foreach ($files as $file) {
        if (in_array($file, [".", ".."]) || is_dir($file)) {
          continue;
        }
        $start_pos = strpos($file, 'netsshare_');
        if ($start_pos === FALSE) {
          continue;
        }
        $filename_arr = explode('.', $file);
        $key = substr($filename_arr[0], $start_pos, strlen($filename_arr[0]) - $start_pos);
        $result[$key] = $file;
      }
    }

    return $result;
  }

  /**
   * Download receipts from NETs share sFTP folder.
   */
  private function fetch() {
    $result = [];

    if (!$sftp = $this->sftpLogin()) {
      return $result;
    }

    if ($files = $sftp->nlist($this->remoteDirPath)) {
      foreach ($files as $file) {
        $destination = drupal_realpath($this->dirPath) . '/' . $file;
        if (!$sftp->get($this->remoteDirPath . $file, $destination)) {
          $this->logger->error('Failed downloading receipt file @file from NETs share storage.', [
            '@file' => $this->remoteDirPath . $file,
          ]);
        }
        $result[] = $file;
      }
    }

    return $result;
  }

  /**
   * SFTP login function.
   *
   * @return mixed
   *   Logged in sFTP object or FALSE.
   */
  private function sftpLogin() {

    $key = new RSA();
    if (!empty($this->config->get('sftp_passphrase'))) {
      $key->setPassword($this->config->get('sftp_passphrase'));
    }
    if (!$key->loadKey(file_get_contents($this->config->get('sftp_private_key')))) {
      $this->logger->error('Failed to load private key');
      return FALSE;
    }

    $sftp = new SFTP($this->config->get('sftp_host'));
    if (!$sftp->login($this->config->get('sftp_username'), $key)) {
      $this->logger->error('Failed to login to @host', [
        '@host' => $this->config->get('sftp_host'),
      ]);
      return FALSE;
    }

    return $sftp;
  }

  /**
   * Helper function to cleanup directory.
   *
   * @param string $path
   *   Path to delete.
   */
  private function cleanup($path) {
    if (!file_exists($path)) {
      $this->logger->warning('Directory @path not exists.', [
        '@path' => $path,
      ]);
      return;
    }

    $files = scandir($path);
    foreach ($files as $value) {
      if (in_array($value, [".", ".."]) || is_dir($value)) {
        continue;
      }
      unlink($path . '/' . $value);
    }
  }

  /**
   * Logging function.
   */
  private function logger($message, $type = 'status') {
    drupal_set_message($message, $type);
    switch ($type) {
      case 'warning':
        $this->logger->warning($message);
        break;

      case 'error':
        $this->logger->error($message);
        break;

      default:
        $this->logger->notice($message);
    }
    return $message;
  }

}
