<?php

namespace Drupal\civicrm_tools_rest\Form;

use Drupal\civicrm_tools\CiviCrmGroupInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Drupal\civicrm_tools\CiviCrmGroupInterface definition.
   *
   * @var \Drupal\civicrm_tools\CiviCrmGroupInterface
   */
  protected $civicrmGroup;

  /**
   * Constructs a new SettingsForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    CiviCrmGroupInterface $civicrm_tools_group
    ) {
    parent::__construct($config_factory);
    $this->civicrmGroup = $civicrm_tools_group;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('civicrm_tools.group')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'civicrm_tools_rest.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'civicrm_tools_rest_settings';
  }

  /**
   * Prepares a list of CiviCRM Groups for select form element.
   *
   * @return array
   *   Map of group labels indexed by group id.
   */
  private function getGroupSelectOptions() {
    $result = [];
    $groups = $this->civicrmGroup->getAllGroups();
    foreach ($groups as $id => $group) {
      $result[$id] = $group['title'];
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('civicrm_tools_rest.settings');
    $form['group_limit'] = [
      '#type' => 'select',
      '#title' => $this->t('Group limit'),
      '#description' => $this->t('Limit the groups that are exposed by the web service. If empty all groups will be exposed.'),
      '#options' => $this->getGroupSelectOptions(),
      '#size' => 5,
      '#multiple' => TRUE,
      '#default_value' => $config->get('group_limit'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('civicrm_tools_rest.settings')
      ->set('group_limit', $form_state->getValue('group_limit'))
      ->save();
  }

}
