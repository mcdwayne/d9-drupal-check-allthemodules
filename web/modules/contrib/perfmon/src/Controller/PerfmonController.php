<?php

namespace Drupal\perfmon\Controller;

use Drupal\Core\Controller\ControllerBase;

class PerfmonController extends ControllerBase {

  /**
   * Main perform results page callback.
   */
  public function mainPage() {
    $output = array();
    // Retrieve the testlist.
    $testlist = perfmon_get_testlist();
    // Retrieve results from last run of the testlist.
    $tests = perfmon_get_stored_results();
    // Only users with the proper permission can run the testlist.
    if (\Drupal::currentUser()
      ->hasPermission('run performance monitor checks')
    ) {
      $output += \Drupal::formBuilder()
        ->getForm('Drupal\perfmon\Form\PerfmonRunForm', $tests);
    }

    if (!empty($tests)) {
      // We have prior results, so display them.
      $output['results'] = perfmon_reviewed($testlist, $tests);
    }
    else {
      // If they haven't configured the site, prompt them to do so.
      \Drupal::messenger()
        ->addStatus($this->t('It appears this is your first time using the performance testlist.'));
    }
    return $output;
  }
}