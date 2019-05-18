<?php

namespace Drupal\domain_language\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\domain\DomainLoaderInterface;
use Drupal\domain_language\Form\DomainLanguageForm;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class DomainLanguageController.
 *
 * @package Drupal\domain_language\Controller
 */
class DomainLanguageController extends ControllerBase {
  /**
   * Edit domain language restrictions.
   *
   * @return array
   *   Edit form page.
   */
  public function edit($domain) {
    /** @var DomainLoaderInterface $domainLoader */
    $domainLoader = \Drupal::service('domain.loader');
    if (!$domain = $domainLoader->load($domain)) {
      throw new NotFoundHttpException();
    }
    $build = [
      'edit_form' => $this->formBuilder()->getForm(DomainLanguageForm::class, $domain),
    ];

    return $build;
  }
}
