<?php

namespace Drupal\exam_spider\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\exam_spider\ExamSpiderDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Xss;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Path\CurrentPathStack;

/**
 * Form builder for the exam continue form.
 *
 * @package Drupal\exam_spider\Form
 */
class ExamSpiderExamContinue extends FormBase {

  /**
   * The ExamSpider service.
   *
   * @var \Drupal\user\ExamSpiderData
   */
  protected $ExamSpiderData;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   */
  public function __construct(ExamSpiderDataInterface $examspider_data, AccountInterface $current_user, CurrentPathStack $current_path) {
    $this->ExamSpiderData = $examspider_data;
    $this->currentUser = $current_user;
    $this->currentPath = $current_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('exam_spider.data'),
      $container->get('current_user'),
      $container->get('path.current')
    );
  }

  /**
   * Get Exam continue form.
   */
  public function getFormId() {
    return 'exam_continue_form';
  }

  /**
   * Exam continue form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!empty($_SESSION['exam_result_data'])) {
      $form['exam_result_data'] = [
        '#markup' => $_SESSION['exam_result_data'],
      ];
      $_SESSION['exam_result_data'] = '';
    }
    else {
      $current_path = $this->currentPath->getPath();
      $path_args = explode('/', $current_path);
      $exam_id = $path_args[2];
      $form['exam_id'] = ['#type' => 'value', '#value' => $exam_id];
      $exam_data = $this->ExamSpiderData->examSpiderGetExam($exam_id);
      $re_attempt = $exam_data['re_attempt'];
      $uid = $this->currentUser->id();
      $user_last_result = $this->ExamSpiderData->examSpiderAnyExamLastResult($uid, $exam_id);
      $user_last_attempt_timestamp = $user_last_result['created'];
      $re_attempt_timestamp = strtotime('+' . $re_attempt . ' day', $user_last_attempt_timestamp);
      if ($re_attempt_timestamp > REQUEST_TIME) {
        $re_exam_warning = $this->t('You have already attempt this @examSpiderExamTitle, You will be eligible again after @re_attempt days from previus @examSpiderExamTitle attempt day.', [
          '@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE,
          '@re_attempt' => $re_attempt,
        ]);
        $form['re_exam_warning'] = [
          '#markup' => $re_exam_warning,
        ];
      }
      else {
        $output = NULL;
        $form['#prefix'] = '<div id="exam_timer"></div>';
        $form['#attached']['library'][] = 'exam_spider/exam_spider';
        if ($exam_data['status'] == 0) {
          throw new AccessDeniedHttpException();
        }
        if ($exam_data['random_quest'] == 1) {
          $query = db_select("exam_questions", "eq")
            ->fields("eq")
            ->condition('examid', $exam_id)->orderRandom()->execute();

        }
        else {
          $query = db_select("exam_questions", "eq")
            ->fields("eq")
            ->condition('examid', $exam_id)->execute();
        }
        $results = $query->fetchAll();
        $form['#title'] = Xss::filter($exam_data['exam_name']);
        if (empty($results)) {
          $output .= $this->t('No question created yet for this @examSpiderExamTitle.', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE]);
        }
        else {
          if ($exam_data['exam_duration'] > 0) {
            $form['#attached']['drupalSettings']['forForm'] = 'exam-continue-form';
            $form['#attached']['drupalSettings']['getTimeLimit'] = $this->ExamSpiderData->examSpidergetTimeLimit($exam_data['exam_duration']);
          }
          $form['li_prefix'] = [
            '#markup' => ' <ul class="exam_spider_slider_exam">',
          ];
          $total_slides = count($results);
          foreach ($results as $key => $value) {
            $options[1] = Xss::filter($value->opt1);
            $options[2] = Xss::filter($value->opt2);
            $options[3] = Xss::filter($value->opt3);
            $options[4] = Xss::filter($value->opt4);

            if ($value->multiple == 1) {
              $form['question'][$value->id] = [
                '#type' => 'checkboxes',
                '#options' => $options,
                '#title' => $this->t('@question', ['@question' => Xss::filter($value->question)]),
                '#prefix' => '<li id="examslide_' . $key . '" class="exam_spider_slider">',
                '#suffix' => ' <a class="exam_spider_slide_next button" href="#next">' . $this->t('Next') . '</a></li>',
              ];
            }
            else {
              $form['question'][$value->id] = [
                '#type' => 'radios',
                '#title' => $this->t('@question', ['@question' => Xss::filter($value->question)]),
                '#options' => $options,
                '#prefix' => '<li id="examslide_' . $key . '" class="exam_spider_slider">',
                '#suffix' => ' <a class="exam_spider_slide_next button" href="#next">' . $this->t('Next') . '</a></li>',
              ];
            }
          }
          $form['next'] = [
            '#type' => 'submit',
            '#prefix' => '<li id="examslide_' . $total_slides . '" class="exam_spider_slider">' . $this->t('<h2>I am done.</h2><br />'),
            '#suffix' => '</li>',
            '#value' => $this->t('Submit'),
          ];
          $form['#tree'] = TRUE;
          $form['li_suffix'] = [
            '#markup' => '</ul>',
          ];
        }
        $form['#suffix'] = $output;
      }
    }
    return $form;
  }

  /**
   * Exam continue page form submit callbacks.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $score_obtain = $total_marks = $wrong_quest = 0;
    $exam_data = $this->ExamSpiderData->examSpiderGetExam($form_state->getValue('exam_id'));
    $total_marks = $exam_data['total_marks'];
    $negative_mark = $exam_data['negative_mark'];
    $negative_mark_per = $exam_data['negative_mark_per'];
    $total_quest = count($form_state->getValue('question'));
    $mark_per_quest = ($total_marks / $total_quest);
    $negative_marking_number = (($mark_per_quest * $negative_mark_per) / 100);
    foreach ($form_state->getValue('question') as $key => $answervalues) {
      $question_data = $this->ExamSpiderData->examSpiderGetQuestion($key);
      if (is_array($answervalues)) {
        $answer_combine = '';
        foreach ($answervalues as $key => $answervalue) {
          if ($answervalue != 0) {
            $answer_combine .= 'opt' . $answervalue . '-';
          }
        }
        $checkanswer = rtrim($answer_combine, "-");
        if ($checkanswer == $question_data['answer']) {
          $score_obtain += $mark_per_quest;
        }
        else {
          if ($negative_mark == 1) {
            $score_obtain -= $negative_marking_number;
          }
          $wrong_quest += 1;
        }
      }
      else {
        $checkanswer = 'opt' . $answervalues;
        if ($checkanswer == $question_data['answer']) {
          $score_obtain += $mark_per_quest;
        }
        else {
          if ($negative_mark == 1) {
            $score_obtain -= $negative_marking_number;
          }
          $wrong_quest += 1;
        }
      }
    }
    $correct_answers = $total_quest - $wrong_quest;
    $reg_id = db_insert('exam_results')
      ->fields(['examid', 'uid', 'total', 'obtain', 'wrong', 'created'])
      ->values([
        'examid' => $form_state->getValue('exam_id'),
        'uid' => $this->currentUser->id(),
        'total' => $total_marks,
        'obtain' => $score_obtain,
        'wrong' => $wrong_quest,
        'created' => REQUEST_TIME,
      ])
      ->execute();
    drupal_set_message($this->t('Your @examSpiderExamTitle has submitted successfully and your REG id is REG-@reg_id.', ['@examSpiderExamTitle' => EXAM_SPIDER_EXAM_TITLE, '@reg_id' => $reg_id]));
    $exam_result_data = $this->t('<b>You have got @score_obtain marks out of @total_marks<br/>Correct Answer(s) @correctAnswers <br/>Wrong Answer(s) @wrong_quest<b>', [
      '@score_obtain' => $score_obtain,
      '@total_marks' => $total_marks,
      '@correctAnswers' => $correct_answers,
      '@wrong_quest' => $wrong_quest,
    ]);
    $_SESSION['exam_result_data'] = $exam_result_data;
  }

}
