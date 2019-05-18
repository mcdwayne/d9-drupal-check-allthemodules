<?php

namespace Drupal\advban\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\advban\AdvbanIpManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form to unban IP addresses.
 */
class AdvbanDelete extends ConfirmFormBase {

  /**
   * The banned IP address.
   *
   * @var string
   */
  protected $banIp;

  /**
   * The banned IP address (end of range).
   *
   * @var string
   */
  protected $banIpEnd;

  /**
   * The IP manager.
   *
   * @var \Drupal\advban\AdvbanIpManagerInterface
   */
  protected $ipManager;

  /**
   * Constructs a new BanDelete object.
   *
   * @param \Drupal\advban\AdvbanIpManagerInterface $ip_manager
   *   The IP manager.
   */
  public function __construct(AdvbanIpManagerInterface $ip_manager) {
    $this->ipManager = $ip_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('advban.ip_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advban_ip_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $formatIp = $this->ipManager->formatIp($this->banIp, $this->banIpEnd);
    return $this->t('Are you sure you want to unblock %ip?', ['%ip' => $formatIp]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('advban.admin_page');
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   Form variable.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   FormState variable.
   * @param string $ban_id
   *   The IP address record ID to unban.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ban_id = '') {
    $ip_info = $this->ipManager->findById($ban_id);
    if (empty($ip_info)) {
      throw new NotFoundHttpException();
    }
    if (is_array($ip_info)) {
      $ip_info = reset($ip_info);
    };
    $this->banIp = $ip_info->ip;
    $this->banIpEnd = $ip_info->ip_end;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->ipManager->unbanIp($this->banIp, $this->banIpEnd);
    $formatIp = $this->ipManager->formatIp($this->banIp, $this->banIpEnd);
    $this->logger('advanced ban')->notice('Deleted %ip', ['%ip' => $formatIp]);
    drupal_set_message($this->t('The IP address(es) %ip was deleted.', ['%ip' => $formatIp]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
