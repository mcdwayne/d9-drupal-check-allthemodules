<?php
namespace Drupal\sfs\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sfs\ReportSpam;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfirmReportUserForm extends ConfirmFormBase {
  protected $to_report_id;
  protected $username;
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
    return 'sfs_confirm_user_report';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = '') {
    $this->to_report_id = (int) $user;
    $this->username = User::load($this->to_report_id)->getAccountName();
    
    $form = parent::buildForm($form, $form_state);
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to report @name (@id) as spammer?', ['@name' => $this->username, '@id' => $this->to_report_id]);
  }
  
  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.user.edit_form', ['user' => $this->to_report_id]);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->reportSpam->userReport($this->to_report_id);
    
    return new Url('entity.user.edit_form', ['user' => $this->to_report_id]);
  }
}
