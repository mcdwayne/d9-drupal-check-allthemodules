<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;

/**
 * Provides a 'MaintenanceMode' action.
 *
 * @Action(
 *  id = "drd_action_maintenance_mode",
 *  label = @Translation("Maintenance Mode"),
 *  type = "drd_domain",
 * )
 */
class MaintenanceMode extends BaseEntityRemote implements BaseConfigurableInterface {

  /**
   * {@inheritdoc}
   */
  protected function setDefaultArguments() {
    parent::setDefaultArguments();
    $this->arguments['mode'] = 'getStatus';
  }

  /**
   * {@inheritdoc}
   */
  public function executeAction(RemoteEntityInterface $domain) {
    $response = parent::executeAction($domain);
    if ($response) {
      /* @var \Drupal\drd\Entity\DomainInterface $domain */
      if ($this->arguments['mode'] == 'getStatus') {
        $domain->cacheMaintenanceMode($response['data']);
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['drd_action_maintenance_mode'] = [
      '#type' => 'select',
      '#title' => t('Mode'),
      '#default_value' => 'getStatus',
      '#options' => [
        'getStatus' => t('get maintenance mode'),
        'on' => t('turn on maintenance mode'),
        'off' => t('turn off maintenance mode'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->arguments['mode'] = $form_state->getValue('drd_action_maintenance_mode');
  }

}
