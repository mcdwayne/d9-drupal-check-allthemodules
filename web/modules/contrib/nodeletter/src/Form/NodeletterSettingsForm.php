<?php

namespace Drupal\nodeletter\Form;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Field\FormatterPluginManager;
use \Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\nodeletter\Entity\NodeTypeSettings;
use Drupal\nodeletter\Entity\TemplateVariableSetting;
use Drupal\nodeletter\MailchimpApiTrait;
use Drupal\nodeletter\NodeletterService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NodeletterSettingsForm extends ConfigFormBase {

  use MailchimpApiTrait;


  /** @var NodeletterService */
  protected $nodeletterService;

  /** @var EntityFieldManagerInterface */
  protected $entityFieldManager;

  /** @var  FieldTypePluginManagerInterface */
  protected $fieldTypeManager;

  /** @var FormatterPluginManager */
  protected $fieldFormatterManager;

  private $_node_type_field_options = [];

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              NodeletterService $nodeletterService,
                              EntityFieldManagerInterface $entityFieldManager,
                              FieldTypePluginManagerInterface $fieldTypeManager,
                              FormatterPluginManager $formatterManager) {
    parent::__construct($config_factory);
    $this->nodeletterService = $nodeletterService;
    $this->entityFieldManager = $entityFieldManager;
    $this->fieldTypeManager = $fieldTypeManager;
    $this->fieldFormatterManager = $formatterManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('nodeletter'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('plugin.manager.field.formatter')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nodeletter_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'nodeletter.settings',
    ];
  }


  protected function buildConfigEntities(array $form, FormStateInterface $form_state) {

    // TODO: implement or remove.
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('nodeletter.settings');

    $mailchimp_template_options = [];

    if (!$this->isMailchimpUsable()) {
      $msg = 'MailChimp setup not functional. Please check the <a ' .
        'href=":mailchimp_settings_url">MailChimp module settings</a>!';
      $msg_vars = [
        ':mailchimp_settings_url' => Url::fromRoute('mailchimp.admin')
          ->toString()
      ];
      drupal_set_message(t($msg, $msg_vars), 'warning');
    }
    else {
      $tpls = $this->getMailChimpTemplates();
      foreach ($tpls as $tpl) {
        $mailchimp_template_options[$tpl->id] = $tpl->name;
      }
    }

    /**
     * Allow clever mapping of submitted values to config-entities
     * during form rebuilds (used in ajax worflows).
     *
     * @see NodeletterSettingsForm::afterBuild
     */
    $form['#after_build'][] = '::afterBuild';


    $form['from_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Default "from"-name'),
      '#description' => $this->t('This name will be show in the "From" field ' .
        'of the newsletter. NOTICE: this is not a e-mail address but a ' .
        'name. E.g.: "Daily Bugle Ltd.". If empty the site name '.
        'will be used as from-name.'),
      '#default_value' => $config->get('from_name'),
    );

    $form['reply_to_address'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Default "reply to"-address'),
      '#description' => $this->t('This e-mail address will be set in the ' .
        '"reply-to" field of the newsletter. If empty the site mail will be ' .
        'used as reply-to address.'),
      '#default_value' => $config->get('reply_to_address'),
    );

    $form['nodeletter_allow_sending'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Enable newsletter sending'),
        '#description' => $this->t('If this is unchecked, ' .
            'you can only use the Newsletter test email function, ' .
            'but you can not send to a real list. NOTICE: ' .
            'this should be <strong>unchecked</strong> on development environments.'),
        '#default_value' => $config->get('nodeletter_allow_sending'),
    );

    $form['sending_monitor_interval'] = array(
      '#type' => 'number',
      '#title' => $this->t('Minimum interval for monitoring active sendings'),
      '#description' => $this->t('Active sendings will be continuously ' .
        'checked for updates from the 3rd party service provider until ' .
        'it gets flagged as complete. This interval is dependent on the ' .
        'interval of <a href=":cron_settings">Drupal\'s cron</a> runs. <em>An ' .
        'interval of 0 indicates that monitoring of sendings will be ' .
        'done on each cron run.</em>',
        [
        ':cron_settings' => Url::fromRoute('system.cron_settings')->toString()
      ]),
      '#field_suffix' => $this->t('Minutes'),
      '#default_value' => $config->get('sending_monitor_interval'),
      '#min' => 0,
      '#max' => 1440,
      '#required' => TRUE,
    );

    $form['node_types'] = [
      '#type' => 'item',
      '#title' => t('Content Type Settings'),
      '#tree' => TRUE,
      '#weight' => 232,
    ];


    /** @var NodeType[] $enabled_node_types */
    $enabled_node_types = $this->nodeletterService->getEnabledNodeTypes();

    if (empty($enabled_node_types)) {
      $form['node_types']['none_container'] = [
        '#type' => 'fieldset',
      ];
      $form['node_types']['none_container']['none_message'] = [
        '#type' => 'item',
        '#title' => $this->t('None enabled'),
        '#description' => $this->t('Currently no content type has the nodeletter functionality enabled.'),
      ];
    }

    foreach ($enabled_node_types as $node_type) {

      $node_type_id = $node_type->id();
      $settings = $this->nodeletterService->getNodeletterSettings($node_type, 'mailchimp');
      $sender = $this->nodeletterService->getNodeletterSender($node_type);

      $template_id = $settings->getTemplateName();
      if ( ! empty($template_id)) {
        $template_name = '- invalid -';
        $templates = $sender->getTemplates();
        foreach($templates as $template) {
          if ($template->getId() == $template_id) {
            $template_name = $template->getLabel();
            break;
          }
        }
      } else {
        $template_name = '- none -'; // TODO: use t()!
      }

      $list_id = $settings->getListID();
      if (! empty($list_id)) {
        $list_name = '- invalid -';
        $lists = $sender->getRecipientLists();
        foreach($lists as $list) {
          if ($list->getId() == $list_id) {
            $list_name = $list->getLabel();
            break;
          }
        }
      } else {
        $list_name = '- none - ';
      }

      $form['node_types'][$node_type_id] = [
        '#type' => 'details',
        '#title' => $node_type->label(),
        '#tree' => TRUE,
        '#open' => FALSE,
      ];

      $form['node_types'][$node_type_id]['template_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Mailchimp template'),
        '#value' => $template_name,
        '#disabled' => TRUE,
      ];

      $form['node_types'][$node_type_id]['list_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Mailchimp List'),
        '#value' => $list_name,
        '#disabled' => TRUE,
      ];

      $form['node_types'][$node_type_id]['settings'] = [
        '#type' => 'link',
        '#title' => $this->t('Edit Configuration'),
        '#url' => Url::fromRoute("nodeletter.node_type_settings", ["node_type" => $node_type_id]),
        '#options' => [
          'attributes' => [
            'class' => ['button', 'button--small']
          ],
        ],
      ];

    }

    return parent::buildForm($form, $form_state);
  }


  /**
   * Form element #after_build callback: Updates the config-entities with submitted data.
   *
   * @see EntityForm::afterBuild
   * @see EntityForm::copyFormValuesToEntity
   *
   */
  public function afterBuild(array $element, FormStateInterface $form_state) {
    // Rebuild the entity if #after_build is being called as part of a form
    // rebuild, i.e. if we are processing input.
    if ($form_state->isProcessingInput()) {
      $this->configs = $this->buildConfigEntities($element, $form_state);
    }

    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // If the main "Save" button was submitted while a field settings subform
    // was being edited, update the new incoming settings when rebuilding the
    // entity, just as if the subform's "Update" button had been submitted.
    if ($edit_field = $form_state->get('plugin_settings_edit')) {
      $form_state->set('plugin_settings_update', $edit_field);
    }


    $config = $this->config('nodeletter.settings');
    $config->set('from_name', $form_state->getValue('from_name'))
      ->set('reply_to_address', $form_state->getValue('reply_to_address'))
      ->set('nodeletter_allow_sending', $form_state->getValue('nodeletter_allow_sending'))
      ->set('sending_monitor_interval',
        intval($form_state->getValue('sending_monitor_interval')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
