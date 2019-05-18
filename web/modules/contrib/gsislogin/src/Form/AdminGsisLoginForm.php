<?php

namespace Drupal\gsislogin\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Configure custom settings for this site.
 */
class AdminGsisLoginForm extends ConfigFormBase {

  private $myConfig = NULL;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * Constructor for AdminGsisLoginForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->request = $request_stack->getCurrentRequest();
    $this->myConfig = $config_factory->get('config.gsislogin');
    parent::__construct($config_factory, $request_stack);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('config.factory'),
    $container->get('request_stack')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'gsislogin_admin_form';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['config.gsislogin'];
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['gsislogin']['EXPLAIN'] = [
      'explain' => [
        '#type' => 'markup',
        '#title' => $this->t('Registration'),
        '#markup' => $this->t("You have to set the") . " <strong>" . $this->request->getScheme() . "://" . $this->request->getHost() . "/gsis" . "</strong> " . $this->t("to allowed urls, on gsis oauth2 registration console."),
      ],
    ];

    $form['gsislogin']['GSISID'] = [
      'gsisid' => [
        '#type' => 'textfield',
        '#title' => $this->t('Gsis ID'),
        '#maxlength' => 255,
        '#default_value' => $this->myConfig->get('GSISID') ? $this->myConfig->get('GSISID') : '',
        '#description' => $this->t('Gsis id.'),
      ],
    ];

    $form['gsislogin']['GSISSECRET'] = [
      'gsissecret' => [
        '#type' => 'textfield',
        '#title' => $this->t('Gsis Consumer Secret'),
        '#maxlength' => 255,
        '#description' => $this->t('Gsis secret.'),
      ],
    ];

    $form['gsislogin']['GSISTEST'] = [
      'gsistest' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Gsis test server'),
        '#maxlength' => 255,
        '#default_value' => $this->myConfig->get('GSISTEST') ? $this->myConfig->get('GSISTEST') : '',
        '#description' => $this->t('Select if you want to use the Gsis Test servers.'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $password = ($form_state->getValue('gsissecret') ? $form_state->getValue('gsissecret') : $this->myConfig->get('GSISSECRET'));

    $this->config('config.gsislogin')
      ->set('GSISID', $form_state->getValue('gsisid'))
      ->set('GSISSECRET', $password)
      ->set('GSISTEST', $form_state->getValue('gsistest'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
