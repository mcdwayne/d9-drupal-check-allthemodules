<?php

namespace Drupal\wsdata\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\wsdata\Plugin\WSConnectorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WSServerForm.
 *
 * @package Drupal\wsdata\Form
 */
class WSServerForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function __construct(WSConnectorManager $plugin_manager_wsconnector) {
    $this->plugin_manager_wsconnector = $plugin_manager_wsconnector;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('plugin.manager.wsconnector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $wsserver_entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $wsserver_entity->label(),
      '#description' => $this->t("Label for the Web Service Server."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $wsserver_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\wsdata\Entity\WSServer::load',
      ],
      '#disabled' => !$wsserver_entity->isNew(),
    ];

    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint'),
      '#maxlength' => 1024,
      '#default_value' => $wsserver_entity->endpoint,
      '#description' => $this->t("Endpoint for this webservice entity."),
      '#required' => TRUE,
    ];

    $connector_definitions = $this->plugin_manager_wsconnector->getDefinitions();

    $options = [];
    foreach ($connector_definitions as $key => $connector) {
      $options[$key] = $connector['label']->render();
    }

    $form['wsconnector'] = [
      '#type' => 'select',
      '#title' => $this->t('Connector'),
      '#description' => $this->t('Methods that data is retrieved.'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $wsserver_entity->wsconnector,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $wsserver_entity = $this->entity;
    $status = $wsserver_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Web Service Server.', [
          '%label' => $wsserver_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Web Service Server.', [
          '%label' => $wsserver_entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($wsserver_entity->urlInfo('collection'));
  }

}
