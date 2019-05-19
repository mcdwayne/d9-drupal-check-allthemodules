<?php

namespace Drupal\simple_glossary\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class SimpleGlossaryCrudForm.
 *
 * @package Drupal\simple_glossary\Form
 */
class SimpleGlossaryCrudForm extends FormBase {

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
    $formIdVal = '';
    $current_path = $this->request->getCurrentRequest()->getRequestUri();
    if (strpos($current_path, 'simple_glossary/add') != FALSE) {
      $formIdVal = 'add';
    }
    elseif (strpos($current_path, 'simple_glossary/edit') != FALSE) {
      $formIdVal = 'edit';
    }
    elseif (strpos($current_path, 'simple_glossary/delete') != FALSE) {
      $formIdVal = 'delete';
    }
    return 'glossary_' . $formIdVal . '_glossary_term';
  }

  /**
   * Building Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = '') {
    $getFormAry = [];
    if (empty($id)) {
      $getFormAry = SimpleGlossaryCrudForm::helperBuildAddGlossaryForm();
    }
    else {
      $termValidOrNot = SimpleGlossaryCrudForm::helperCheckTermIdIsValidOrNot($id);
      if (!empty($termValidOrNot)) {
        $current_path = $this->request->getCurrentRequest()->getRequestUri();
        if (strpos($current_path, 'simple_glossary/edit') != FALSE) {
          $getFormAry = SimpleGlossaryCrudForm::helperBuildEditGlossaryForm($id, $termValidOrNot);
        }
        elseif (strpos($current_path, 'simple_glossary/delete') != FALSE) {
          $getFormAry = SimpleGlossaryCrudForm::helperBuildDeleteGlossaryForm($id);
        }
      }
      else {
        drupal_set_message($this->t('Invalid request, This term does not exist.'), 'error');
        SimpleGlossaryCrudForm::helperDrupalRedirect('admin/config/system/simple_glossary');
      }
    }
    foreach ($getFormAry as $key => $val) {
      $form[$key] = $val;
    }
    return $form;
  }

  /**
   * Form Validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $postData = [];
    $postData['crud_type'] = $form_state->getValue('crud_type');
    if ($postData['crud_type'] == 'add') {
      $postData['term'] = $form_state->getValue('term');
      $termValue = $postData['term'];
      $termExist = SimpleGlossaryCrudForm::helperCheckTermNameExist($termValue);
      if (!empty($termExist)) {
        $form_state->setErrorByName('term', $this->t('This Term already exists.'));
      }
    }
    elseif ($postData['crud_type'] == 'edit') {
      $postData['term'] = $form_state->getValue('term');
      $postData['gid'] = $form_state->getValue('gid');
      $getAnyTermHavingSameName = SimpleGlossaryCrudForm::helperCheckTermNameExistForUpdate($postData['gid'], $postData['term']);
      if (!empty($getAnyTermHavingSameName)) {
        $form_state->setErrorByName('term', $this->t('This Term already exists.'));
      }
    }
    elseif ($postData['crud_type'] == 'delete') {
      /* Piece of Code for Validation on "DELETE" operation*/
    }
  }

  /**
   * Form submission.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $postData = [];
    $postData['crud_type'] = $form_state->getValue('crud_type');
    try {
      if ($postData['crud_type'] == 'add') {
        $postData['term'] = $form_state->getValue('term');
        $postData['definition'] = $form_state->getValue('definition');
        db_insert('simple_glossary_content')->fields(['term' => $postData['term'], 'description' => strip_tags($postData['definition'])])->execute();
        drupal_set_message($this->t('Configuration! Term has been saved successfully.'));
      }
      elseif ($postData['crud_type'] == 'edit') {
        $postData['gid'] = $form_state->getValue('gid');
        $postData['term'] = $form_state->getValue('term');
        $postData['definition'] = $form_state->getValue('definition');
        db_update('simple_glossary_content')->fields(['term' => $postData['term'], 'description' => strip_tags($postData['definition'])])->condition('gid', $postData['gid'])->execute();
        drupal_set_message($this->t('Configuration! Term has been updated successfully.'));
      }
      elseif ($postData['crud_type'] == 'delete') {
        $postData['gid'] = $form_state->getValue('gid');
        db_delete('simple_glossary_content')->condition('gid', $postData['gid'])->execute();
        drupal_set_message($this->t('Configuration! Term has been deleted successfully.'));
      }
      SimpleGlossaryCrudForm::helperDrupalRedirect('admin/config/system/simple_glossary');
    }
    catch (Exception $e) {
      $form_state->setErrorByName('term', $this->t('Invalid Response.'));
    }
  }

  /**
   * HELPER METHOD - Check Term Name Exist or Not.
   */
  public function helperCheckTermNameExist($term_name) {
    $data = db_select('simple_glossary_content', 't')->fields('t')->condition('term', $term_name)->execute()->fetchAssoc();
    return $data;
  }

  /**
   * HELPER METHOD - Build Add Glossary Form.
   */
  public function helperBuildAddGlossaryForm() {
    $form = [];
    $form['term'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Term'),
      '#maxlength' => 150,
      '#required' => TRUE,
    ];
    $form['crud_type'] = [
      '#type' => 'hidden',
      '#default_value' => 'add',
    ];
    $form['definition'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Definition'),
      '#required' => TRUE,
      '#cols' => 20,
      '#maxlength' => 1000,
      '#rows' => 5,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Add',
    ];
    return $form;
  }

  /**
   * HELPER METHOD - BUILD EDIT GLOSSARY FORM.
   */
  public function helperBuildEditGlossaryForm($id, $existingData) {
    $form = [];
    $form['gid'] = [
      '#type' => 'hidden',
      '#default_value' => $id,
    ];
    $form['crud_type'] = [
      '#type' => 'hidden',
      '#default_value' => 'edit',
    ];
    $form['term'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Term'),
      '#required' => TRUE,
      '#maxlength' => 150,
      '#default_value' => $existingData['term'],
    ];
    $form['definition'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Definition'),
      '#required' => TRUE,
      '#cols' => 20,
      '#maxlength' => 1000,
      '#rows' => 5,
      '#default_value' => $existingData['description'],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Update',
    ];
    return $form;
  }

  /**
   * HELPER METHOD - CHECK TERM NAME EXIST OR NOT.
   */
  public function helperCheckTermNameExistForUpdate($id, $term_name) {
    $data = db_select('simple_glossary_content', 't')->fields('t')->condition('term', $term_name)->condition('gid', $id, '!=')->execute()->fetchAssoc();
    return $data;
  }

  /**
   * HELPER METHOD - BUILD DELETE GLOSSARY FORM.
   */
  public function helperBuildDeleteGlossaryForm($id) {
    global $base_url;
    $form = [];
    $form['gid'] = [
      '#type' => 'hidden',
      '#default_value' => $id,
    ];
    $form['crud_type'] = [
      '#type' => 'hidden',
      '#default_value' => 'delete',
    ];
    $form['markuptext'] = [
      '#markup' => 'Are you sure want to delete this term ? <br />',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Delete',
      '#suffix' => '<a class="button" href="' . $base_url . '/admin/config/system/simple_glossary">Cancel</a>',
    ];
    return $form;
  }

  /**
   * HELPER METHOD - Check Term Is Valid Or Not.
   */
  public function helperCheckTermIdIsValidOrNot($id) {
    $data = db_select('simple_glossary_content', 't')->fields('t')->condition('gid', $id)->execute()->fetchAssoc();
    return $data;
  }

  /**
   * HELPER METHOD - redirect response.
   */
  public function helperDrupalRedirect($path) {
    $response = new RedirectResponse($path);
    $response->send();
  }

}
