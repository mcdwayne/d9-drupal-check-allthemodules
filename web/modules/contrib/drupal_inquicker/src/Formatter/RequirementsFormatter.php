<?php

namespace Drupal\drupal_inquicker\Formatter;

use Drupal\drupal_inquicker\Source\SourceCollection;
use Drupal\drupal_inquicker\traits\Singleton;
use Traversable;

/**
 * Formats a SourceCollection as expected by hook_requirements().
 */
class RequirementsFormatter extends Formatter {

  use Singleton;

  /**
   * {@inheritdoc}
   */
  public function catchError(\Throwable $t) {
    $this->watchdogThrowable($t);
    return [
      $this->generateUuid() => [
        'title' => $this->t('Drupal Inquicker: Cannot check requirements'),
        'description' => $t->getMessage(),
        'value' => 'A throwable occurred.',
        'severity' => $this->requirementError(),
      ],
    ];
  }

  /**
   * Get the description to display in the status report.
   *
   * @param Traversable $all
   *   The full list of Sources.
   * @param Traversable $live
   *   The live Sources only.
   * @param Traversable $valid
   *   The valid Sources only.
   * @param Traversable $invalid
   *   The invalid Sources only.
   *
   * @return string
   *   A descriptive string based on our sources.
   */
  public function description(Traversable $all, Traversable $live, Traversable $valid, Traversable $invalid) : string {
    if (count($invalid)) {
      return $this->t('There is at least one invalid source, @i; see ./README.md on how to fix this in your settings.php file.', [
        '@i' => $this->keyListFormatter()->format($invalid),
      ]);
    }
    if (!count($all)) {
      return 'No sources are defined; see ./README.md on how to modify your settings.php file.';
    }
    if (count($live)) {
      return $this->t('At least one live source is defined (@l); see ./README.md on how to modify your settings.php file.', [
        '@l' => $this->keyListFormatter()->format($live),
      ]);
    }
    return $this->t('All sources are non-live sources (@l), meaning they will not cause requests from the live API. This may not be an issue if you know what you are doing; see ./README.md on how to modify your settings.php file.', [
      '@l' => $this->keyListFormatter()->format($all),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function formatValidatedSource($data) {
    $all = $data;
    $live = $data->liveOnly();
    $valid = $data->validOnly();
    $invalid = $data->invalidOnly();
    return [
      $this->generateUuid() => [
        'title' => $this->t('Drupal Inquicker sources'),
        'description' => $this->description($all, $live, $valid, $invalid),
        'value' => $this->t('@a source(s), @l live, @v valid and @i invalid', [
          '@a' => count($all),
          '@l' => count($live),
          '@v' => count($valid),
          '@i' => count($invalid),
        ]),
        'severity' => $this->severity($all, $live, $valid, $invalid),
      ],
    ];
  }

  /**
   * Get the severity to display in the status report.
   *
   * @param Traversable $all
   *   The full list of Sources.
   * @param Traversable $live
   *   The live Sources only.
   * @param Traversable $valid
   *   The valid Sources only.
   * @param Traversable $invalid
   *   The invalid Sources only.
   *
   * @return int
   *   A severity.
   */
  public function severity(Traversable $all, Traversable $live, Traversable $valid, Traversable $invalid) : int {
    if (count($invalid)) {
      return $this->requirementError();
    }
    if (!count($all)) {
      return $this->requirementError();
    }
    if (count($live)) {
      return $this->requirementOK();
    }
    return $this->requirementWarning();
  }

  /**
   * {@inheritdoc}
   */
  public function validateSource($data) {
    $this->validateClass($data, SourceCollection::class);
    $data->validateMembers();
  }

}
