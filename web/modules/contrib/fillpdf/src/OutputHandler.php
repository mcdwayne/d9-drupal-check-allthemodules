<?php

namespace Drupal\fillpdf;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileInterface;
use Drupal\fillpdf\Component\Utility\FillPdf;
use Drupal\fillpdf\Entity\FillPdfFileContext;
use Psr\Log\LoggerInterface;

/**
 * Class OutputHandler.
 *
 * @package Drupal\fillpdf
 */
class OutputHandler implements OutputHandlerInterface {

  use StringTranslationTrait;

  /**
   * The FillPDF token resolver.
   *
   * @var \Drupal\fillpdf\TokenResolverInterface
   */
  protected $tokenResolver;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The FillPdf link manipulator.
   *
   * @var \Drupal\fillpdf\FillPdfLinkManipulatorInterface
   */
  protected $linkManipulator;

  /**
   * OutputHandler constructor.
   *
   * @param \Drupal\fillpdf\TokenResolverInterface $token_resolver
   *   The FillPdf token resolver.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\fillpdf\FillPdfLinkManipulatorInterface $link_manipulator
   *   The FillPdf link manipulator.
   */
  public function __construct(TokenResolverInterface $token_resolver, LoggerInterface $logger, FillPdfLinkManipulatorInterface $link_manipulator) {
    $this->tokenResolver = $token_resolver;
    $this->logger = $logger;
    $this->linkManipulator = $link_manipulator;
  }

  /**
   * {@inheritdoc}
   */
  public function savePdfToFile(array $configuration, $destination_path_override = NULL) {
    /** @var \Drupal\fillpdf\Entity\FillPdfForm $fillpdf_form */
    $fillpdf_form = $configuration['form'];

    // @todo: Rename 'token_objects' to 'entities' in FillPDF 5.x. Webform
    // submissions are now entities, too.
    /** @var \Drupal\Core\Entity\EntityInterface[] $entities */
    $entities = $configuration['token_objects'];

    $destination_path = 'fillpdf';
    if (!empty($destination_path_override)) {
      $destination_path .= "/{$destination_path_override}";
    }
    elseif (!empty($fillpdf_form->destination_path->value)) {
      $destination_path .= "/{$fillpdf_form->destination_path->value}";
    }

    $resolved_destination_path = $this->processDestinationPath(trim($destination_path), $entities, $fillpdf_form->scheme->value);
    $path_exists = file_prepare_directory($resolved_destination_path, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    $saved_file = FALSE;
    if ($path_exists === FALSE) {
      $this->logger->critical($this->t("The path %destination_path does not exist and could not be
      automatically created. Therefore, the previous submission was not saved. If
      the URL contained download=1, then the PDF was still sent to the user's browser.
      If you were redirecting them to the PDF, they were sent to the homepage instead.
      If the destination path looks wrong and you have used tokens, check that you have
      used the correct token and that it is available to FillPDF at the time of PDF
      generation.",
        ['%destination_path' => $resolved_destination_path]));
    }
    else {
      // Full steam ahead!
      $saved_file = file_save_data($configuration['data'], "{$resolved_destination_path}/{$configuration['filename']}", FILE_EXISTS_RENAME);
      $this->rememberFileContext($saved_file, $configuration['context']);
    }

    return $saved_file;
  }

  /**
   * Processes the destination path.
   *
   * @param string $destination_path
   *   The raw destination path, possibly containing unresolved tokens.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entities to be used for replacing tokens.
   * @param string $scheme
   *   (optional) The storage scheme. Defaults to 'public'.
   *
   * @return string
   *   The normalized URI
   */
  protected function processDestinationPath($destination_path, array $entities, $scheme = 'public') {
    $destination_path = $this->tokenResolver->replace($destination_path, $entities);
    return FillPdf::buildFileUri($scheme, $destination_path);
  }

  /**
   * Saves the file context.
   *
   * @param \Drupal\file\FileInterface $fillpdf_file
   *   File object containing the generated PDF file.
   * @param array $context
   *   An associative array representing the context of the generated file.
   *   This array should match the format returned by
   *   FillPdfLinkManipulator::parseLink().
   *
   * @see \Drupal\fillpdf\FillPdfLinkManipulatorInterface::parseLink()
   * @see FileFieldItemList::postSave()
   */
  protected function rememberFileContext(FileInterface $fillpdf_file, array $context) {
    $fillpdf_link = $this->linkManipulator->generateLink($context);

    $fillpdf_file_context = FillPdfFileContext::create([
      'file' => $fillpdf_file,
      'context' => $fillpdf_link->toUriString(),
    ]);

    // The file field will automatically add file usage information upon save.
    $fillpdf_file_context->save();
  }

}
