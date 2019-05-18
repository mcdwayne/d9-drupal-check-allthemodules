<?php

namespace Drupal\custom_configuration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\custom_configuration\Helper\ConfigurationHelper;
use Drupal\Core\Link;

/**
 * Class CustomConfigurationList.
 *
 * @package Drupal\custom_configuration\Form
 */
class CustomConfigurationList extends ConfigFormBase {

  /**
   * Helper object.
   *
   * @var Drupal\custom_configuration\Helper\ConfigurationHelper
   */
  protected $configHelpler;

  /**
   * Character limit when showing config value.
   *
   * @var string
   */
  public $cahracterLimit = 100;

  /**
   * Construct of the Custom Configuration List.
   *
   * @param \Drupal\custom_configuration\Form\ConfigurationHelper $helper
   *   This will create an object of the ConfigurationHelper class.
   */
  public function __construct(ConfigurationHelper $helper) {
    $this->configHelpler = $helper;
  }

  /**
   * This will help us to achieve the dependency injection.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   It will get all the services inside the container interface.
   *
   * @return \static
   *   It will return the service of the class.
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('custom.configuration')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['custom_configuration.listShow'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_configuration_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['config-list'] = [
      '#type' => 'table',
      '#header' => [$this->t('Config Name'), $this->t('Machine Name'), $this->t(
            'Value'), $this->t('Lang'), $this->t('Domain'), $this->t('Status'),
        $this->t('Edit'),
        $this->t('Delete'),
      ],
      '#empty' => $this->t('There are no items yet. Add a Custom Configuration.'),
    ];
    $result_list = $this->configHelpler->getConfigList();
    foreach ($result_list as $list) {
      $language = $list->custom_config_langcode;
      $language = $this->configHelpler->getLanguageName($language);
      $list->custom_config_langcode = implode(',', $language);

      $domains = $list->custom_config_domains;
      $domains = $this->configHelpler->getDomainName($domains);
      $list->custom_config_domains = implode(',', $domains);

      if (strlen($list->custom_config_value) > $this->cahracterLimit) {
        $list->custom_config_value = substr($list->custom_config_value, 0, $this->cahracterLimit) . '....';
      }
      $form['config-list'][$list->custom_config_id]['config_name'] = [
        '#markup' => $list->custom_config_name,
      ];
      $form['config-list'][$list->custom_config_id]['machine_name'] = [
        '#markup' => $list->custom_config_machine_name,
      ];
      $form['config-list'][$list->custom_config_id]['custom_config_value'] = [
        '#markup' => $list->custom_config_value,
      ];
      $form['config-list'][$list->custom_config_id]['langcode'] = [
        '#markup' => $list->custom_config_langcode,
      ];
      $form['config-list'][$list->custom_config_id]['domain_key'] = [
        '#markup' => $list->custom_config_domains,
      ];
      $form['config-list'][$list->custom_config_id]['custom_config_status'] = [
        '#markup' => ($list->custom_config_status == 1) ? 'ACTIVE' : 'INACTIVE',
      ];
      $edit_link = Link::createFromRoute(
              $this->t('Edit'), 'custom_configuration.edit_configuration_list', ['custom_config_id' => $list->custom_config_id]
          )->toString();
      $form['config-list'][$list->custom_config_id]['edit'] = [
        '#markup' => $edit_link,
      ];
      $delete_link = Link::createFromRoute($this->t('Delete'), 'custom_configuration.delete_configuration_list', ['custom_config_id' => $list->custom_config_id])->toString();
      $form['config-list'][$list->custom_config_id]['delete'] = [
        '#markup' => $delete_link,
      ];
    }
    return $form;
  }

}
