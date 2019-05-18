<?php

namespace Drupal\contest;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderBase;

/**
 * Custom contest breadcrumb builder.
 */
class ContestBreadcrumbBuilder extends BreadcrumbBuilderBase {

  /**
   * Determine if we shoud use the contest's breadcrumb.
   *
   * @param array $attributes
   *   An array of attributes.
   *
   * @return bool
   *   True if there are contest attributes.
   */
  public function applies(array $attributes) {
    return !empty($attributes['contest']) && ($attributes['contest'] instanceof ContestInterface);
  }

  /**
   * Build a contest breadcrumb.
   *
   * @param array $attributes
   *   An array of contest attributes.
   *
   * @return array
   *   An array that will be used to build the contest breadcrumb.
   */
  public function build(array $attributes) {
    return [
      $this->l($this->t('Home'), '<front>'),
      $this->l($attributes['contest']->getTitle(), 'contest.contest_view', ['contest' => $attributes['contest']->id()]),
    ];
  }

}
