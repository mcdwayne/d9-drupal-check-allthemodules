<?php

namespace Drupal\doccheck_basic\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Path\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a form to configure module settings.
 */
class SettingsForm extends ConfigFormBase {

  const CUSTOM_TEMPLATE = '_custom_';

  /**
   * The variable containing the user manager.
   *
   * @var \Drupal\Core\Entity\UserStorageInterface
   */
  private $userManager;

  /**
   * The variable containing the request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Dependency injection through the constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The request stack service.
   */
  public function __construct(EntityTypeManager $entityTypeManager, RequestStack $requestStack, AliasManagerInterface $alias_manager) {
    $this->userManager = $entityTypeManager->getStorage('user');
    $this->requestStack = $requestStack;
    $this->aliasManager = $alias_manager;
  }

  /**
   * Dependency injection create.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('path.alias_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'doccheck_basic.settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['config.doccheck_basic'];
  }

  /**
   * Builds settings form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('config.doccheck_basic');

    $form['doccheck_cream'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings, copy to DocCheck CReaM (crm.doccheck.com)'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['doccheck_cream']['loginlink'] = [
      '#type' => 'item',
      '#title' => $this->t('Login URL (or use any URL which shows DocCheck Basic block)'),
      '#description' => $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/doccheck-login',
    ];
    $form['doccheck_cream']['targetlink'] = [
      '#type' => 'item',
      '#title' => $this->t('Target URL'),
      '#description' => $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/_dc_callback',
    ];

    $form['doccheck_loginid'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Login ID from DocCheck CReaM'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['doccheck_loginid']['loginid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DocCheck Login ID'),
      '#default_value' => $config->get('dc_loginid'),
      '#size' => 13,
      '#required' => TRUE,
    ];

    $form['doccheck_template'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('DocCheck Template'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['doccheck_template']['template'] = [
      '#type' => 'select',
      '#title' => $this->t('Standard template'),
      '#default_value' => $config->get('dc_template'),
      '#options' => [
        's_red' => 'S',
        'm_red' => 'M',
        'l_red' => 'L',
        'xl_red' => 'XL',
        'login_s' => 'S (new design)',
        'login_m' => 'M (new design)',
        'login_l' => 'L (new design)',
        'login_xl' => 'XL (new design)',
        self::CUSTOM_TEMPLATE => $this->t('Custom'),
      ],
      '#description' => $this->t('Template for DocCheck login frame') ,
      '#required' => TRUE,
    ];
    $form['doccheck_template']['template_custom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom template name'),
      '#default_value' => $config->get('dc_template_custom'),
    ];
    $form['doccheck_template']['template_custom_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width for custom template'),
      '#default_value' => $config->get('dc_template_custom_width'),
      '#min' => 0,
    ];
    $form['doccheck_template']['template_custom_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height for custom template'),
      '#default_value' => $config->get('dc_template_custom_height'),
      '#min' => 0,
    ];

    $form['doccheck_basic'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings for local site'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['doccheck_basic']['devmode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Development mode'),
      '#default_value' => $config->get('dc_devmode'),
      '#description' => $this->t('Show direct login button for development, before Doccheck account is set up.') ,
    ];

    $filtered_users = [];
    $ids = $this->userManager->getQuery()
      ->condition('status', 1)
      ->condition('roles', 'anonymous', '<>')
      ->condition('roles', 'administrator', '<>')
      ->execute();
    $all_users = $this->userManager->loadMultiple($ids);
    foreach ($all_users as $key => $value) {
      $filtered_users[$key] = $value->getDisplayName();
    }
    $form['doccheck_basic']['user'] = [
      '#type' => 'select',
      '#title' => $this->t('Login as User'),
      '#description' => $this->t('Users with "Administrator", "Anonymous user" and "Authenticated user" only roles are excluded. An additional role is required. Please ensure that this role has view rights for the protected pages.'),
      '#default_value' => $config->get('dc_user'),
      '#required' => TRUE,
      '#options' => $filtered_users,
    ];
    $form['doccheck_basic']['noderedirect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page login redirect'),
      '#description' => $this->t('This page is displayed when logging in using page /doccheck-login . Leave blank to return to page /doccheck-login .'),
      '#default_value' => $config->get('dc_noderedirect'),
    ];
    $form['doccheck_basic']['blockredirect'] = [
      '#type' => 'item',
      '#title' => $this->t('Block login redirect'),
      '#description' => $this->t('When logging in using a block, you will return to the URL with the login block.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validate submit.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('noderedirect')) {
      $form_state->setValueForElement($form['doccheck_basic']['noderedirect'], $this->aliasManager->getPathByAlias($form_state->getValue('noderedirect')));
    }
    if (($value = $form_state->getValue('noderedirect')) && $value[0] !== '/') {
      $form_state->setErrorByName('noderedirect', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('noderedirect')]));
    }
    if ($form_state->getValue('template') == self::CUSTOM_TEMPLATE && $form_state->getValue('template_custom') == '') {
      $form_state->setErrorByName('template_custom', $this->t('Select standard template or type in custom template name.'));
    }
  }

  /**
   * Handles submit.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('config.doccheck_basic');

    $config->set('dc_devmode', $form_state->getValue('devmode'));
    $config->set('dc_template', $form_state->getValue('template'));
    $config->set('dc_template_custom', $form_state->getValue('template_custom'));
    $config->set('dc_template_custom_width', $form_state->getValue('template_custom_width'));
    $config->set('dc_template_custom_height', $form_state->getValue('template_custom_height'));
    $config->set('dc_loginid', $form_state->getValue('loginid'));
    $config->set('dc_user', $form_state->getValue('user'));
    $config->set('dc_noderedirect', $form_state->getValue('noderedirect'));

    $config->save();

    return parent::submitForm($form, $form_state);
  }

}
