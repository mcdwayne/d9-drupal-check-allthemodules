<?php

namespace Drupal\exam_spider\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\exam_spider\ExamSpiderDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ExamSpiderExamDelete.
 */
class ExamSpiderExamDelete extends ConfirmFormBase {

  /**
   * The exam ID.
   *
   * @var int
   */
  public $examid;

  /**
   * The ExamSpider service.
   *
   * @var \Drupal\user\ExamSpiderData
   */
  protected $ExamSpiderData;

  /**
   * Constructs a ExamSpider object.
   *
   * @param \Drupal\exam_spider\ExamSpiderDataInterface $examspider_data
   *   The ExamSpider multiple services.
   */
  public function __construct(ExamSpiderDataInterface $examspider_data) {
    $this->ExamSpiderData = $examspider_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('exam_spider.data')
    );
  }

  /**
   * Delete Exam form.
   */
  public function getFormId() {
    return 'delete_exam_form';
  }

  /**
   * Delete Exam confirm text.
   */
  public function getQuestion() {
    $exam_id = $this->id;
    $exam_data = $this->ExamSpiderData->examSpiderGetExam($exam_id);
    return t('Do you want to delete @exam_name @examSpiderExamTitle ?', ['@exam_name' => $exam_data['exam_name'], '@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE]);
  }

  /**
   * Delete Exam cancel url.
   */
  public function getCancelUrl() {
    return new Url('exam_spider.exam_spider_dashboard');
  }

  /**
   * Delete Exam Description text.
   */
  public function getDescription() {
    return $this->t('This action cannot be undone.');
  }

  /**
   * Delete button text.
   */
  public function getConfirmText() {
    return $this->t('Delete it!');
  }

  /**
   * Cancel button text.
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * Delete Exam form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $examid = NULL) {
    $this->id = $examid;
    return parent::buildForm($form, $form_state);
  }

  /**
   * Delete Exam form submit callbacks.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $examspider_service = new ExamSpider();
    $connection = \Drupal::database();
    $exam_id = $this->id;
    $exam_data = $examspider_service->examSpiderGetExam($exam_id);
    $connection->db_delete('exam_list')
      ->condition('id', $exam_id)
      ->execute();
    $connection->db_delete('exam_questions')
      ->condition('examid', $exam_id)
      ->execute();
    $connection->db_delete('exam_results')
      ->condition('examid', $exam_id)
      ->execute();
    $connection->drupal_set_message(t('@exam_name has been deleted successfully.', ['@exam_name' => $exam_data['exam_name']]));
    $form_state->setRedirect('exam_spider.exam_spider_dashboard');
  }

}
