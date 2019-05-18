<?php

namespace Drupal\eform\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eform\Entity\EFormType;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form class to build EForm Type Defaults form.
 */
class EFormTypeDefaultsForm extends ConfigFormBase {

  /**
   * @var \Drupal\eform\Form\EFormTypeForm
   */
  protected $EFormTypeForm;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EFormTypeForm $eformtype_form) {
    parent::__construct($config_factory);
    $this->setConfigFactory($config_factory);
    $this->EFormTypeForm = $eformtype_form;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $eformtype_form = EFormTypeForm::create($container);
    $eformtype_form->setEntityManager($container->get('entity.manager'));
    return new static(
      $container->get('config.factory'),
      $eformtype_form
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eform_type_defaults';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();

    $config = $this->configFactory()->getEditable('eform.type_defaults');
    $keys = array_keys($config->getRawData());
    foreach ($keys as $key) {
      $config->clear($key);
    }

    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['eform.type_defaults'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->configFactory()->get('eform.type_defaults');
    $default_values = $config->getRawData();
    $eform_type = new EFormType($default_values, 'eform_type');
    $form += $this->EFormTypeForm->EFormTypeElements($form, $eform_type);
    // Unset "Use default" settings.
    unset($form['submission_views']['admin_submissions_view']['#options'][EFormTypeForm::VIEW_DEFAULT]);
    unset($form['submission_views']['user_submissions_view']['#options'][EFormTypeForm::VIEW_DEFAULT]);
    return parent::buildForm($form, $form_state);
  }

}
