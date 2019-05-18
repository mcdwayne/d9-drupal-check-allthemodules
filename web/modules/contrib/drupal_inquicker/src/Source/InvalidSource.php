<?php

namespace Drupal\drupal_inquicker\Source;

/**
 * A source which is invalid.
 */
class InvalidSource extends Source {

  /**
   * Constructor.
   *
   * @param string $key
   *   The key of this source, its identifier, for example: "default".
   * @param array $config
   *   The configuration for this source as defined in settings.php (see
   *   ./README.md) which will contain information required by subclasses,
   *   such as the API key.
   * @param string $reason
   *   Why this source is invalid.
   */
  public function __construct(string $key, array $config, string $reason) {
    $this->reason = $reason;
    parent::__construct($key, $config);
  }

  /**
   * {@inheritdoc}
   */
  public function live() : bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function response($uri, $options = []) {
    throw new \Exception('An invalid source cannot get a response.');
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    throw new \Exception($this->t('This source is invalid because @r; see ./README.md: !d', [
      '@r' => $this->reason,
      '!d' => $this->jsonEncode($this->getConfig()),
    ]));
  }

}
