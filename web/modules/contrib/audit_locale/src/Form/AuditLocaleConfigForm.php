<?php

namespace Drupal\audit_locale\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class AuditLocaleConfigForm extends FormBase {
  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   *
   */
  public function __construct(QueryFactory $query_factory, EntityStorageInterface $storage) {
    $this->queryFactory = $query_factory;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity.manager')->getStorage('audit_locale')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'audit_locale_common_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['module'] = [
      '#type' => 'textfield',
      '#size' => 60,
      '#maxlength' => 128,
      '#attributes' => [
        'class' => ['form-control'],
        'placeholder' => '待审批的模块名',
      ],
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => '保存',
      '#attributes' => [
        'class' => ['btn btn-primary btn-lg'],
      ],
    ];
    $form['#theme'] = 'audit_locale_config_form';
    $form['#attached']['library'] = ['audit_locale/audit_locale_overview_form'];
    return $form;
  }

  /**
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $module = $form_state->getValue('module');
    if (!\Drupal::moduleHandler()->moduleExists($module)) {
      $form_state->setErrorByName('module', $module . '不存在，请重新输入。');
    }
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl(new Url('audit_locale.rule.overview', ['module' => $form_state->getValue('module')]));
  }

}
