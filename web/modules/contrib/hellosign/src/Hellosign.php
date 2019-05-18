<?php

namespace Drupal\hellosign;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\encryption\EncryptionService;
use HelloSign\Client;
use HelloSign\EmbeddedSignatureRequest;
use HelloSign\SignatureRequest;
use HelloSign\Signer;
use Psr\Log\LoggerInterface;

/**
 * Establishes a connection to HelloSign.
 */
class Hellosign {

  use StringTranslationTrait;

  /**
   * The hellosign.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The HelloSign PHP SDK client.
   *
   * @var \Hellosign\Client
   */
  protected $client;

  /**
   * The encryption service.
   *
   * @var \Drupal\encryption\EncryptionService
   */
  protected $encryption;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Establishes the connection to HelloSign.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\encryption\EncryptionService $encryption
   *   The encryption service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   *
   * @throws \Exception
   */
  public function __construct(ConfigFactoryInterface $config_factory, EncryptionService $encryption, FileSystemInterface $file_system, LoggerInterface $logger, TranslationInterface $string_translation) {
    $this->config = $config_factory->get('hellosign.settings');
    $this->encryption = $encryption;
    $this->fileSystem = $file_system;
    $this->logger = $logger;
    $this->stringTranslation = $string_translation;

    $api_key = $this->encryption->decrypt($this->config->get('api_key'), TRUE);

    if (!$api_key) {
      throw new \Exception($this->t('Could not connect to HelloSign because no API key has been set.'));
    }

    $this->client = new Client($api_key);
  }

  /**
   * Gets the HelloSign client.
   *
   * @return \Hellosign\Client
   *   The HelloSign PHP SDK client.
   */
  public function getClient() {
    return $this->client;
  }

  /**
   * Helper function for creating a new HelloSign eSignature request.
   *
   * @param string $title
   *   Document title.
   * @param string $subject
   *   Email subject.
   * @param array $signers
   *   Array of signers with a key of email address and a value of name.
   * @param string $file
   *   A full path to a local system file.
   * @param string $mode
   *   (optional) The type of signature request, either "embedded" or "email".
   *
   * @throws \Exception
   *
   * @return array
   *   Contains the signature_request_id and an array of signatures.
   */
  public function createSignatureRequest($title, $subject, array $signers, $file, $mode = 'email') {
    $this->logger->debug('Generating %mode signature request %title using file %file.', [
      '%title' => $title,
      '%file' => $file,
      '%mode' => $mode,
    ]);

    // Attempt to create new signature request.
    $request = new SignatureRequest();

    // If selected, place in test mode.
    if ($this->config->get('test_mode')) {
      $request->enableTestMode();
    }

    // Set the title and the subject of the request.
    $request->setTitle($title);
    $request->setSubject($subject);

    // Add cc emails (non signers).
    $cc_emails = $this->config->get('cc_emails');
    if ($cc_emails) {
      foreach (explode(',', $cc_emails) as $cc_email) {
        $request->addCC($cc_email);
      }
    }

    // Add all signers to list.
    $signer_count = 0;
    foreach ($signers as $signer_email => $signer_name) {
      $request->addSigner(new Signer([
        'name' => $signer_name,
        'email_address' => $signer_email,
        'order' => ++$signer_count,
      ]));
    }

    // Attach the document.
    $request->addFile($file);

    // Initiate request based on mode.
    switch ($mode) {
      case 'email':
        $response = $this->client->sendSignatureRequest($request);
        break;

      case 'embedded':
        $client_id = $this->encryption->decrypt($this->config->get('client_id'), TRUE);
        if (!$client_id) {
          throw new \Exception($this->t('A HelloSign Client ID must be set in order to create embedded signature requests.'));
        }
        $embedded_request = new EmbeddedSignatureRequest($request, $client_id);
        $response = $this->client->createEmbeddedSignatureRequest($embedded_request);
        break;

      default:
        throw new \Exception($this->t('The specified signature request mode was not recognized.'));
    }

    // Check for errors.
    if ($response->hasError()) {
      throw new \Exception($this->t('An unknown error has occurred while generating the eSignature request.'));
    }

    return [
      'signature_request_id' => $response->getId(),
      'signatures' => $response->getSignatures()->toArray(),
    ];
  }

}
