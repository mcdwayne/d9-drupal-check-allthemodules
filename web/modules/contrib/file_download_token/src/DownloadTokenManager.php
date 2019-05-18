<?php

namespace Drupal\file_download_token;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\file\FileInterface;

class DownloadTokenManager implements DownloadTokenManagerInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * DownloadTokenManager constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $connection) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function createToken(FileInterface $file) {
    try {
      $token = $this->generateToken();

      $this->connection
        ->insert('download_tokens')
        ->fields([
          'fid' => $file->id(),
          'token' => $token,
          'timestamp' => \Drupal::time()->getCurrentTime(),
        ])
        ->execute();

      return $token;
    }
    catch (\Exception $e) {
      // @todo: Handle exception.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createTokenUrl(FileInterface $file) {
    $token = $this->createToken($file);
    return Url::fromRoute('file_download_token.download_file', ['token' => $token], ['absolute' => TRUE]);
  }

  /**
   * {@inheritdoc}
   */
  public function createTokenLink(FileInterface $file) {
    return Link::fromTextAndUrl($this->t('Download @filename', ['@filename' => $file->label()]), $this->createTokenUrl($file));
  }

  /**
   * {@inheritdoc}
   */
  public function generateToken() {
    return Crypt::randomBytesBase64(55);
  }

  /**
   * {@inheritdoc}
   */
  public function getFile(string $token) {
    $result = $this->connection->select('download_tokens', 'dt')
      ->fields('dt')
      ->condition('token', $token)
      ->execute();

    if ($record = $result->fetch()) {
      $file = $this->entityTypeManager->getStorage('file')->load($record->fid);
      return $file;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cleanupTokens() {
    $timestamp = \Drupal::time()->getCurrentTime();
    // We delete all tokens older than 24 hours.
    $timestamp -= 24*60*60;
    $this->deleteTokensOlderThan($timestamp);
  }

  /**
   * @param int $timestamp
   */
  public function deleteTokensOlderThan($timestamp) {
    if ($timestamp == NULL) {
      $timestamp = \Drupal::time()->getCurrentTime();
    }

    $results = $this->connection->delete('download_tokens')
      ->condition('timestamp', $timestamp, '>=')
      ->execute();
  }

}