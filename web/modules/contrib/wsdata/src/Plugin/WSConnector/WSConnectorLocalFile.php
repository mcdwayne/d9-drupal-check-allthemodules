<?php

namespace Drupal\wsdata\Plugin\WSConnector;

use Drupal\Core\Utility\Token;
use Drupal\wsdata\Plugin\WSConnectorBase;

/**
 * Local file connector.
 *
 * @WSConnector(
 *   id = "WSConnectorLocalFile",
 *   label = @Translation("Local file connector", context = "WSConnector"),
 * )
 */
class WSConnectorLocalFile extends WSConnectorBase {

  /**
   * {@inheritdoc}
   */
  public function getMethods() {
    return ['read', 'write', 'append'];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return [
      'filename' => NULL,
      'readonly' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsForm($options = []) {
    return [
      'filename' => [
        '#title' => $this->t('Filename'),
        '#type' => 'textfield',
      ],
      'readonly' => [
        '#title' => $this->t('Prevent writing to this file.'),
        '#type' => 'checkbox',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getReplacements(array $options) {
    return $this->findTokens($this->endpoint . '/' . $options['filename']);
  }

  /**
   * {@inheritdoc}
   */
  public function call($options, $method, $replacements = [], $data = NULL, array $tokens = []) {
    $filename = $this->endpoint . '/' . $options['filename'];
    $filename = $this->applyReplacements($filename, $replacements, $tokens);
    $flags = 0;
    switch ($method) {
      case 'append':
        $flags = FILE_APPEND;
      case 'write':
        if (!is_writable($filename)) {
          $this->setError(1, $this->t('%filename is not writable.', ['%filename' => $filename]));
          return FALSE;
        }
        return file_put_contents($filename, $data, $flags);

      case 'read':
      default:
        if (!file_exists($filename)) {
          $this->setError(1, $this->t('%filename does not exist.', ['%filename' => $filename]));
          return FALSE;
        }
        if (!is_readable($filename)) {
          $this->setError(1, $this->t('%filename is not readable.', ['%filename' => $filename]));
          return FALSE;
        }
        return file_get_contents($filename);
    }
  }

}
