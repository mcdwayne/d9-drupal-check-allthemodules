<?php

namespace Drupal\micro_site\Plugin\Validation\Constraint;

use Drupal\Component\Utility\Unicode;
use Drupal\micro_site\Entity\SiteInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that the site url is unique and valid for the given entity type.
 */
class SiteUrlFieldConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    /** @var \Drupal\micro_site\entity\SiteInterface $entity */
    $site_url = $entity->site_url->value;
    $type_url = $entity->type_url->value;
    $base_url = $entity::getHostBaseUrl();
    $public_url = $entity::getHostPublicUrl();
    $error = FALSE;
    switch ($type_url) {
      case 'subdomain':
        $complete_url = $site_url . '.' .$base_url;
        // check pattern.
        $pattern = '/^[a-z0-9\-]*$/';
        if (!preg_match($pattern, $site_url)) {
          $error = TRUE;
        }
        // Check for lower case.
        if ($site_url != Unicode::strtolower($site_url)) {
          $error = TRUE;
        }
        if ($error) {
          $this->context->addViolation($constraint->message_sub_domain, [
            '%value' => $site_url,
          ]);
        }

        // Check reserved keywords.
        $keywords = ['host', 'localhost', 'www', 'dev', 'stage', 'preprod'];
        if (in_array($site_url, $keywords)) {
          $this->context->addViolation($constraint->message_reserved, [
            '%value' => $site_url,
          ]);
        }
        break;
      case 'domain':
        $complete_url = $site_url;
        if ($site_url == $base_url || $site_url == $public_url) {
          $this->context->addViolation($constraint->message_reserved, [
            '%value' => $site_url,
          ]);
        }
        $keywords = ['host', 'localhost', 'www', 'dev', 'stage', 'preprod'];
        foreach ($keywords as $keyword) {
          if ($site_url == $keyword . '.' . $base_url) {
            $this->context->addViolation($constraint->message_reserved, [
              '%value' => $site_url,
            ]);
          }
        }
        // check pattern.
        $pattern = '/^(([a-z0-9\-]*)\.)?(([a-z0-9\-]*)\.)?([a-z0-9\-]*)\.([a-z\.]{1,7})$/';
        if (!preg_match($pattern, $site_url, $matches)) {
          $error = TRUE;
        }
        if ($error) {
          $this->context->addViolation($constraint->message_domain, [
            '%value' => $site_url,
          ]);
        }
        break;
      default:
        $complete_url = $site_url;
        break;
    }

    // Check existing site.
    $existing = \Drupal::entityTypeManager()->getStorage('site')->loadByProperties(['site_url' => $complete_url]);
    if (!$entity->isNew()) {
      if (count($existing) > 1) {
        $this->context->addViolation($constraint->message, [
          '%value' => $site_url,
        ]);
      }
      // The current site may have its site url changed. Check if the existing
      // site is really the same current site being save.
      else {
        $existing = reset($existing);
        if ($existing instanceof SiteInterface) {
          if ($existing->id() != $entity->id()) {
            $this->context->addViolation($constraint->message, [
              '%value' => $site_url,
            ]);
          }
        }
      }
    }
    else {
      $existing = reset($existing);
      if ($existing) {
        $this->context->addViolation($constraint->message, [
          '%value' => $site_url,
        ]);
      }
    }

  }

}
