<?php

namespace Drupal\quenforcer\Quotas;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity;

abstract class Quota {
  const CONFIG = 'quenforcer.settings';
  const WARNING_THESHOLD_PERCENTAGE = 75;
  const ENTITY_CLASSES = [
    'user' => 'UserQuota',
    'node' => 'StorageQuota',
  ];

  protected $limit;
  protected $currently_used_amount;

  public function __construct(Config $config) {
    $this->limit = $config->get(static::LIMIT_SETTING);
    $this->currently_used_amount = $this->calculateCurrentlyUsedAmount();
  }

  public static function getStatusPageInfo() {
    $status = REQUIREMENT_OK;
    $reports = [];

    foreach (array_keys(self::ENTITY_CLASSES) as $entity_type) {
      $quota = self::getInstanceFromEntityType($entity_type);
      if ($quota->exists()) {
        $reports[] = self::getThemedReport($quota);

        if ($quota->hasReachedWarningThreshold() && ($status < REQUIREMENT_WARNING)) {
          $status = REQUIREMENT_WARNING;
        }
        if ($quota->hasReachedLimit() && ($status < REQUIREMENT_ERROR)) {
          $status = REQUIREMENT_ERROR;
        }
      }
    }

    return [
      'title' => t('Quotas'),
      'value' => empty($reports) ? t('No quota limits have been set') : self::getStatusMessage($status),
      'severity' => $status,
      'description' => self::getThemedReports($reports),
    ];
  }

  protected static function getThemedReport(Quota $quota) {
    return [
      '#markup' => $quota->getReportSummary(),
      'children' => $quota->getReportDetails(),
    ];
  }

  protected static function getThemedReports($summaries) {
    return [
      '#theme' => 'item_list',
      '#items' => $summaries,
    ];
  }

  protected static function getStatusMessage($status) {
    switch ($status) {
      case REQUIREMENT_WARNING:
        $message = t('At least one quota is nearing its limit.');
        break;
      case REQUIREMENT_ERROR:
        $message = t('At least one quota has reached its limit!');
        break;
      default:
        $message = t('All quotas are well below their limits.');
        break;
    }
    return $message;
  }

  public static function setFormErrorIfExceeded(FormStateInterface $form_state) {
    $entity = $form_state->getFormObject()->getEntity();
    if ($entity->isNew()) {
      $quota = self::getInstanceFromEntityType($entity->getEntityTypeId());
      if ($quota->exists() && $quota->hasReachedLimit()) {
        $form_state->setErrorByName('submit', $quota->exceededMessage());
      }
    }
  }

  protected static function getInstanceFromEntityType($entity_type) {
    $quota_classes = self::ENTITY_CLASSES;
    // Remove trim() after https://netbeans.org/bugzilla/show_bug.cgi?id=240795 is fixed.
    $quota_class = trim('Drupal\quenforcer\Quotas\ ') . $quota_classes[$entity_type];
    return new $quota_class(\Drupal::config(Quota::CONFIG));
  }

  protected function getReportSummary() {
    return t($this->getHumanReadableName()) . ': ' .
      round($this->currently_used_amount) . ' / ' . $this->limit . ' ' . t(static::UNITS) .
      ' (' . $this->getPercentageUsed() . '%)';
  }

  protected function getHumanReadableName() {
    return static::HUMAN_READABLE_NAME;
  }

  protected function exists() {
    return is_null($this->limit) ? FALSE : TRUE;
  }

  protected function getPercentageUsed() {
    return round(($this->currently_used_amount / $this->limit) * 100);
  }

  protected function hasReachedWarningThreshold() {
    return $this->getPercentageUsed() >= self::WARNING_THESHOLD_PERCENTAGE;
  }

  protected function hasReachedLimit() {
    return $this->getPercentageUsed() >= 100;
  }

  abstract protected function calculateCurrentlyUsedAmount();
  abstract protected function exceededMessage();
  abstract protected function getReportDetails();
}
