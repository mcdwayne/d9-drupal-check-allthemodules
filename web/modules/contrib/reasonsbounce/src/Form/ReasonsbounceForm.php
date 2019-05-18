<?php

/**
 * @file
 * Contains \Drupal\reasonsbounce\Controller\ReasonsbounceForm.
 */

namespace Drupal\reasonsbounce\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\contact\ContactFormInterface;
use \Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

class ReasonsbounceForm extends ConfigFormBase {
  
  /**
   * @var \Drupal\contact\ContactFormInterface
   */
  protected $contact_form;
  
  /**
   * The condition manager.
   *
   * @var \Drupal\Component\Plugin\Factory\FactoryInterface
   */
  protected $conditionManager;

  /**
   * The request path condition.
   *
   * @var \Drupal\system\Plugin\Condition\RequestPath $condition
   */
  protected $path_condition;
  
  /**
   * The node type condition.
   *
   * @var \Drupal\node\Plugin\Condition\NodeType
   */
  protected $node_type_condition;
  
  /**
   * The user role condition.
   *
   * @var \Drupal\user\Plugin\Condition\UserRole
   */
  protected $user_role_condition;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reasonsbounce_form';
  }

  /**
   * Creates a new PathMessageAdminForm.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Component\Plugin\Factory\FactoryInterface $plugin_factory
   *   The condition plugin factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FactoryInterface $plugin_factory) {
    $this->path_condition = $plugin_factory->createInstance('request_path');
    $this->node_type_condition = $plugin_factory->createInstance('node_type');
    $this->user_role_condition = $plugin_factory->createInstance('user_role');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ContactFormInterface $contact_form = NULL) {    
    $this->contact_form = $contact_form;
  
    // Message settings.
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#default_value' => $contact_form->getThirdPartySetting('reasonsbounce', 'message'),
      '#rows' => 3,
      '#wysiwyg' => FALSE,
      '#required' => TRUE,
    ];

    $this->path_condition->setConfiguration($contact_form->getThirdPartySetting('reasonsbounce', 'request_path', []));
    $form += $this->path_condition->buildConfigurationForm($form, $form_state);

    $this->node_type_condition->setConfiguration($contact_form->getThirdPartySetting('reasonsbounce', 'node_types', []));
    $form += $this->node_type_condition->buildConfigurationForm($form, $form_state);

    $this->user_role_condition->setConfiguration($contact_form->getThirdPartySetting('reasonsbounce', 'roles', []));
    $form += $this->user_role_condition->buildConfigurationForm($form, $form_state);

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->path_condition->submitConfigurationForm($form, $form_state);
    $this->node_type_condition->submitConfigurationForm($form, $form_state);
    $this->user_role_condition->submitConfigurationForm($form, $form_state);

    $this->contact_form->setThirdPartySetting('reasonsbounce', 'message', SafeMarkup::checkPlain($form_state->getValue('message')));
    $this->contact_form->setThirdPartySetting('reasonsbounce', 'request_path', $this->path_condition->getConfiguration());
    $this->contact_form->setThirdPartySetting('reasonsbounce', 'node_types', $this->node_type_condition->getConfiguration());
    $this->contact_form->setThirdPartySetting('reasonsbounce', 'roles', $this->user_role_condition->getConfiguration());
    $this->contact_form->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('contact.form.' . $this->contact_form->id() . '.third_party.reasonsbounce');
  }
  
}