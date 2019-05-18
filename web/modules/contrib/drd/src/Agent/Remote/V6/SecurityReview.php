<?php

namespace Drupal\drd\Agent\Remote\V6;

class SecurityReview {

  /**
   * @return array
   */
  static public function collect() {
    $review = array();

    if (module_exists('security_review')) {
      module_load_include('inc', 'security_review');
      $checklist = module_invoke_all('security_checks');

      $skipped = security_review_skipped_checks();
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

      // Run the checklist.
      $checklist_results = security_review_run($checklist, FALSE);
      security_review_store_results($checklist_results);

      // Retrieve results from last run of the checklist.
      $checks = array();
      $results = db_query("SELECT namespace, reviewcheck, result, lastrun, skip, skiptime, skipuid FROM {security_review}");
      while ($result = db_fetch_array($results)) {
        $checks[] = $result;
      }
      if (!empty($checks)) {
        $review['security_review'] = array(
          'title' => t('Security Review'),
          'result' => security_review_reviewed($checklist, $checks),
        );
      }

    }

    return $review;
  }

}
