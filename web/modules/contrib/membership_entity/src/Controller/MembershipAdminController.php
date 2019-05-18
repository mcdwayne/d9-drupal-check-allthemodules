<?php
declare(strict_types=1);

namespace Drupal\membership_entity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Class MembershipAdminController.
 *
 *  Returns responses for Membership admin routes.
 */
class MembershipAdminController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Build the membership dashboard page.
   *
   * @return array
   *   A render array for the main admin dashboard.
   */
  public function adminDashboard() {
    // @TODO: Implement the dashboard.
    return array();
  }

  /**
   * Build the main membership configuration page.
   *
   * @return array
   *   A render array for the membership configuration page.
   */
  public function configPage() {
    // @TODO: Implement the config page.
    return array();
  }

  /**
   * Build the main membership reports page.
   *
   * @return array
   *   A render array for the membership reports page.
   */
  public function reportsPage() {
    // @TODO: Implement the reports page.
    return array();
  }

}
