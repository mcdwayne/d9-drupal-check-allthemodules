<?php
namespace Drupal\sfs\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sfs\ReportSpam;

class ConfirmReportCommentForm extends ConfirmFormBase {
  protected $to_report_id;
  protected $reportSpam;
  
  /**
   * Class constructor.
   */
  public function __construct(ReportSpam $report_spam) {
    $this->reportSpam = $report_spam;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('sfs.report.spam')
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sfs_confirm_comment_report';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $comment = '') {
    $this->to_report_id = (int) $comment;
    
    $form = parent::buildForm($form, $form_state);
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to report comment %id as spam?', ['%id' => $this->to_report_id]);
  }
  
  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.comment.edit_form', ['comment' => $this->to_report_id]);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->reportSpam->commentReport($this->to_report_id);
    
    return new Url('entity.comment.edit_form', ['comment' => $this->to_report_id]);
  }
}
