<?php

namespace Drupal\bibcite;

use ADCI\FullNameParser\Parser;

/**
 * Human name parser service.
 */
class HumanNameParser implements HumanNameParserInterface {

  /**
   * Parser object.
   *
   * @var \ADCI\FullNameParser\Parser
   */
  protected $parser;

  /**
   * HumanNameParser constructor.
   */
  public function __construct() {
    $this->parser = new Parser([
      'mandatory_last_name' => FALSE,
    ]);
  }

  /**
   * Parse the name into its constituent parts.
   *
   * @param string $name
   *   Human name string.
   *
   * @return array
   *   Parsed name parts.
   *
   * @throws \ADCI\FullNameParser\Exception\NameParsingException
   */
  public function parse($name) {
    $parsed_name = $this->parser->parse($name);

    return [
      'leading_title' => $parsed_name->getLeadingInitial(),
      'prefix' => $parsed_name->getAcademicTitle(),
      'first_name' => $parsed_name->getFirstName(),
      'middle_name' => $parsed_name->getMiddleName(),
      'last_name' => $parsed_name->getLastName(),
      'nick' => $parsed_name->getNicknames(),
      'suffix' => $parsed_name->getSuffix(),
    ];
  }

}
