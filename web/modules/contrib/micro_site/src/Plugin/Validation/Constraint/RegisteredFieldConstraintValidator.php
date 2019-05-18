<?php

namespace Drupal\micro_site\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use GuzzleHttp\Exception\RequestException;

/**
 * Validates that the DNS for a site url is well configured vefore publishing a site.
 */
class RegisteredFieldConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (\Drupal::config('micro_site.settings')->get('skip_validation_dns')) {
      return;
    }
    if (!$item = $items->first()) {
      return;
    }
    /** @var \Drupal\micro_site\Entity\SiteInterface $entity */
    $entity = $items->getEntity();
    $registered = $item->value;
    if ($registered) {
      $url = $entity->getSitePath() . '/' . drupal_get_path('module', 'micro_site') . '/tests/200.png';
      try {
        $request = \Drupal::httpClient()->get($url);
        $status_code = $request->getStatusCode();
      }
      // We cannot know which Guzzle Exception class will be returned; be generic.
      catch (RequestException $e) {
        watchdog_exception('micro_site', $e);
        // File a general server failure.
        $this->context->addViolation($constraint->message, [
          '%value' => $entity->getSiteUrl(),
          '%error' => $e->getMessage(),
        ]);
      }
    }

  }

}
