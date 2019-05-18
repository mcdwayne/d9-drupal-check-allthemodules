<?php

namespace Drupal\academic_applications;

use Drupal\academic_applications\Utility\TextUtility;
use Drupal\academic_applications\Utility\ArrayElement;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebFormSubmissionInterface;

/**
 * Class SubmissionBundler converts Webform submissions into PDFs.
 */
class SubmissionBundler {

  /**
   * The workflow connector.
   *
   * @var WorkflowConnector
   */
  protected $workflowConnector;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The submission PDF finder.
   *
   * @var SubmissionPdfFinder
   */
  protected $submissionPdfFinder;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * SubmissionBundler constructor.
   *
   * @param WorkflowConnector $workflowConnector
   *   The workflow connector.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entityQuery
   *   The query factory.
   * @param SubmissionPdfFinder $submissionPdfFinder
   *   The submission PDF finder.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   */
  public function __construct(
    WorkflowConnector $workflowConnector,
    QueryFactory $entityQuery,
    SubmissionPdfFinder $submissionPdfFinder,
    FileSystemInterface $fileSystem
  ) {
    $this->workflowConnector = $workflowConnector;
    $this->entityQuery = $entityQuery;
    $this->submissionPdfFinder = $submissionPdfFinder;
    $this->fileSystem = $fileSystem;
  }

  /**
   * Converts submission field data and attached PDFs into a single PDF.
   *
   * @param \Drupal\webform\WebFormSubmissionInterface $submission
   *   A Webform submission.
   *
   * @return string
   *   The filesystem path to the bundled file.
   */
  public function bundle(WebFormSubmissionInterface $submission) {

    $uris[] = $this->submissionToPdf($submission);

    $file_ids = [];

    // The application form submission.
    $file_ids = array_merge($file_ids, $this->submissionPdfFinder->getFileIds($submission));

    // The related upload form submissions.
    foreach ($this->uploadFormSubmissions($submission) as $upload_submission) {
      $file_ids = array_merge($file_ids, $this->submissionPdfFinder->getFileIds($upload_submission));
    }
    $uris = array_merge($uris, $this->fileUris($file_ids));
    $name = !empty($submission->getData()['name']) ? '-' . $submission->getData()['name'] : '';
    $name = preg_replace('/[^a-zA-Z]+/', '-', strtolower($name));
    $outputFilename = $submission->getWebForm()->id() . '-' . $submission->id() . $name . '.pdf';
    $dir = 'private://academic_applications';
    $this->fileSystem->mkdir($dir);
    $outputUri = "$dir/$outputFilename";
    $this->concatenatePdfs($uris, $outputUri);

    return $this->fileSystem->realpath($outputUri);
  }

  /**
   * Concatenates PDF files.
   *
   * @param array $sourceUris
   *   URIs of PDF files.
   * @param string $outputUri
   *   The output file URI.
   *
   * @return int
   *   The GhostScript return code.
   */
  protected function concatenatePdfs(array $sourceUris, $outputUri) {
    $command_paths = '';
    foreach ($sourceUris as $uri) {
      $command_paths .= escapeshellarg($this->fileSystem->realpath($uri)) . ' ';
    }
    // @todo Inject the configuration.
    $config = \Drupal::config('academic_applications.settings');
    $executable = $config->get('ghostscript_path');
    $command = sprintf('%s -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=%s %s', $executable, escapeshellarg($this->fileSystem->realpath($outputUri)), $command_paths);
    $output = [];
    exec($command, $output, $return);
    return $return;
  }

  /**
   * Determines real paths affiliated with file IDs.
   *
   * @param array $file_ids
   *   File entity IDs.
   *
   * @return array
   *   Real filesystem paths to files.
   */
  protected function fileUris(array $file_ids) {
    /** @var \Drupal\file\Entity\File[] $files */
    $files = File::loadMultiple($file_ids);
    $uris = [];
    foreach ($files as $file) {
      $uris[] = $file->getFileUri();
    }
    return $uris;
  }

  /**
   * Finds the upload submissions affiliated with an application submission.
   *
   * @param \Drupal\webform\WebFormSubmissionInterface $webFormSubmission
   *   A Web form submission.
   *
   * @return \Drupal\webform\WebFormSubmissionInterface[]
   *   Upload submissions.
   */
  public function uploadFormSubmissions(WebFormSubmissionInterface $webFormSubmission) {
    $workflowMap = $this->workflowConnector->workflowMap();
    $related_submissions = [];
    if (isset($workflowMap[$webFormSubmission->getWebForm()->id()])) {
      $query = $this->entityQuery->get('webform_submission', 'AND')
        ->condition('webform_id', $workflowMap[$webFormSubmission->getWebForm()->id()]);
      $submission_ids = $query->execute();
      $related_submissions = [];
      foreach (WebFormSubmission::loadMultiple($submission_ids) as $submission) {
        /* @var WebformSubmissionInterface $submission */
        if ($submission->getElementData('wt') == $webFormSubmission->uuid()) {
          $related_submissions[] = $submission;
        }
      }
    }

    return $related_submissions;
  }

  /**
   * Converts a submission to a PDF.
   *
   * @param \Drupal\webform\WebFormSubmissionInterface $webFormSubmission
   *   Webform submission.
   *
   * @return string
   *   The PDF URI.
   */
  protected function submissionToPdf(WebFormSubmissionInterface $webFormSubmission) {
    $outputFilename = $webFormSubmission->getWebForm()->id() . '-' . $webFormSubmission->id() . '-results.pdf';
    $outputUri = 'temporary://' . $outputFilename;
    $pdf = new \FPDF('P', 'in', 'Letter');
    $pdf->addPage();
    $pdf->SetFont('Arial', '', 10);
    $this->submissionResultsToPdf($pdf, $webFormSubmission);
    file_unmanaged_save_data($pdf->Output($outputFilename, 'S'), $outputUri, FILE_EXISTS_REPLACE);
    return $outputUri;
  }

  /**
   * Writes a single form submission result into the PDF.
   *
   * @param \FPDF $pdf
   *   An FPDF.
   * @param mixed $result
   *   An single Webform submission result.
   */
  protected function submissionResultToPdf(\FPDF $pdf, $result) {
    $pdf->SetFont('', '');
    $line = is_array($result) ? ArrayElement::resultToString($result) : $result;
    $pdf->Write(0.2, ': ' . TextUtility::textPdfEncode($line));
  }

  /**
   * Left-indents fields in the PDF to the specified depth.
   *
   * @param \FPDF $pdf
   *   An FPDF.
   * @param int $depth
   *   The indentation depth.
   */
  protected function pdfIndent(\FPDF $pdf, $depth = 0) {
    $i = 0;
    while ($i < $depth) {
      $pdf->Write(0.2, "\t\t\t\t");
      $i++;
    }
  }

  /**
   * Writes form submission results into the PDF.
   *
   * @param \FPDF $pdf
   *   An FPDF.
   * @param \Drupal\webform\WebFormSubmissionInterface $webFormSubmission
   *   A Webform submission.
   */
  protected function submissionResultsToPdf(\FPDF $pdf, WebFormSubmissionInterface $webFormSubmission) {
    $results = $webFormSubmission->getData();
    foreach ($webFormSubmission->getWebForm()->getElementsInitializedAndFlattened() as $machine_name => $element) {
      $pdf->SetFont('', 'B');
      $this->pdfIndent($pdf, $element['#webform_depth']);
      $pdf->Write(0.2, $element['#title']);
      if (isset($results[$machine_name])) {
        $this->submissionResultToPdf($pdf, $results[$machine_name]);
      }
      $pdf->ln();
    }
  }

}
