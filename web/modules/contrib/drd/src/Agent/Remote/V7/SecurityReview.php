<?php

namespace Drupal\drd\Agent\Remote\V7;

class SecurityReview {

  /**
   * @return array
   */
  static public function collect() {
    $review = array();

    if (module_exists('security_review')) {
      module_load_include('inc', 'security_review');
      $checklist = security_review_get_checklist();

      // Only check once per day
      if (REQUEST_TIME - variable_get('security_review_last_run', 0) > 86400) {
        $skipped = security_review_skipped_checks();
        // Remove checks that are being skipped.
        if (!empty($skipped)) {
          foreach ($skipped as $module => $checks) {
            foreach ($checks as $check_name => $check) {
              unset($checklist[$module][$check_name]);
            }
            if (empty($checklist[$module])) {
              unset($checklist[$module]);
            }
          }
        }

        // Run and store the checklist.
        security_review_run_store($checklist, FALSE);
      }

      $checks = security_review_get_stored_results();
      if (!empty($checks)) {
        module_load_include('inc', 'security_review', 'security_review.pages');
        $review['security_review'] = array(
          'title' => t('Security Review'),
          'result' => security_review_reviewed($checklist, $checks),
        );
      }

    }

    return $review;
  }

}
