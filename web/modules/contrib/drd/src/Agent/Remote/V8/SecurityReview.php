<?php

namespace Drupal\drd\Agent\Remote\V8;

use Drupal\security_review\Controller\ChecklistController;
use Drupal\Core\Session\UserSession;

/**
 * Implements the SecurityReview class.
 */
class SecurityReview {

  /**
   * Collect the security review results.
   *
   * @return array
   *   List of all the security review results.
   */
  public static function collect() {
    $moduleManager = \Drupal::moduleHandler();

    $review = [];

    if ($moduleManager->moduleExists('security_review')) {
      /** @var \Drupal\security_review\SecurityReview $security_review */
      $security_review = \Drupal::service('security_review');

      // Only check once per day.
      if (\Drupal::time()->getRequestTime() - $security_review->getLastRun() > 86400) {
        /** @var \Drupal\Core\Session\AccountSwitcherInterface $switcher */
        $switcher = \Drupal::service('account_switcher');
        $switcher->switchTo(new UserSession(['uid' => 1]));

        /** @var \Drupal\security_review\Checklist $checklist */
        $checklist = \Drupal::service('security_review.checklist');
        $checklist->runChecklist();

        $switcher->switchBack();
      }

      $clc = ChecklistController::create(\Drupal::getContainer());
      $review['security_review'] = [
        'title' => t('Security Review'),
        'result' => $clc->results(),
      ];

    }

    return $review;
  }

}
