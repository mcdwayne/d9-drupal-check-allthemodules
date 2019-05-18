<?php

namespace Drupal\fillpdf;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an interface to allow parsing and building FillPDF Links.
 *
 * A guideline for functionality is that calling generateLink on the result
 * of parseRequest should return a string that would parse the same way as the
 * original one.
 */
interface FillPdfLinkManipulatorInterface {

  /**
   * Parses a request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request containing the query string to parse.
   *
   * @return array
   *   The FillPDF request context as returned by parseLink().
   *
   * @see \Drupal\fillpdf\FillPdfLinkManipulatorInterface::parseLink()
   *
   * @todo: Move this elsewhere, maybe to that current_fillpdf_link service I was thinking of or whatever it was.
   */
  public function parseRequest(Request $request);

  /**
   * Parses a root-relative URL.
   *
   * @param string $url
   *   The root-relative FillPDF URL that would be used to generate the PDF.
   *   e.g. '/fillpdf?fid=1&entity_type=node&entity_id=1'.
   *
   * @return array
   *   The FillPDF request context as returned by parseLink().
   *
   * @see \Drupal\fillpdf\FillPdfLinkManipulatorInterface::parseLink()
   */
  public function parseUrlString($url);

  /**
   * Parses a Url object.
   *
   * @param \Drupal\Core\Url $link
   *   The valid URL containing the FillPDF generation metadata.
   *   e.g. 'http://example.com/fillpdf?entities[]=node:1&entities[]=contact:7'.
   *
   * @return array
   *   An associative array representing the request context and containing the
   *   following properties:
   *     fid: int ID of the FillPDF form.
   *     sample: true|null Flag indicating if a sample file is to be generated.
   *       TRUE if yes, otherwise NULL.
   *     entity_ids: string[] Array of entity_type:entity_id pairs to populate
   *       the fields with. Will otherwise contain an empty array.
   *     force_download: true|null Flag indicating if the populated file should
   *       always be downloaded. TRUE if yes, otherwise NULL.
   *     flatten: false|null Flag indicating if the populated file should
   *       be flattened. FALSE if not, otherwise NULL.
   */
  public static function parseLink(Url $link);

  /**
   * Generates a FillPdf Url from the given parameters.
   *
   * @param array $parameters
   *   The array of parameters to be converted into a
   *   URL and query string.
   *
   * @return \Drupal\Core\Url
   *   Url object.
   */
  public function generateLink(array $parameters);

}
