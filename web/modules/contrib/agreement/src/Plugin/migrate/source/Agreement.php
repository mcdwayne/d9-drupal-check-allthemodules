<?php

namespace Drupal\agreement\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Agreement migrate source plugin.
 *
 * @MigrateSource(
 *   id = "agreement",
 *   source_module = "agreement",
 * )
 */
class Agreement extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['id' => ['type' => 'integer']];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Unique Identifier'),
      'uid' => $this->t('User Identifier'),
      'sid' => $this->t('Session Identifier'),
      'agreed' => $this->t('Agreed?'),
      'agreed_date' => $this->t('Agreement timestamp'),
    ];

    if ($this->needsAgreementType()) {
      $fields['type'] = $this->t('Agreement type');
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $fields = $this->fields();
    return $this->select('agreement', 'agreement')
      ->fields('agreement', array_keys($fields));
  }

  /**
   * Checks the version applied to the migration.
   *
   * @return bool
   *   TRUE if the version needs agreement type field.
   */
  protected function needsAgreementType() {
    $version = isset($this->configuration['version']) ? (int) $this->configuration['version'] : 7;
    return $version === 7;
  }

}
