<?php

namespace Drupal\owms\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\owms\Entity\OwmsData;

/**
 * Class OwmsDataForm.
 */
class OwmsDataForm extends EntityForm {

  /**
   * @var \Drupal\owms\OwmsManager
   */
  protected $OwmsManager;

  /**
   * OwmsDataForm constructor.
   */
  public function __construct() {
    $this->OwmsManager = \Drupal::getContainer()->get('owms.manager');
  }

  /**
   * Validates the the OWMS data object before saving.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function validate(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\owms\Entity\OwmsDataInterface $owms_data */
    $owms_data = $form_state->getFormObject()->getEntity();
    if ($owms_data->isNew() && OwmsData::load(strtolower($owms_data->getEndpointIdentifier())) !== NULL) {
      $form_state->setErrorByName('endpoint', 'The endpoint already exists');
    }
    $errors = $owms_data->validate();
    if (is_array($errors)) {
      foreach ($errors as $property => $exception) {
        $form_state->setErrorByName($property, t('There were errors saving the OWMS Data object: @errors', [
          '@errors' => $exception->getMessage(),
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\owms\Entity\OwmsDataInterface $owms_data */
    $owms_data = $this->entity;

    $form = parent::form($form, $form_state);

    $form['endpoint'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Endpoint'),
      '#options' => $this->OwmsManager->getEndpoints(),
      '#description' => $this->t("The Endpoint of the XML feed as defined on <em>http://standaarden.overheid.nl</em>."),
      '#default_value' => $owms_data->getEndpointIdentifier(),
      '#disabled' => !$owms_data->isNew(),
    ];

    $form['#validate'][] = [
      get_class($this),
      'validate',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $owms_data = $this->entity;
    $status = $owms_data->save();
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label OWMS data object.', [
          '%label' => $owms_data->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Updated the %label OWMS data object.', [
          '%label' => $owms_data->label(),
        ]));
    }
    $form_state->setRedirectUrl($owms_data->toUrl('collection'));
  }

}
