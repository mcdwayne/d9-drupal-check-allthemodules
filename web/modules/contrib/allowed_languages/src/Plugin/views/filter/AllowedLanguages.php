<?php

namespace Drupal\allowed_languages\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter content by the current users allowed languages.
 *
 * @ViewsFilter("allowed_languages")
 */
class AllowedLanguages extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    return $this->t('Current users allowed languages');
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $user = allowed_languages_get_current_user();
    $allowed_languages = allowed_languages_get_allowed_languages_for_user($user);

    if ($allowed_languages) {
      $this->ensureMyTable();

      $field = "{$this->tableAlias}.{$this->realField}";
      $value = array_values($allowed_languages);

      $this->query->addWhere($this->options['group'], $field, $value, 'IN');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    $contexts[] = 'user';

    return $contexts;
  }

}
