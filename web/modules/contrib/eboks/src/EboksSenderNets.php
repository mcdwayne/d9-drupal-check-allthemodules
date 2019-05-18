<?php
/**
 * @file
 * Contains e-Boks sender definition.
 */

namespace Drupal\eboks;

use DOMDocument;
use Drupal\eboks\Entity\EboksMessage;
use Drupal\eboks\Entity\EboksMessageInterface;
use Drupal\Component\Utility\Unicode;
use ZipArchive;
use phpseclib\Net\SFTP;
use phpseclib\Crypt\RSA;
use Mpdf\Mpdf;

/**
 * Class e-Boks Nets sender.
 *
 * @package Drupal\eboks
 */
class EboksSenderNets implements EboksSenderInterface {

  /**
   * EboksSender configuration.
   *
   * @var array $config
   */
  protected $config;

  /**
   * Array with messages.
   *
   * @var array $messages
   */
  protected $messages;

  /**
   * Array with sender information.
   *
   * @var mixed $senderData
   */
  protected $senderData;

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
   * Shipment id.
   *
   * @var string $shipmentId
   */
  protected $shipmentId;

  /**
   * Receiver.
   *
   * @var string $receiver
   */
  protected $receiverId;

  /**
   * Receiver.
   *
   * @var string $receiverType
   */
  protected $receiverType;

  /**
   * Temp dir path.
   *
   * @var string $dirPath
   */
  protected $dirPath;

  /**
   * Valid config flag.
   *
   * @var bool $configValid
   */
  protected $configValid = TRUE;

  /**
   * Eboks message entity.
   *
   * @var EboksMessageInterface $entity
   */
  protected $entity;

  /**
   * Sender construct method.
   *
   * @param string $receiver_id
   *   Receiver id.
   * @param string $receiver_type
   *   Receiver type: CPR or CVR.
   * @param array $messages
   *   Array with messages.
   * @param mixed $sender_data
   *   Sender information data.
   */
  public function __construct($receiver_id, $receiver_type, array $messages = [], $sender_data = FALSE) {
    $this->logger = \Drupal::logger('eboks');
    $this->config = \Drupal::service('config.factory')->get('eboks.nets');
    // Validate sender config.
    $this->validateConfig();

    $this->receiverId = $receiver_id;
    $this->receiverType = $receiver_type;
    $this->messages = $messages;
    $this->senderData = $sender_data;

    $this->dirPath = 'temporary://eboks_shipment/';
  }

  /**
   * Initialization of sending process and create sending entity.
   */
  public function init() {
    if (!$this->configValid) {
      return FALSE;
    }
    $this->entity = EboksMessage::create([
      'shipment_xml' => 'prepraring...',
      'messages' => serialize($this->messages),
      'timestamp' => time(),
      'sender_data' => serialize($this->senderData),
      'status' => 'initiated',
      'response' => '',
    ]);
    $this->entity->save();
    $this->shipmentId = $this->entity->generateShipmentId();
    $this->cleanup($this->messageTempDir());
    return $this->entity->id();
  }

  /**
   * Send e-Boks message callback.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function send() {
    if (!$this->configValid) {
      return FALSE;
    }

    $shipment_xml = $this->getXml();
    if (empty($shipment_xml)
      || !file_unmanaged_save_data($shipment_xml, $this->messageTempDir() . 'shipment.xml')) {
      $this->logger->warning('Failed shipment.xml saving.');
      return FALSE;
    }

    $files = [];
    foreach (scandir($this->messageTempDir()) as $value) {
      if (in_array($value, [".", ".."])) {
        continue;
      }
      $files[] = $this->messageTempDir() . $value;
    }
    $filename = EBOKS_FILE_PREFIX . $this->shipmentId . '.zip';
    $zip_file = $this->dirPath . $filename;
    $this->createZip($zip_file, $files);

    if ($upload_result = $this->uploadMessage($zip_file)) {
      if ($this->receiverType == 'CPR') {
        $shipment_xml = str_replace($this->receiverId, '********', $shipment_xml);
      }
      $this->entity->set('shipment_xml', $shipment_xml);
      $this->entity->set('status', 'sent');
      $this->entity->save();
    }

    // Cleanup after sending.
    $this->cleanup($this->messageTempDir());
    rmdir($this->messageTempDir());
    if (file_exists($zip_file)) {
      unlink($zip_file);
    }

    return $upload_result;
  }

  /**
   * Validation get function.
   */
  public function isValid() {
    return $this->configValid;
  }

  /**
   * Create zip arch function.
   */
  private function createZip($path, array $files) {
    $zip = new ZipArchive();
    if ($zip->open(drupal_realpath($path), ZipArchive::CREATE) !== TRUE) {
      $this->logger->error('Can not create file @filename', [
        '@filename' => $path,
      ]);
    }
    foreach ($files as $file) {
      $zip->addFile(drupal_realpath($file), basename($file));
    }
    $zip->close();
  }

  /**
   * Send e-Boks message callback.
   */
  private function getXml() {
    $messages = $this->getMessages();
    if (empty($messages)) {
      $this->logger->warning('No message to send e-Boks mail.');
      return FALSE;
    }

    if (empty($this->shipmentId)) {
      $this->logger->warning('Shipment id is not defined.');
      return FALSE;
    }

    $data = [
      'name' => 'shipment',
      'attributes' => [
        'xmlns' => 'http://www.nets.eu/nets-share/1.6',
        'xmlns:eboks' => 'http://www.nets.eu/nets-share/1.6/eboks',
      ],
      [
        'name' => 'shipmentInfo',
        ['name' => 'shipmentId', 'value' => $this->shipmentId],
        [
          'name' => 'sender',
          ['name' => 'corporateIdentityNumber', 'value' => $this->config->get('corporateId')],
          ['name' => 'country', 'value' => $this->config->get('country')],
        ],
      ],
      ['name' => 'messages'] + $messages,
    ];
    $doc = new DOMDocument('1.0', 'UTF-8');
    $child = $this->generateXmlElement($doc, $data);
    if ($child) {
      $doc->appendChild($child);
    }
    // Add whitespace to make easier to read XML.
    $doc->formatOutput = TRUE;
    return $doc->saveXML();
  }

  /**
   * Send e-Boks message callback.
   */
  private function getReceiver() {
    $receiver = [
      'CPR' => [
        'name' => 'eboks:personIdentityNumber',
        'value' => $this->receiverId,
      ],
      'CVR' => [
        'name' => 'eboks:danishOrganisation',
        'attributes' => [
          'cvr' => $this->receiverId,
        ],
      ],
    ];
    return isset($receiver[$this->receiverType]) ? $receiver[$this->receiverType] : [];
  }

  /**
   * Get function to fetch message temp dir.
   */
  private function messageTempDir() {
    $temp_dir = $this->dirPath . '/' . $this->shipmentId . '/';
    if (!file_exists($temp_dir)) {
      mkdir($temp_dir, 0777, TRUE);
    }
    return $temp_dir;
  }

  /**
   * Get messages.
   */
  private function getMessages() {
    $xml_arr = [];

    foreach ($this->messages as $key => $message) {
      $m_id = $key + 1;
      if (empty($message['filepath'])) {
        if (empty($message['content'])) {
          $this->logger->warning('Nothing to send as attachment in message');
          continue;
        }

        $mpdf = new Mpdf(['tempDir' => drupal_realpath('temporary://mpdf')]);
        $mpdf->WriteHTML($message['content']);
        $filepath = $this->messageTempDir() . 'attachment_' . $m_id . '.pdf';
        $mpdf->Output($filepath, 'F');
        $message['filepath'] = basename($filepath);

      }

      if (empty($message['description'])) {
        $this->logger->warning('Message description empty. Seding empty line');
        $message['description'] = '';
      }
      elseif (Unicode::strlen($message['description']) > 50) {
        $desc_truncated = Unicode::substr($message['description'], 0, 50);
        $this->logger->notice('Message description @description_before has been truncated to @description_after', [
          '@description_before' => $message['description'],
          '@description_after' => $desc_truncated,
        ]);
        $message['description'] = $desc_truncated;
      }

      $xml_arr[] = [
        'name' => 'message',
        'attributes' => [
          'id' => $m_id,
        ],
        [
          'name' => 'sender',
          ['name' => 'eboks:eboksId', 'value' => $this->config->get('eBoksId')],
        ],
        [
          'name' => 'eboks:receiver',
          $this->getReceiver(),
          ['name' => 'eboks:country', 'value' => $this->config->get('country')],
        ],
        [
          'name' => 'eboks:config',
          ['name' => 'eboks:description', 'value' => $message['description']],
        ],
        [
          'name' => 'document',
          ['name' => 'documentType', 'value' => $this->config->get('documentType')],
          ['name' => 'filepath', 'value' => $message['filepath']],
          [
            'name' => 'attachment',
            'attributes' => [
              'path' => $message['filepath'],
            ],
            ['name' => 'eboks:attachmentDescription', 'value' => $message['description']],
          ],
        ],
      ];
    }

    return $xml_arr;
  }

  /**
   * Recursive function generates xml element.
   */
  private function generateXmlElement(DOMDocument $dom, $data) {
    if (empty($data['name'])) {
      return FALSE;
    }

    // Create the element.
    $element_value = (!empty($data['value'])) ? $data['value'] : NULL;
    $element = $dom->createElement($data['name'], $element_value);

    // Add any attributes.
    if (!empty($data['attributes']) && is_array($data['attributes'])) {
      foreach ($data['attributes'] as $attribute_key => $attribute_value) {
        $element->setAttribute($attribute_key, $attribute_value);
      }
    }

    // Any other items in the data array should be child elements.
    foreach ($data as $data_key => $child_data) {
      if (!is_numeric($data_key)) {
        continue;
      }

      $child = $this->generateXmlElement($dom, $child_data);
      if ($child) {
        $element->appendChild($child);
      }
    }

    return $element;
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
   * Upload message to eBoks sFTP.
   *
   * @param string $filepath
   *   Path to local file should be uploaded.
   *
   * @return bool
   *   True in success, FALSE otherwise.
   */
  private function uploadMessage($filepath) {
    $src_file = drupal_realpath($filepath);

    if (!file_exists($src_file)) {
      $this->logger->error('File to upload @file are not exists.', [
        '@file' => $src_file,
      ]);
      return FALSE;
    }

    $filename = basename($src_file);
    $remote_path = '/Inbound/' . $this->config->get('country') . $this->config->get('corporateId') . '/';

    if (!$sftp = $this->sftpLogin()) {
      return FALSE;
    }

    if (!$sftp->put($remote_path . $filename,
      $src_file,
      SFTP::SOURCE_LOCAL_FILE)) {
      $this->logger->error('Could not upload file to @file as remote file @remote_tile', [
        '@file' => $src_file,
        '@remote_tile' => $remote_path . $filename,
      ]);
      return FALSE;
    }

    return TRUE;
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

}
