<?php

namespace Drupal\webform_to_leads\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformRequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


class WebToLeadForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'web_to_lead';
  }

  /**
   * The webform entity.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The webform source entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $sourceEntity;

  /**
   * The webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $submissionStorage;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * Constructs a object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WebformRequestInterface $request_handler) {
    $this->submissionStorage = $entity_type_manager->getStorage('webform_submission');
    $this->requestHandler = $request_handler;
    list($this->webform, $this->sourceEntity) = $this->requestHandler->getWebformEntities();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('webform.request')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('webform_to_leads.forms');
    $settings = $config->get();
    $form['web2lead'] = [
      '#type' => "fieldset",
      '#title' => 'Web 2 Lead Settings'
    ];
    $form['web2lead']['active'] = array(
      '#type' => 'radios',
      '#title' => t('Submit to SalesForce'),
      '#default_value' => (isset($settings[$this->webform->get("id")]) ? $settings[$this->webform->get("id")]['active'] : "0"),
      '#options' => [
        0 => 'NO',
        1 => "Yes"
      ],
      '#description' => t('If yes, the form will be sent via CURL to SalesForce.'),
    );
    $form['web2lead']['lead_source'] = array(
      "#type" => "textfield",
      "#title" => "Lead Source for this Webform",
      '#default_value' => (isset($settings[$this->webform->get("id")]) ? $settings[$this->webform->get("id")]['lead_source'] : ""),
    );
    $form['web2lead']['submit'] = array('#type' => 'submit', '#value' => t('Save Settings'));


    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasValue("active")) {
      if ($form_state->getValue('active') == "0") {
        return TRUE;
      }
    }
    if ($form_state->hasValue('lead_source')) {
      if (!empty($form_state->getValue('lead_source'))) {
        return TRUE;
      }
    }
    $form_state->setErrorByName("lead_source", "Lead Source can't be empty.");
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('webform_to_leads.forms');
    $settings = [
      'lead_source' => $form_state->getValue('lead_source'),
      'active' => $form_state->getValue('active'),
    ];
    $config->set($this->webform->get("id"), $settings)->save();

  }

}
