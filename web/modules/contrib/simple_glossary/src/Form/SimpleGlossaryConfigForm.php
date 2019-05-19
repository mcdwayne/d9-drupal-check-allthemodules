<?php

namespace Drupal\simple_glossary\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class SimpleGlossaryConfigForm.
 *
 * @package Drupal\simple_glossary\Form
 */
class SimpleGlossaryConfigForm extends FormBase {

  /**
   * A form state interface instance.
   *
   * @var Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * A Request stack instance.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * A entity type manager interface instance.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SimpleGlossaryFrontendController object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   A form state variable.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   A Request stack variable.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   A entity type manager interface variable.
   */
  public function __construct(StateInterface $state, RequestStack $request, EntityTypeManagerInterface $entity_type_manager) {
    $this->state = $state;
    $this->request = $request;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('request_stack'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Set Form Id.
   */
  public function getFormId() {
    return 'glossary_configuration_page';
  }

  /**
   * Building Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['glossary_page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page Title'),
      '#maxlength' => 500,
      '#required' => TRUE,
      '#default_value' => $this->state->get('glossary_page_title'),
    ];
    $form['glossary_page_subheading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subheading'),
      '#maxlength' => 1024,
      '#required' => TRUE,
      '#default_value' => $this->state->get('glossary_page_subheading'),
    ];
    $form['glossary_bg_image'] = [
      '#required' => TRUE,
      '#type' => 'managed_file',
      '#name' => 'glossary_bg_image',
      '#title' => $this->t('Background Image'),
      '#size' => 20,
      '#default_value' => json_decode($this->state->get('glossary_bg_image')),
      '#description' => $this->t('Allowed extensions : jpg, jpeg, png format only'),
      '#upload_location' => 'public://glossary_config/',
      '#upload_validators' => ['file_validate_extensions' => ['jpg jpeg png']],
    ];
    $form['glossary_bottom_text'] = [
      '#title' => $this->t('Bottom Text'),
      '#required' => TRUE,
      '#type' => 'textarea',
      '#default_value' => $this->state->get('glossary_bottom_text'),
      '#description' => $this->t('Bottom text of glossary page'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
    ];
    return $form;
  }

  /**
   * Validating Form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Submiting Form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $postData = [];
    $postData['glossary_page_title'] = $form_state->getValue('glossary_page_title');
    $postData['glossary_page_subheading'] = $form_state->getValue('glossary_page_subheading');
    $tempFile = $form_state->getValue('glossary_bg_image');
    SimpleGlossaryConfigForm::updateFileStatus($tempFile[0]);
    $postData['glossary_bg_image'] = json_encode($form_state->getValue('glossary_bg_image'));
    $postData['glossary_bottom_text'] = $form_state->getValue('glossary_bottom_text');
    foreach ($postData as $key => $val) {
      $this->state->set($key, $val);
    }
    drupal_set_message($this->t('Configuration has been saved successfully.'));
  }

  /**
   * Helper Method.
   */
  public function updateFileStatus($fid) {
    try {
      $res = db_update('file_managed')->fields(['status' => 1])->condition('fid', $fid, '=')->execute();
      return $res;
    }
    catch (Exception $e) {
      return $e->getMessage();
    }
  }

}
