<?php

namespace Drupal\Tests\collect_crm\Kernel;

use Drupal\collect\Entity\Container;
use Drupal\collect\Entity\Model;
use Drupal\collect_crm\Plugin\collect\Processor\MailContactMatcher;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\inmail\Kernel\InmailTestHelperTrait;

/**
 * Tests the mail contact matcher processor plugin.
 *
 * @group collect_crm
 * @requires module inmail_test
 * @requires module past_db
 */
class MailContactMatcherTest extends KernelTestBase {

  use InmailTestHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'collect',
    'field',
    'hal',
    'rest',
    'user',
    'serialization',
    'inmail',
    'inmail_test',
    'inmail_collect',
    'name',
    'options',
    'datetime',
    'collect_common',
    'collect_crm',
    'crm_core_contact',
    'crm_core_match',
    'options',
    'datetime',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['collect_crm']);
    $this->installEntitySchema('collect_container');
    $this->installEntitySchema('crm_core_individual');
  }

  /**
   * Tests the features of a mail contact matcher.
   */
  public function testMailContactMatcher() {
    // Load inmail test mail from Nancy.
    $raw = $this->getMessageFileContents('addresses/simple-autoreply.eml');
    $container = Container::create([
      'data' => json_encode([
        'raw' => $raw,
      ]),
      'schema_uri' => 'https://www.drupal.org/project/inmail/schema/message',
      'type' => 'application/json',
    ]);

    // Create suggested model.
    /** @var \Drupal\collect\Model\ModelManagerInterface $model_manager */
    $model_manager = \Drupal::service('plugin.manager.collect.model');
    $model = $model_manager->suggestModel($container);
    Model::create([
      'id' => 'email_model',
      'label' => $model->label(),
      'plugin_id' => $model->getPluginId(),
      'uri_pattern' => $model->getUriPattern(),
      'properties' => $model->getProperties(),
    ])->save();

    /** @var \Drupal\collect\TypedData\TypedDataProvider $collect_type_data_provider */
    $collect_type_data_provider = \Drupal::service('collect.typed_data_provider');

    // Add a matcher configuration.
    $matcher_configuration = [
      'plugin_id' => 'contact_matcher_mail',
      'contact_type' => 'individual',
      'matcher' => 'inmail_individual',
      'fields' => ['name' => 'from'],
    ];

    // Create a new instance of a mail contact matcher.
    $mail_contact_matcher = new MailContactMatcher($matcher_configuration, 'contact_matcher_mail', NULL, \Drupal::logger('default'), $collect_type_data_provider, \Drupal::entityManager());
    $context = [];
    // Process the data of a container.
    $mail_contact_matcher->process($collect_type_data_provider->getTypedData($container), $context);
    $contact = reset($context['contacts']['default']);

    // Assert returned contact has a correct name.
    $this->assertEquals($contact->get('name')->given, 'Nancy');
  }

}
