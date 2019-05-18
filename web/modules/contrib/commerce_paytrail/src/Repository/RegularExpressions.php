<?php

declare(strict_types = 1);

namespace Drupal\commerce_paytrail\Repository;

/**
 * Provides regular expressions used to filter/validate.
 */
final class RegularExpressions {

  /**
   * Regex for default string validation.
   *
   * @var string
   *
   * @note Regex taken from Paytrail documentation.
   * @see http://docs.paytrail.com/en/index-all.html#payment-api.e2
   */
  public const VALIDATE_TEXT_DEFAULT = '/^[\pL\-0-9- "\', \(\)\[\]{}*\/ + \-_,.:&!?@#$£=*;~]*$/u';

  /**
   * Regex for strict string validation.
   *
   * @var string
   *
   * @note Regex taken from Paytrail documentation.
   * @see http://docs.paytrail.com/en/index-all.html#payment-api.e2
   */
  public const VALIDATE_TEXT_STRICT = '/^[\pL\-0-9- "\', ()\[\]{}*+\-_,.]*$/u';

  /**
   * Regex to sanitize string text.
   *
   * @var string
   */
  public const SANITIZE_TEXT_DEFAULT = '/[^\pL\-0-9- "\', ()\[\]{}*\/ + \-_,.:&!?@#$£=*;~]*/u';

  /**
   * Regex to sanitize strict text.
   *
   * @var string
   */
  public const SANITIZE_TEXT_STRICT = '/[^\pL\-0-9- "\', ()\[\]{}*+\-_,.]*/u';

}
