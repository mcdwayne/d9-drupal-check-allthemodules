<?php

namespace Drupal\formazing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\formazing\Entity\FormazingEntity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FormSubmissionController extends ControllerBase {

  /**
   * @param ContentEntityInterface|null $form
   *
   * @return Response
   */
  public function exportCsv(ContentEntityInterface $form) {
    if (!$form instanceof FormazingEntity) {
      return new Response($this->t('Could not find a form!'), Response::HTTP_NOT_FOUND);
    }

    $file = \Drupal::service('formazing.submission_exporter')->exportCsv($form);

    if (!$file) {
      \Drupal::messenger()
        ->addError($this->t('We couldn\'t find any values to export for this form'));
      return $this->redirect('entity.formazing_entity.collection');
    }

    $response = new BinaryFileResponse($file, Response::HTTP_OK, [
      'Content-Encoding' => 'UTF-8',
      'Content-type' => 'text/csv; charset=UTF-8',
    ]);
    $response->setContentDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      'formazing_' . preg_replace('/[^a-z0-9]+/', '-', strtolower($form->getName())) . '_export.csv'
    );

    return $response;
  }
}
