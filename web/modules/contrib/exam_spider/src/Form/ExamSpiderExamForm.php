<?php

namespace Drupal\exam_spider\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\exam_spider\ExamSpiderDataInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form builder for the add exam form.
 *
 * @package Drupal\exam_spider\Form
 */
class ExamSpiderExamForm extends FormBase {

  /**
   * The ExamSpider service.
   *
   * @var \Drupal\user\ExamSpiderData
   */
  protected $ExamSpiderData;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a ExamSpider object.
   *
   * @param \Drupal\exam_spider\ExamSpiderDataInterface $examspider_data
   *   The ExamSpider multiple services.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(ExamSpiderDataInterface $examspider_data, CurrentPathStack $current_path, AccountInterface $current_user) {
    $this->ExamSpiderData = $examspider_data;
    $this->currentPath = $current_path;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('exam_spider.data'),
      $container->get('path.current'),
      $container->get('current_user')
    );
  }

  /**
   * Add/Update get exam form.
   */
  public function getFormId() {
    return 'add_edit_exam_form';
  }

  /**
   * Add/Update build exam form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $current_path = $this->currentPath->getPath();
    $path_args = explode('/', $current_path);
    if ($path_args[5] == 'edit' && is_numeric($path_args[4])) {
      $exam_id = $path_args[4];
      $values = $this->ExamSpiderData->examSpiderGetExam($exam_id);
      $form['exam_id'] = ['#type' => 'value', '#value' => $values['id']];
    }
    $form['exam_name'] = [
      '#title' => $this->t('@examSpiderExamTitle Name', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE]),
      '#type' => 'textfield',
      '#maxlength' => '170',
      '#required' => TRUE,
      '#default_value' => isset($values['exam_name']) ? $values['exam_name'] : NULL,
    ];
    $form['exam_description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#maxlength' => '550',
      '#cols' => 20,
      '#rows' => 1,
      '#default_value' => isset($values['exam_description']) ? $values['exam_description'] : NULL,
    ];
    $form['examsettings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('@examSpiderExamTitle settings', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE]),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,

    ];
    $form['examsettings']['exam_duration'] = [
      '#title' => $this->t('@examSpiderExamTitle Duration', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE]),
      '#description' => $this->t('@examSpiderExamTitle time duration in minutes.', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE]),
      '#type' => 'number',
      '#maxlength' => '10',
      '#size' => 10,
      '#required' => TRUE,
      '#default_value' => isset($values['exam_duration']) ? $values['exam_duration'] : NULL,
      '#min' => 0,
    ];
    $form['examsettings']['total_marks'] = [
      '#title' => $this->t('Total Marks'),
      '#type' => 'number',
      '#maxlength' => '10',
      '#size' => 10,
      '#required' => TRUE,
      '#default_value' => isset($values['total_marks']) ? $values['total_marks'] : NULL,
      '#min' => 1,
    ];
    $form['examsettings']['random_quest'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display Random Question'),
      '#default_value' => isset($values['random_quest']) ? $values['random_quest'] : NULL,
    ];
    $form['examsettings']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Published'),
      '#default_value' => isset($values['status']) ? $values['status'] : NULL,
    ];
    $form['examsettings']['negative_mark'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Negative Marking'),
      '#default_value' => isset($values['negative_mark']) ? $values['negative_mark'] : NULL,
    ];
    $form['examsettings']['negative_mark_per'] = [
      '#title' => $this->t('Negative mark %'),
      '#description' => $this->t('@examSpiderExamTitle negative marking in %.', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE]),
      '#type' => 'number',
      '#maxlength' => '10',
      '#size' => 10,
      '#required' => TRUE,
      '#default_value' => isset($values['negative_mark_per']) ? $values['negative_mark_per'] : 0,
      '#min' => 0,
    ];
    $form['examsettings']['re_attempt'] = [
      '#title' => $this->t('Re-attempt @examSpiderExamTitle time', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE]),
      '#description' => $this->t('Re-attempt @examSpiderExamTitle time in days.', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE]),
      '#type' => 'number',
      '#maxlength' => '10',
      '#size' => 10,
      '#required' => TRUE,
      '#default_value' => isset($values['re_attempt']) ? $values['re_attempt'] : 0,
      '#min' => 0,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * Exam Add/Update form submit callbacks.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $exam_id = $form_state->getValue('exam_id');
    $values = [];
    $values['exam_name'] = $form_state->getValue('exam_name');
    $values['exam_description'] = $form_state->getValue('exam_description');
    $values['uid'] = $this->currentUser->id();
    $values['exam_duration'] = $form_state->getValue('exam_duration');
    $values['total_marks'] = $form_state->getValue('total_marks');
    $values['random_quest'] = $form_state->getValue('random_quest');
    $values['negative_mark'] = $form_state->getValue('negative_mark');
    $values['negative_mark_per'] = $form_state->getValue('negative_mark_per');
    $values['re_attempt'] = $form_state->getValue('re_attempt');
    $values['status'] = $form_state->getValue('status');
    $values['changed'] = REQUEST_TIME;

    if ($exam_id) {
      db_update('exam_list')
        ->fields($values)
        ->condition('id', $exam_id)
        ->execute();
      drupal_set_message($this->t('You have successfully updated @examName @examSpiderExamTitle.', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE, '@examName' => $form_state->getValue('exam_name')]));
    }
    else {
      db_insert('exam_list')
        ->fields($values)
        ->execute();
      drupal_set_message($this->t('You have successfully created @examSpiderExamTitle, Please add Questions for @examName @examSpiderExamTitle.', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE, '@examName' => $form_state->getValue('exam_name')]));
    }
    $form_state->setRedirect('exam_spider.exam_spider_dashboard');
  }

}
