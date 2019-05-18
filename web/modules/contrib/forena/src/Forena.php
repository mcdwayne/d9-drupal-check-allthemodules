<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/9/2017
 * Time: 1:58 PM
 */

namespace Drupal\forena;

class Forena {
  use FrxAPI;
  static protected $instance;

  /**
   * Singleton
   * @return static
   */
  public static function service() {
    if (static::$instance === NULL) {
      static::$instance = new static();
    }
    return static::$instance;
  }

  public function report($report, $parms = []) {
    $content = ReportManager::instance()->report($report, $parms);
    return $content;
  }

  public function setContext($id, $data) {
    $this->setDataContext($id, $data);
  }

  /**
   * @param string $report
   *   Name of the report to run with format.
   * @param array $parms
   *   Parameters to apply to report
   * @return string
   *   The report text.
   */
  public function runReport($report, $parms = []) {
    $content = \Drupal\forena\ReportManager::instance()->report($report, $parms);
    $d = \Drupal\forena\DocManager::instance();
    $doc_type = $d->getDocumentType();
    $email_override = \Drupal::config('forena.settings')->get('email_override');
    $user = \Drupal::currentUser();
    $email = $email_override ? $user->getEmail() : '';

    switch ($doc_type) {
      case 'drupal':
        $content = $content['report']['#template'];
        break;
      case 'email':
        /** @var \Drupal\forena\FrxPlugin\Document\EmailMerge $merge */
        $merge = $d->getDocument();
        $merge->sendMail($email, 0);
        break;
    }
    return $content;
  }

}