<?php

namespace Drupal\entity_access_audit\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\entity_access_audit\AccessAuditResult;
use Drupal\entity_access_audit\EntityAccessAuditManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Common features of the audit controllers.
 */
abstract class AuditControllerBase extends ControllerBase {

  /**
   * The audit manager.
   *
   * @var \Drupal\entity_access_audit\EntityAccessAuditManager
   */
  protected $auditManager;

  /**
   * Create an instance of AuditController.
   */
  public function __construct(EntityAccessAuditManager $auditManager) {
    $this->auditManager = $auditManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_access_audit.access_checker')
    );
  }

  /**
   * Format an access audit result.
   *
   * @param \Drupal\entity_access_audit\AccessAuditResult $result
   *   The access audit result.
   *
   * @return array
   *   A formatted audit access result.
   */
  protected function formatAccessAuditResult(AccessAuditResult $result) {
    $access_result = $result->getAccessResult();
    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => !$access_result->isAllowed() ? new FormattableMarkup('<span style="color:red;">✘</span>', []) : new FormattableMarkup('<span style="color:#22bf22;">✔</span>', []),
      '#attributes' => [
        'class' => [
        ],
      ],
    ];
  }

}
