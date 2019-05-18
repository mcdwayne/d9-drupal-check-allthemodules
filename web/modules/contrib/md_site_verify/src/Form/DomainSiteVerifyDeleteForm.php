<?php

namespace Drupal\md_site_verify\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\domain\DomainStorage;
use Drupal\md_site_verify\Service\DomainSiteVerifyService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DomainSiteVerifyDeleteForm
 *
 * @package Drupal\md_site_verify\Form
 */
class DomainSiteVerifyDeleteForm extends ConfirmFormBase {

  /**
   * @var null
   */
  protected $dSiteVerify = NULL;

  /**
   * @var \Drupal\Core\Database\Connection $database
   */
  protected $database;

  /**
   * Domain storage.
   *
   * @var \Drupal\md_site_verify\Service\DomainSiteVerifyService
   *   $domainSiteVerify
   */
  protected $domainSiteVerify;

  /**
   * Domain storage.
   *
   * @var \Drupal\domain\DomainStorage $domainStorage
   */
  protected $domainStorage;

  /**
   * DomainSiteVerifyDeleteForm constructor.
   *
   *
   * @param \Drupal\domain\DomainStorage $domainStorage
   *   The domain storage.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database loader.
   *
   * @param \Drupal\md_site_verify\Service\DomainSiteVerifyService $domainSiteVerify
   *   The service domain verification.
   */
  public function __construct(DomainStorage $domainStorage, Connection $database, DomainSiteVerifyService $domainSiteVerify) {
    $this->domainStorage = $domainStorage;
    $this->database = $database;
    $this->domainSiteVerify = $domainSiteVerify;
  }

  /**
   * Create function return static.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Load the ContainerInterface.
   *
   * @return static
   *   Return domain loader configuration and database and domain service verfy.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('domain'),
      $container->get('database'),
      $container->get('md_site_verify_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['md_site_verify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'md_site_verify_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if (!empty($this->dSiteVerify)) {
      $record = $this->domainSiteVerify->domainSiteVerifyLoad($this->dSiteVerify);
      return $this->t('Are you sure you want to delete the site verification %label?', ['%label' => $record['engine']['name']]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('md_site_verify.verifications_list');
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
  public function buildForm(array $form, FormStateInterface $form_state, $dsverify = NULL) {
    $this->dSiteVerify = $dsverify;
    $record = $this->domainSiteVerify->domainSiteVerifyLoad($this->dSiteVerify);
    $form = parent::buildForm($form, $form_state);
    $form['record'] = [
      '#type' => 'value',
      '#value' => $record,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $record = $form_state->getValue('record');
    $this->database->delete('md_site_verify')
      ->condition('dsv_id', $record['dsv_id'])
      ->execute();

    drupal_set_message(t('Verification for %engine has been deleted.', [
      '%engine' => $record['engine']['name'],
    ]));

    \Drupal::logger('md_site_verify')
      ->notice(t('Verification for %engine deleted.', [
        '%engine' => $record['engine']['name'],
      ]));
    $form_state->setRedirect('md_site_verify.verifications_list');

    // Set the menu to be rebuilt.
    \Drupal::service('router.builder')->setRebuildNeeded();
  }

}
