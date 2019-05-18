<?php

namespace Drupal\academic_applications\Controller;

use Drupal\academic_applications\SubmissionBundler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\WebFormSubmissionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class BundleController.
 */
class BundleController extends ControllerBase {

  /**
   * The bundler.
   *
   * @var \Drupal\academic_applications\SubmissionBundler
   */
  protected $bundler;

  /**
   * BundleController constructor.
   *
   * @param \Drupal\academic_applications\SubmissionBundler $bundler
   *   A PDF handler.
   */
  public function __construct(SubmissionBundler $bundler) {
    $this->bundler = $bundler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('academic_applications.submission_bundler'));
  }

  /**
   * The title callback.
   */
  public function title(WebFormSubmissionInterface $webform_submission) {
    return $this->t('Webform Submission #@id Bundle', ['@id' => $webform_submission->id()]);
  }

  /**
   * The page action for bundling.
   */
  public function bundleAction(WebFormSubmissionInterface $webform_submission) {

    $file = $this->bundler->bundle($webform_submission);
    if (file_exists($file)) {
      header('Content-Description: File Transfer');
      header('Content-Type: application/pdf');
      header('Content-Disposition: inline; filename=' . basename($file));
      header('Expires: 0');
      header('Cache-Control: must-revalidate');
      header('Pragma: public');
      header('Content-Length: ' . filesize($file));
      readfile($file);
      exit();
    }
    throw new NotFoundHttpException();
  }

}
