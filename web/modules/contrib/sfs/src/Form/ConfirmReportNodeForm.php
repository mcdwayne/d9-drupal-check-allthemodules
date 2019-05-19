<?php
namespace Drupal\sfs\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sfs\ReportSpam;

class ConfirmReportNodeForm extends ConfirmFormBase {
  protected $to_report_id;
  protected $to_report_entity_type;
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
    return 'sfs_confirm_node_report';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = '') {
    $this->to_report_id = (int) $node;
    
    $form = parent::buildForm($form, $form_state);
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to report content item %id as spam?', ['%id' => $this->to_report_id]);
  }
  
  public function getCancelUrl() {
    return Url::fromUserInput("/node/{$this->to_report_id}/edit");
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->reportSpam->nodeReport($this->to_report_id);
    
    $form_state->setRedirectUrl(Url::fromUserInput("/node/{$this->to_report_id}/edit"));
  }
}
