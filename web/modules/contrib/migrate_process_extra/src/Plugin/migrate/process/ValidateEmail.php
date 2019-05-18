<?php

namespace Drupal\migrate_process_extra\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Checks if the mail syntax is correct.
 *
 * @MigrateProcessPlugin(
 *   id = "validate_email"
 * )
 */
class ValidateEmail extends ProcessPluginBase {

  /**
   * Checks DNS based on MX and fallback on A (or AAAA) record.
   *
   * @param string $email
   *   Email address.
   *
   * @return bool
   *   The MX or the A record is valid.
   */
  private function checkDns($email) {
    // Use the input to check DNS if we cannot extract something similar
    // to a domain.
    $host = $email;
    // Arguable pattern to extract the domain.
    // Not aiming to validate the domain nor the email.
    if (FALSE !== $lastAtPos = strrpos($email, '@')) {
      $host = substr($email, $lastAtPos + 1);
    }
    $host = rtrim($host, '.') . '.';
    $resultA = TRUE;
    $resultMX = checkdnsrr($host, 'MX');
    if (!$resultMX) {
      $resultA = checkdnsrr($host, 'A') || checkdnsrr($host, 'AAAA');
    }
    return $resultMX || $resultA;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = trim($value);
    if (\Drupal::service('email.validator')->isValid($value)) {
      if (isset($this->configuration['extra_validator'])) {
        // Egulias\EmailValidator\Validation\DNSCheckValidation should be used
        // but Drupal Core 8.3.x uses 1.2.* and DNSCheckValidation is available
        // from > releases (currently 2.1.*). So using private method inspired
        // from this class instead.
        //
        // Extra validator is currently unique but we let the option open on
        // another release by defining 'extra_validator' config by name and not
        // a boolean value on 'dns'.
        if ($this->configuration['extra_validator'] == 'dns') {
          if ($this->checkDns($value)) {
            return $value;
          }
          else {
            throw new MigrateException(sprintf('DNS check not valid for %s mail.', var_export($value, TRUE)));
          }
        }
        else {
          throw new MigrateException(sprintf('The %s validator is not implemented.', var_export($value, TRUE)));
        }
      }
      else {
        return $value;
      }
    }
    else {
      throw new MigrateException(sprintf('%s is not a valid mail.', var_export($value, TRUE)));
    }
  }

}
