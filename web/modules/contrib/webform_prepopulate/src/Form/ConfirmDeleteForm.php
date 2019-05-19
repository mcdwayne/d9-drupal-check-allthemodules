<?php

namespace Drupal\webform_prepopulate\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\webform_prepopulate\WebformPrepopulateStorage;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Confirmation form for deletion of prepopulate data for a Webform.
 */
class ConfirmDeleteForm extends ConfirmFormBase {

  /**
   * Drupal\webform_prepopulate\WebformPrepopulateStorage definition.
   *
   * @var \Drupal\webform_prepopulate\WebformPrepopulateStorage
   */
  protected $webformPrepopulateStorage;

  /**
   * Id of the Webform.
   *
   * @var string
   */
  protected $webform;

  /**
   * Constructs a new WebformPrepopulateController object.
   *
   * @param \Drupal\webform_prepopulate\WebformPrepopulateStorage $webform_prepopulate_storage
   *   The Webform prepopulate storage.
   */
  public function __construct(WebformPrepopulateStorage $webform_prepopulate_storage) {
    $this->webformPrepopulateStorage = $webform_prepopulate_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform_prepopulate.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "webform_prepopulate_confirm_delete_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $webform = NULL) {
    $this->webform = $webform;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->webformPrepopulateStorage->deleteWebformData($this->webform)) {
      \Drupal::messenger()
        ->addMessage($this->t('Prepopulate data for the @webform Webform have been deleted.', [
          '@webform' => $this->webform,
        ]));
      $form_state->setRedirectUrl(Url::fromRoute('webform_prepopulate.prepopulate_list_form', ['webform' => $this->webform]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('webform_prepopulate.prepopulate_list_form', ['webform' => $this->webform]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to delete the <em>@webform</em> Webform prepopulate data?', ['@webform' => $this->webform]);
  }

}
