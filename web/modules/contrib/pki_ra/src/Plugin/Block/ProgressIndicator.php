<?php

namespace Drupal\pki_ra\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\pki_ra\Processors\PKIRARegistrationProcessor;
use Drupal\user\Entity\User;

/**
 * Provides a 'Progress Indicator' block.
 *
 * @Block(
 *  id = "eoi_progress_indicator",
 *  admin_label = @Translation("EOI Sources Progress block"),
 * )
 */
class ProgressIndicator extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $account = User::load(\Drupal::currentUser()->id());
    $email = $account->get('mail')->value;
    $registration = PKIRARegistrationProcessor::getRegistrationByTitle($email);
    // If there is no registration found with the current user.
    if (empty($registration)) {
      $message = $this->t("The email address %email is not registered.", ['%email' => $email]);
      $build['content'] = [
        '#markup' => $message,
      ];
      return $build;
    }
    // Get available EOI Sources.
    $eoi_method = \Drupal::service('pki_ra.eoi_progress_manager')->getEnabledEoiSources();
    $steps = [];
    if (is_array($eoi_method) && !empty($eoi_method)) {
      $steps = $this->getEoiSourceData($eoi_method, $registration->id());
    }
    // Disable block cache.
    $build['#cache']['max-age'] = 0;
    $build['content'] = [
      '#theme' => 'user_progress_indicator',
      '#content' => $steps,
    ];
    return $build;
  }

  /**
   * Get EOI Source results and format an array with these results.
   *
   * @param string $eoi_method
   *   EOI Source.
   * @param int $registration_id
   *   Registration Id.
   * @return mixed
   */
  public function getEoiSourceData($eoi_method, $registration_id) {
    $progress_manager = \Drupal::service('pki_ra.eoi_progress_manager');
    foreach ($eoi_method as $key => $method) {
      $progress_result = $progress_manager->getEoiSourcesProgress($registration_id, $key);
      $steps[$key]['status'] = 'Pending';
      $steps[$key]['class'] = 'Verification-pending';
      $steps[$key]['label'] = $method['label'];
      $steps[$key]['url'] = $method['url'];
      if (!empty($progress_result)) {
        $steps[$key]['status'] = $progress_result->status;
        $steps[$key]['time'] = \Drupal::service('date.formatter')->format($progress_result->updated, 'medium');
        $steps[$key]['class'] = ($progress_result->status == 'Completed' || $progress_result->status == 'Pass') ? 'verification-completed' : 'verification-fail';
      }
    }
    return $steps;
  }

}
