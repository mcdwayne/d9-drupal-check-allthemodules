<?php

namespace Drupal\inmail_collect\Plugin\collect\Model;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Model\ModelPluginBase;
use Drupal\collect\Model\PropertyDefinition;
use Drupal\collect\Model\SpecializedDisplayModelPluginInterface;
use Drupal\collect\TypedData\CollectDataInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\inmail\MIME\MimeParser;
use Drupal\inmail\MIME\MimeRenderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Model plugin for Inmail messages.
 *
 * @Model(
 *   id = "inmail_message",
 *   label = @Translation("Email message"),
 *   description = @Translation("Contains body and header fields of an email message."),
 *   patterns = {
 *     "https://www.drupal.org/project/inmail/schema/message"
 *   }
 * )
 */
class InmailMessage extends ModelPluginBase implements ContainerFactoryPluginInterface, SpecializedDisplayModelPluginInterface {

  /**
   * The injected MIME parser.
   *
   * @var \Drupal\inmail\MIME\MimeParser
   */
  protected $parser;

  /**
   * The injected MIME renderer.
   *
   * @var \Drupal\inmail\MIME\MimeRenderer
   */
  protected $renderer;

  /**
   * Constructs a new InmailMessage plugin instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MimeParser $parser, MimeRenderer $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->parser = $parser;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('inmail.mime_parser'),
      $container->get('inmail.mime_renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function parse(CollectContainerInterface $container) {
    $raw = json_decode($container->getData())->raw;
    return $this->parser->parseMessage($raw);
  }

  /**
   * {@inheritdoc}
   */
  public function build(CollectDataInterface $data) {
    return $this->renderer->renderEntity($data->getParsedData());
  }

  /**
   * {@inheritdoc}
   */
  public function buildTeaser(CollectDataInterface $data) {
    /** @var \Drupal\inmail\MIME\MimeMessageInterface $parsed_data */
    $parsed_data = $data->getParsedData();
    $output = parent::buildTeaser($data);

    $output['subject'] = array(
      '#type' => 'item',
      '#title' => $this->t('Subject'),
      '#markup' => htmlentities($parsed_data->getSubject()),
    );
    $output['from'] = array(
      '#type' => 'item',
      '#title' => $this->t('From'),
      '#markup' => htmlentities(implode(', ', $parsed_data->getFrom())),
    );
    // @todo Fix email display in https://www.drupal.org/node/2824683.
    $output['to'] = array(
      '#type' => 'item',
      '#title' => $this->t('To'),
      // By RFC 2822, addresses are comma-separated list.
      '#markup' => htmlentities(implode(', ', $parsed_data->getTo())),
    );

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public static function getStaticPropertyDefinitions() {
    $properties['body'] = new PropertyDefinition('body', DataDefinition::create('string')
      ->setLabel(t('Body')));

    $properties['subject'] = new PropertyDefinition('subject', DataDefinition::create('string')
      ->setLabel(t('Subject')));

    $properties['from'] = new PropertyDefinition('from', DataDefinition::create('inmail_mailbox')
      ->setLabel(t('From')));

    $properties['to'] = new PropertyDefinition('to', ListDataDefinition::create('inmail_mailbox')
      ->setLabel(t('To')));

    $properties['cc'] = new PropertyDefinition('cc', ListDataDefinition::create('inmail_mailbox')
      ->setLabel(t('Cc')));

    $properties['bcc'] = new PropertyDefinition('bcc', ListDataDefinition::create('inmail_mailbox')
      ->setLabel(t('Bcc')));

    $properties['_default_title'] = new PropertyDefinition('_default_title', DataDefinition::create('string')
      ->setLabel('Default title')
      ->setDescription('The default title of a container provided by applied model.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveQueryPath($data, array $path) {
    // @todo Define a query format. For To/From/Cc, allow it to specify name, address or both.
    $property_name = reset($path);
    /** @var \Drupal\inmail\MIME\MimeEntityInterface $message */
    $message = $data;

    if ($property_name == '_default_title') {
      return 'Mail: ' . $message->getHeader()->getFieldBody('subject');
    }

    if ($property_name == 'body') {
      // @todo Handle MultipartEntity, https://www.drupal.org/node/2450229
      return $message->getDecodedBody();
    }

    if (in_array($property_name, ['from', 'to', 'cc', 'bcc'])) {
      $field_body = $message->getHeader()->getFieldBody($property_name);
      // The returned value is an array of Rfc2822Address objects.
      $mailboxes = MimeParser::parseAddress($field_body);

      // This associative array with 'name' and 'address' elements is needed
      // for suitable inmail_mailbox data type reasons.
      $mailboxes_array = [];
      foreach ($mailboxes as $key => $mailbox) {
        $mailboxes_array[$key]['name'] = $mailbox->getName();
        $mailboxes_array[$key]['address'] = $mailbox->getAddress();
      }

      // Determine whether to return as single or as array.
      if (in_array($property_name, ['to', 'cc', 'bcc'])) {
        return $mailboxes_array;
      }
      return reset($mailboxes_array);
    }
    // Many property names are just header field names.
    return $message->getHeader()->getFieldBody($property_name);
  }

}
