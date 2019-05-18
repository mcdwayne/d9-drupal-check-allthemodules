<?php

namespace Drupal\exam_spider\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\exam_spider\ExamSpiderDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form builder for the add/edit Question form.
 *
 * @package Drupal\exam_spider\Form
 */
class ExamSpiderQuestionForm extends FormBase {

  /**
   * The ExamSpider service.
   *
   * @var \Drupal\user\ExamSpiderData
   */
  protected $ExamSpiderData;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Constructs a ExamSpider object.
   *
   * @param \Drupal\exam_spider\ExamSpiderDataInterface $examspider_data
   *   The ExamSpider multiple services.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   */
  public function __construct(ExamSpiderDataInterface $examspider_data, Renderer $renderer, CurrentPathStack $current_path) {
    $this->ExamSpiderData = $examspider_data;
    $this->render = $renderer;
    $this->currentPath = $current_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('exam_spider.data'),
      $container->get('renderer'),
      $container->get('path.current')
    );
  }

  /**
   * Add/Update get Question form.
   */
  public function getFormId() {
    return 'add_edit_question_form';
  }

  /**
   * Add/edit Question form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = $exam_options = $values = $answer = [];
    $form['#attached']['library'][] = 'exam_spider/exam_spider';
    $current_path = $this->currentPath->getPath();
    $path_args = explode('/', $current_path);
    $default_sel = $path_args[5];
    if ($path_args[6] == 'edit' && is_numeric($path_args[5])) {
      $question_id = $path_args[5];
      $values = $this->ExamSpiderData->examSpiderGetQuestion($question_id);
      $answer = array_flip(explode('-', $values['answer']));
      $form['question_id'] = ['#type' => 'value', '#value' => $question_id];
      $default_sel = $values['examid'];
    }
    $all_exam = $this->ExamSpiderData->examSpiderGetExam();
    foreach ($all_exam as $option) {
      $exam_options[$option->id] = $option->exam_name;
    }
    $form['#attributes'] = ['class' => ['questions-action']];
    $form['selected_exam'] = [
      '#type' => 'select',
      '#title' => $this->t('Select @examSpiderExamTitle', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE]),
      '#options' => $exam_options,
      '#default_value' => isset($default_sel) ? $default_sel : NULL,
      '#required' => TRUE,
    ];
    $form['question_name'] = [
      '#title' => $this->t('Question Name'),
      '#type' => 'textfield',
      '#maxlength' => '170',
      '#required' => TRUE,
      '#default_value' => isset($values['question']) ? $values['question'] : NULL,
    ];
    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Option settings'),
      '#open' => TRUE,
    ];
    $form['options']['multi_answer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Multiple Answers'),
      '#default_value' => isset($values['multiple']) ? $values['multiple'] : NULL,
    ];
    for ($i = 1; $i <= 4; $i++) {

      $form['options']['opt' . $i] = [
        '#title' => $this->t('Option @i', ['@i' => $i]),
        '#type' => 'textarea',
        '#maxlength' => '550',
        '#cols' => 20,
        '#rows' => 1,
        '#required' => TRUE,
        '#default_value' => isset($values['opt' . $i]) ? $values['opt' . $i] : NULL,
        '#prefix' => '<div class="option_set">',
      ];
      $form['options']['answer' . $i] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Correct Option'),
        '#attributes' => ['class' => ['answer']],
        '#default_value' => isset($answer['opt' . $i]) ? 1 : NULL,
        '#suffix' => '</div>',
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $exam_spider_get_questions = $this->examSpiderGetQuestionsList($default_sel);
    $form['#suffix'] = $this->render->render($exam_spider_get_questions);
    return $form;
  }

  /**
   * Add/Update exam page validate callbacks.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $answer1 = $form_state->getValue('answer1');
    $answer2 = $form_state->getValue('answer2');
    $answer3 = $form_state->getValue('answer3');
    $answer4 = $form_state->getValue('answer4');
    if ($answer1 == 0 && $answer2 == 0 && $answer3 == 0 && $answer4 == 0) {
      return $form_state->setErrorByName('answer', $this->t('Please choose at least one answer.'));
    }
  }

  /**
   * Exam Add/Update form submit callbacks.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $answer = '';
    for ($i = 1; $i <= 4; $i++) {
      if ($form_state->getValue('answer' . $i) == 1) {
        $answer .= 'opt' . $i . '-';
      }
    }
    $answer = rtrim($answer, "-");
    $examid = $form_state->getValue('selected_exam');
    $values['examid'] = $examid;
    $values['question'] = $form_state->getValue('question_name');
    $values['opt1'] = $form_state->getValue('opt1');
    $values['opt2'] = $form_state->getValue('opt2');
    $values['opt3'] = $form_state->getValue('opt3');
    $values['opt4'] = $form_state->getValue('opt4');
    $values['answer'] = $answer;
    $values['multiple'] = $form_state->getValue('multi_answer');
    $values['created'] = REQUEST_TIME;
    $values['changed'] = REQUEST_TIME;

    $question_id = $form_state->getValue('question_id');
    if ($question_id) {
      db_update('exam_questions')
        ->fields($values)
        ->condition('id', $question_id)
        ->execute();
      drupal_set_message($this->t('You have successfully updated question.'));
    }
    else {
      db_insert('exam_questions')
        ->fields($values)
        ->execute();
      drupal_set_message($this->t('You have successfully created question for this @examSpiderExamTitle', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE]));
    }
    $form_state->setRedirect('exam_spider.exam_spider_add_question', ['examid' => $examid]);
  }

  /**
   * Get Question list using exam id function.
   */
  public function examSpiderGetQuestionsList($exam_id) {
    $output = NULL;
    if (is_numeric($exam_id)) {
      $header = [
        [
          'data' => 'Question',
          'field' => 'eq.question',
        ],
        [
          'data' => 'Status',
          'field' => 'eq.status',
        ],
        [
          'data' => 'Operations',
        ],
      ];
      $query = db_select("exam_questions", "eq")
        ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
        ->extend('\Drupal\Core\Database\Query\TableSortExtender');
      $query->fields('eq', ['id', 'question', 'status']);
      $query->condition('examid', $exam_id);
      $results = $query
        ->limit(10)
        ->orderByHeader($header)
        ->execute()
        ->fetchAll();
      $rows = [];
      foreach ($results as $row) {
        $editquestion_url = Url::fromRoute('exam_spider.exam_spider_edit_question', ['questionid' => $row->id]);
        $editquestion_link = Link::fromTextAndUrl($this->t('Edit'), $editquestion_url)->toString();
        $deletequestion_url = Url::fromRoute('exam_spider.exam_spider_delete_question', ['questionid' => $row->id]);
        $deletequestion_link = Link::fromTextAndUrl($this->t('Delete'), $deletequestion_url)->toString();
        $operations = $this->t('@editquestion_link | @deletequestion_link', ['@editquestion_link' => $editquestion_link, '@deletequestion_link' => $deletequestion_link]);
        if ($row->status == 0) {
          $status = 'Closed';
        }
        else {
          $status = 'Open';
        }
        $rows[] = [
          'data' => [
            $row->question,
            $status,
            $operations,
          ],
        ];
      }
      $output['questions_list'] = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No question created yet for this @examSpiderExamTitle', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE]),
        '#attributes' => ['class' => 'questions-list-table'],
      ];
      $output['questions_pager'] = ['#type' => 'pager'];
    }
    return $output;
  }

}
