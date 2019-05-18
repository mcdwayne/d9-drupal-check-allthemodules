<?php

namespace Drupal\carerix_form\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\carerix_form\CarerixServiceInterface;
use Egulias\EmailValidator\EmailValidator;

/**
 * Class CRFormBase.
 *
 * @package Drupal\carerix_form\Form
 */
abstract class CRFormBase extends FormBase {

  /**
   * Carerix service.
   *
   * @var \Drupal\carerix_form\CarerixServiceInterface
   */
  protected $carerix;

  /**
   * @var
   */
  protected $carerixFormFields;

  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * @var
   */
  protected $redirectRouteName;

  /**
   * CarerixFormBase constructor.
   *
   * @param \Drupal\carerix_form\CarerixServiceInterface $carerix
   *   Carerix service.
   * @param $carerixFormFields
   *   The carerix form fields.
   * @param \Egulias\EmailValidator\EmailValidator $emailValidator
   *   Email validator.
   */
  public function __construct(CarerixServiceInterface $carerix, $carerixFormFields, EmailValidator $emailValidator) {
    $this->carerix = $carerix;
    $this->carerixFormFields = $carerixFormFields;
    $this->emailValidator = $emailValidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      // Load the service required to construct this class.
      $container->get('carerix'),
      $container->get('carerix.form_fields.open'),
      $container->get('email.validator')
    );
  }

}
