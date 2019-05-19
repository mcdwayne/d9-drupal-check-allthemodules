<?php

/**
 * @file
 * Contains \Drupal\smartling\Form\SendMultipleConfirmForm.
 */

namespace Drupal\smartling\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a confirmation form for sending multiple content entities.
 */
class UploadTaxonomyTermApproveForm extends SendMultipleConfirmForm {

  /**
   * Temp storage name we are saving entity_ids to.
   *
   * @var string
   */
  protected $tempStorageName = 'smartling_taxonomy_term_operations_send';

}
