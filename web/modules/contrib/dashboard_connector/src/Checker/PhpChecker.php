<?php

namespace Drupal\dashboard_connector\Checker;

use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Checks whether the current PHP version is supported.
 *
 * End of Life Dates
 * The most recent branches to reach end of life status are:
 *
 * 7.0:  3 Dec 2018 (1543795200)
 * 5.6: 28 Aug 2017 (1503878400)
 * 5.5: 20 Jul 2016 (1468108800)
 * 5.4:  3 Sep 2015
 * 5.3: 14 Aug 2014
 *
 * @See http://php.net/releases/index.php
 */
class PhpChecker extends CheckerBase {

  /**
   * The request time.
   *
   * @var int
   */
  protected $time;

  /**
   * The PHP Version.
   *
   * @var int
   */
  protected $phpVersion;

  /**
   * PhpChecker constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param int $php_version
   *   The PHP Version.
   */
  public function __construct(TranslationInterface $string_translation, RequestStack $request_stack, $php_version) {
    parent::__construct($string_translation);
    $this->time = (int) $request_stack->getCurrentRequest()->get('REQUEST_TIME');
    $this->phpVersion = $php_version;
  }

  /**
   * {@inheritdoc}
   */
  public function getChecks() {
    $eol     = FALSE;
    $checks  = [];
    $version = $this->phpVersion;
    $time    = $this->time;

    // Anything older than 5.5 has been end-of-lifed already.
    if ($version < 50500) {
      $eol = TRUE;
    }
    // 5.5 will be EOL 10 Jul 2016.
    elseif ($version < 50600 && $time > 1468108800) {
      $eol = TRUE;
    }
    // 5.6 will be EOL 28 Aug 2017.
    elseif ($version < 70000 && $time > 1503878400) {
      $eol = TRUE;
    }
    // Assuming the next is 7.1, 7.0 will be EOL 3 Dec 2018.
    elseif ($version < 70100 && $time > 1543795200) {
      $eol = TRUE;
    }

    if ($eol) {
      $checks[] = $this->buildCheck('php', 'version', $this->t('PHP @version is no longer maintained.', ['@version' => PHP_VERSION]), 'error');
    }
    else {
      $checks[] = $this->buildCheck('php', 'version', $this->t('Running on PHP @version.', ['@version' => PHP_VERSION]), 'notice');
    }

    return $checks;
  }

}
