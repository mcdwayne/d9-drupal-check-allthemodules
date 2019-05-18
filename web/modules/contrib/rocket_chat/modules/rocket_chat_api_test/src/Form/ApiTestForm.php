<?php

namespace Drupal\rocket_chat_api_test\Form;

/**
 * Copyright (c) 2017.
 *
 * Authors:
 * - Lawri van Buël <sysosmaster@2588960.no-reply.drupal.org>.
 *
 * This file is part of (rocket_chat) a Drupal 8 Module for Rocket.Chat
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * @file
 * Contains \Drupal\rocket_chat_api_test\Form\ApiTestForm.
 *
 * This Form allows you to test Rocket chat API calls through the api Module.
 */

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\State\StateInterface;
use Drupal\rocket_chat_api\RocketChat\ApiClient;
use Drupal\rocket_chat_api\RocketChat\Drupal8Config;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class RocketChatSettingsForm.
 *
 * @package Drupal\rocket_chat_api_test\Form
 */
class ApiTestForm extends FormBase {

  private $moduleHandler;
  private $state;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The ModuleHandler to interact with loaded modules.
   * @param \Drupal\Core\State\StateInterface $state
   *   The StateInterface to manipulate state information.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler, StateInterface $state) {
    $this->setConfigFactory($config_factory);
    $this->moduleHandler = $moduleHandler;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    if (!empty($container)) {
      return new static(
        $container->get("config.factory"),
        $container->get("module_handler"),
        $container->get("state")
      );
    }
    else {
      // Something huge went wrong, we are missing the ContainerInterface.
      throw new ServiceNotFoundException('ContainerInterface');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rocket_chat_api_test.ApiTest';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $form['info'] = [
      '#type' => 'item',
      '#title' => $this->t('API Docs'),
      '#markup' => "See the <a href=\"https://rocket.chat/docs/developer-guides/rest-api\">Rocket chat docs on the rest-api</a> for" .
      " what method's there are and what input it needs.",
    ];

    $form['verb'] = [
      '#type' => 'select',
      '#title' => $this->t('HTTP Verb'),
      '#required' => TRUE,
      '#options' => [
        'GET' => $this->t('Get'),
        'POST' => $this->t('Post'),
      ],
      '#default_value' => 'GET',
    ];
    $form['method'] = [
      '#type' => 'textfield',
      '#description' => $this->t("Rocket chat Method name like 'me'"),
      '#title' => $this->t('Method'),
      '#required' => TRUE,
    ];

    $form['Options'] = [
      '#type' => 'textarea',
      '#title' => $this->t('JSON encoded options list'),
      '#description' => $this->t("Put a valid JSON string in this textarea."),
      '#required' => FALSE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Execute!'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // All required fields are submitted.
    if (!empty($form_state->getValue('Options'))) {
      $jsonOptions = Json::decode($form_state->getValue('Options'));
      if (!empty($form_state->getValue('Options')) && is_null($jsonOptions)) {
        // Json is wrong.
        $form_state->setErrorByName('Options', $this->t('JSON PARSE ERROR,please check your JSON'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $apiConfig = new Drupal8Config(
      $this->configFactory,
      $this->moduleHandler,
      $this->state
    );
    $apiClient = new ApiClient($apiConfig);
    switch ($form_state->getValue('verb')) {
      case "GET":
        $options = ['query' => Json::decode($form_state->getValue('Options'))];
        $result = $apiClient->getFromRocketChat(
          $form_state->getValue('method'),
          $options
        );
        break;

      case "POST":
        $options = ['json' => Json::decode($form_state->getValue('Options'))];
        $result = $apiClient->postToRocketChat(
          $form_state->getValue('method'),
          $options
        );
        break;

      default:
        $result = [
          "status" => "ILLEGAL ACTION",
          "body" => "NULL",
        ];
        break;

    }

    drupal_set_message(
      $result['status'] . ': ' .
      var_export($result['body'], TRUE)
    );
  }

}
