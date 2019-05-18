<?php

namespace Drupal\business_rules;

use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\system\PathBasedBreadcrumbBuilder;

/**
 * Adjust the breadcrumbs for the Business Rules module.
 *
 * @package Drupal\business_rules
 */
class BusinessRulesBreadcrumb extends PathBasedBreadcrumbBuilder {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    if (stristr($route_name, 'entity.business_rule')) {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    /** @var \Drupal\Core\Breadcrumb\Breadcrumb $breadcrumb */
    $breadcrumb = parent::build($route_match);
    $route_name = $route_match->getRouteName();
    $breadcrumb->addLink(Link::createFromRoute($this->t('Business Rules'), 'entity.business_rule.collection'));

    if (stristr($route_name, 'entity.business_rules_action')) {
      $breadcrumb->addLink(Link::createFromRoute($this->t('Actions'), 'entity.business_rules_action.collection'));
    }
    elseif (stristr($route_name, 'entity.business_rules_condition')) {
      $breadcrumb->addLink(Link::createFromRoute($this->t('Conditions'), 'entity.business_rules_condition.collection'));
    }
    elseif (stristr($route_name, 'entity.business_rules_variable')) {
      $breadcrumb->addLink(Link::createFromRoute($this->t('Variables'), 'entity.business_rules_variable.collection'));
    }

    return $breadcrumb;
  }

}
