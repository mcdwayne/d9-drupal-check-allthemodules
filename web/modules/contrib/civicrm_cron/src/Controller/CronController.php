<?php

namespace Drupal\civicrm_cron\Controller;

use CRM_Core_JobManager;
use CRM_Utils_System;
use Drupal\civicrm\Civicrm;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CronController.
 */
class CronController extends ControllerBase {

  /**
   * CiviCRM service.
   *
   * @var \Drupal\civicrm\Civicrm
   */
  protected $civicrm;

  /**
   * CronController constructor.
   *
   * @param \Drupal\civicrm\Civicrm $civicrm
   *   CiviCRM service.
   */
  public function __construct(Civicrm $civicrm) {
    $this->civicrm = $civicrm;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('civicrm'));
  }

  /**
   * Runs CiviCRM cron.
   *
   * Consumers of this endpoint should expect an HTTP response with a non-empty
   * value for the "X-CiviCRM-Cron" header.
   */
  public function runCron() {
    $this->civicrm->initialize();

    $config = $this->config('civicrm_cron.settings');

    if ($username = $config->get('username')) {
      CRM_Utils_System::authenticateScript(TRUE, $username, $config->get('password'));
    }
    else {
      CRM_Utils_System::authenticateScript(FALSE);
    }

    $facility = new CRM_Core_JobManager();
    $facility->execute();

    // Prevents caching of the entire page/request and allows us to add a header
    // indicating that the cron run was successful.
    $response = new Response();
    $response->setContent('CiviCRM cron run successful.');
    $response->headers->set('X-CiviCRM-Cron', TRUE);

    return $response;
  }

}
