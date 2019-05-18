<?php

namespace Drupal\nodeify\Form;

use Drupal\nodeify\TokenInfoTrait;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MessageSettingsForm extends ConfigFormBase {

  use TokenInfoTrait;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['nodeify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nodeify_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    parent::__construct($config_factory);

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($entity_types as $node_type => $info) {
      $form[$node_type] = [
        '#title' => $info->label(),
        '#type' => 'details',
        '#tree' => TRUE,
      ];

      foreach (['create', 'delete', 'update'] as $action) {
        $config = $info->getThirdPartySetting('nodeify', 'text');
        $message = !empty($config[$action]) ? $config[$action] : '';
        $form[$node_type][$action] = [
          '#type' => 'textarea',
          '#title' => $this->t('@action', ['@action' => $action]),
          '#default_value' => $message,
        ];
        // Only open it if one of the actions have an override.
        if (!empty($message) && !isset($form[$node_type]['#open'])) {
          $form[$node_type]['#open'] = TRUE;
        }
      }
    }
    $form['token_info'] = $this->getTokenInfoList();
    $form_state->setTemporaryValue('entity_types', $entity_types);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_types = $form_state->getTemporaryValue('entity_types');
    $values = $form_state->cleanValues()->getValues();
    foreach ($values as $key => $messages) {
      $entity_types[$key]->setThirdPartySetting('nodeify', 'text', $messages);
      $entity_types[$key]->save();
    }
    parent::submitForm($form, $form_state);
  }

}
