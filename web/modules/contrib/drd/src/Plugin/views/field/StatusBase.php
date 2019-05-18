<?php

namespace Drupal\drd\Plugin\views\field;

use Drupal\Core\Render\Markup;
use Drupal\drd\Entity\BaseInterface;
use Drupal\drd\Entity\DomainInterface;
use Drupal\drd\Entity\Requirement;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Abstract field handler to display status indicator for host, core and domain.
 */
abstract class StatusBase extends FieldPluginBase implements StatusBaseInterface {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['hide_alter_empty'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\drd\Entity\BaseInterface $remote */
    $remote = $values->_entity;
    if ($remote instanceof DomainInterface && !$remote->isInstalled()) {
      return '';
    }

    $warnings = $this->getCategories($remote, 'warnings');
    $errors = $this->getCategories($remote, 'errors');

    $allCategories = Requirement::getCategoryKeys();

    $output = '';
    foreach ($allCategories as $category) {
      $class = [$category];
      if (in_array($category, $errors)) {
        $class[] = 'error';
      }
      elseif (in_array($category, $warnings)) {
        $class[] = 'warning';
      }
      else {
        $class[] = 'ok';
      }
      $output .= '<span title="' . $category . '" class="' . implode(' ', $class) . '">' . $category . '</span>';
    }
    return Markup::create('<div class="drd-remote-status">' . $output . '</div>');
  }

  /**
   * Get aggregated warnings and error for a remote entity.
   *
   * @param \Drupal\drd\Entity\BaseInterface $remote
   *   The remote DRD entity.
   * @param string $field
   *   Either "warnings" or "errors".
   *
   * @return array
   *   List of categories in which the entity has errors or warnings.
   */
  private function getCategories(BaseInterface $remote, $field) {
    $ids = [];
    foreach ($this->getDomains($remote) as $domain) {
      foreach ($domain->get($field)->getValue() as $value) {
        $ids[] = $value['target_id'];
      }
    }

    if (empty($ids)) {
      return [];
    }

    /* @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = \Drupal::database()->select('drd_requirement', 'r')
      ->fields('r', ['category'])
      ->distinct()
      ->condition('r.id', $ids, 'IN');
    return $query
      ->execute()
      ->fetchAllKeyed(0, 0);
  }

}
