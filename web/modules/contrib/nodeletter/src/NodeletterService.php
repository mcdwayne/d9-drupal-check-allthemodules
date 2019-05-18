<?php

/**
 * @file
 * Contains \Drupal\nodeletter\NodeletterService.
 */

namespace Drupal\nodeletter;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use \Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\nodeletter\Entity\NodeletterSending;
use Drupal\nodeletter\Entity\NodeTypeSettings;
use Drupal\nodeletter\NodeletterSender\NodeletterSenderManager;
use Drupal\nodeletter\NodeletterSender\NodeletterSenderPluginInterface;
use Drupal\nodeletter\NodeletterSender\NewsletterParameters;
use Drupal\nodeletter\Plugin\NodeletterSender\RenderedTemplateVariable;

class NodeletterService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * The nodeletter sender plugin manager.
   *
   * @var \Drupal\nodeletter\NodeletterSender\NodeletterSenderManager
   */
  protected $nodeletterSender;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The field formatter manager.
   *
   * @var FormatterPluginManager
   */
  protected $fieldFormatterManager;

  /**
   * Constructs a NodeletterService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_manager
   *   The field formatter manager.
   * @param \Drupal\nodeletter\NodeletterSender\NodeletterSenderManager $nodeletter_sender
   *   The nodeletter sender plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              EntityFieldManagerInterface $entity_field_manager,
                              FormatterPluginManager $formatter_manager,
                              NodeletterSenderManager $nodeletter_sender) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->fieldFormatterManager = $formatter_manager;
    $this->nodeletterSender = $nodeletter_sender;
  }


  /**
   * Check if nodeletter is enabled for a node type.
   *
   * @param $node_type string|\Drupal\node\Entity\NodeType
   * @return bool
   */
  public function nodeTypeEnabled($node_type ) {
    if (is_string($node_type)) {
      if (! $node_type_entity = NodeType::load($node_type)) {
        return FALSE;
      }
    } else {
      /** @var \Drupal\node\Entity\NodeType $node_type_entity */
      $node_type_entity = $node_type;
    }
    return $node_type_entity->getThirdPartySetting('nodeletter', 'enabled', FALSE);
  }

  /**
   * Get all node types which have nodeletter enabled.
   *
   * @return \Drupal\node\Entity\NodeType[] of NodeType config entities
   */
  public function getEnabledNodeTypes() {
    $all_node_types = NodeType::loadMultiple();
    $enabled_node_types = [];
    /** @var NodeType $node_type */
    foreach($all_node_types as $node_type) {
      if ($this->nodeTypeEnabled($node_type))
        $enabled_node_types[] = $node_type;
    }
    return $enabled_node_types;
  }


  /**
   * Get configured NodeletterSender plugin id for a content type.
   *
   * @param $node_type string|\Drupal\node\Entity\NodeType
   * @return string NodeletterSender plugin id.
   * @throws \InvalidArgumentException
   * @throws \Drupal\nodeletter\NodeletterNotEnabledException
   */
  public function getNodeletterSenderPluginId( $node_type ) {
    if (empty($node_type)) {
      throw new \InvalidArgumentException();
    } elseif (is_string($node_type)) {
      if (! $node_type_entity = NodeType::load($node_type)) {
        throw new \InvalidArgumentException();
      }
    } else {
      $node_type_entity = $node_type;
    }
    if ( ! $this->nodeTypeEnabled($node_type))
      throw new NodeletterNotEnabledException($node_type);

    $sender_id = $node_type_entity->getThirdPartySetting('nodeletter', 'service_provider', FALSE);
    if (!$sender_id)
      throw new NodeletterNotEnabledException($node_type);

    return $sender_id;
  }


  /**
   * Get configured NodeletterSender plugin for a content type.
   *
   * @param $node_type string|\Drupal\node\Entity\NodeType
   * @return \Drupal\nodeletter\NodeletterSender\NodeletterSenderPluginInterface
   * @throws \Drupal\nodeletter\NodeletterNotEnabledException
   */
  public function getNodeletterSender( $node_type ) {
    $sender_id = $this->getNodeletterSenderPluginId($node_type);
    return $this->getNodeletterSenderByPluginId($sender_id);
  }

  /**
   * Get a NodeletterSender plugin.
   *
   * @param $plugin_id string
   * @return \Drupal\nodeletter\NodeletterSender\NodeletterSenderPluginInterface
   * @throws \Drupal\nodeletter\NodeletterNotEnabledException
   */
  public function getNodeletterSenderByPluginId( $plugin_id ) {
    return $this->nodeletterSender->createInstance($plugin_id);
  }

  /**
   * Retrieve configuration entity for specific node type and service provider.
   *
   * Resulting NodeTypeSettings configuration entity may be newly created on
   * the fly if no former configuration entity is found in storage.
   *
   * @param \Drupal\node\Entity\NodeType|string $node_type
   * @param $sender string|\Drupal\nodeletter\NodeletterSender\NodeletterSenderPluginInterface
   *   (optional: if empty the default sender configured for the node type
   *   will be used)
   * @return NodeTypeSettings
   */
  public function getNodeletterSettings( $node_type, $sender=NULL ) {

    if (is_object($node_type) && $node_type instanceof NodeType) {
      $node_type = $node_type->id();
    } else if ( ! is_string($node_type)) {
      throw new \InvalidArgumentException(
        "node_type argument may either be an instance of NodeType or a string"
      );
    }
    if (empty($sender))
      $sender_id = $this->getNodeletterSenderPluginId($node_type);
    else if (is_string($sender))
      $sender_id = $sender;
    else if (is_object($sender)
      && $sender instanceof NodeletterSenderPluginInterface)
      $sender_id = $sender->id();
    else
      throw new \InvalidArgumentException(
        "service_provider argument may either be an instance of " .
        "NodeletterSenderPluginInterface or a string"
      );

    $settings_id = $node_type . '.' . $sender_id;
    $settings = NodeTypeSettings::load($settings_id);
    if ($settings === null) {
      $settings = NodeTypeSettings::create([
        'node_type' => $node_type,
        'service_provider' => $sender_id
      ]);
    }
    return $settings;
  }


  protected function getNewsletterParams( NodeInterface $node,
                                          array $recipient_selectors ) {

    $node_type_id = $node->getType();
    $settings = $this->getNodeletterSettings($node_type_id);

    $list_id = $settings->getListID();
    $template_id = $settings->getTemplateName();
    $template_vars = $this->renderTemplateVariables($node, $settings);
    $subject = $node->getTitle();

    $params = new NewsletterParameters($list_id, $subject, $template_id);
    $params->setTemplateVariables($template_vars);
    $params->setRecipientSelectors($recipient_selectors);

    return $params;
  }

  /**
   * @param NodeletterSenderPluginInterface $sender
   * @param string $mode
   * @param Node $node
   * @param NewsletterParameters $params
   * @return \Drupal\nodeletter\Entity\NodeletterSending
   */
  protected function createSendingEntity($sender, $mode, $node, $params) {

    $storage = \Drupal::entityTypeManager()->getStorage('nodeletter_sending');

    $selector_ids = [];
    foreach($params->getRecipientSelectors() as $selector) {
      $selector_ids[] = $selector->getId();
    }

    /** @var NodeletterSending $sending_entity */
    $sending_entity = $storage->create([
      'mode' => $mode,
      'node_id' => $node->id(),
      'node_changed' => $node->getChangedTime(),
      'service_provider' => $sender->id(),
      'list_id' => $params->getListId(),
      'recipient_selector_ids' => $selector_ids,
      'subject' => $params->getSubject(),
      'tpl_id' => $params->getTemplateId(),
      'sending_status' => SendingStatus::NOT_CREATED,
    ]);

    $sending_entity->addVariables($params->getTemplateVariables());

    // TODO: add $recipient_selectors to $sending_entity

    $sending_entity->save();

    return $sending_entity;
  }


  /**
   * @param \Drupal\node\NodeInterface $node
   * @param \Drupal\nodeletter\NodeletterSender\RecipientSelectorInterface[] $recipient_selectors
   * @return \Drupal\nodeletter\Entity\NodeletterSendingInterface
   * @throws \InvalidArgumentException
   * @throws \Drupal\nodeletter\NodeletterNotEnabledException
   */
  public function sendNewsletter(NodeInterface $node,
                                 array $recipient_selectors) {

    $node_type_id = $node->getType();
    $sender = $this->getNodeletterSender($node_type_id);
    $params = $this->getNewsletterParams($node, $recipient_selectors);

    $sending_entity = $this->createSendingEntity($sender, 'real', $node, $params);

    try {

      // ACTION !
      $sending_id = $sender->send($params);

      $sending_entity->setSendingId($sending_id);
      $sending_entity->setSendingStatus(SendingStatus::SCHEDULED);

    } catch (NodeletterSendException $e) {
      $sending_entity->setSendingStatus(SendingStatus::FAILED);
      $sending_entity->setErrorCode($e->getCode());
      $sending_entity->setErrorMessage($e->getMessage());

    } catch (\Exception $e) {
      $sending_entity->setSendingStatus(SendingStatus::FAILED);
      $sending_entity->setErrorCode(NodeletterSendException::CODE_UNDEFINED_ERROR);
      $sending_entity->setErrorMessage($e->getMessage());

    } finally {
      $sending_entity->save();
    }


    $node_url = Url::fromRoute(
      'entity.node.canonical',
      ['node' => $node->id()]
    )->toString();

    if ($sending_entity->getSendingStatus() == SendingStatus::FAILED) {
      \Drupal::logger('nodeletter')->error(
        'Sending @id for <a href="{node_url}">node %node_title</a> failed: ' .
        '@error. Error message: @message',
        [
          '@id' => $sending_entity->id(),
          '%node_title' => $node->getTitle(),
          'node_url' => $node_url,
          '@error' => NodeletterSendException::describe($sending_entity->getErrorCode()),
          '@message' => $sending_entity->getErrorMessage(),
        ]
      );
    } else {
      \Drupal::logger('nodeletter')->notice(
        'Sending @id for <a href="{node_url}">node %node_title</a> ' .
        'scheduled for immediate delivery',
        [
          '@id' => $sending_entity->id(),
          '%node_title' => $node->getTitle(),
          'node_url' => $node_url,
        ]
      );
    }

    return $sending_entity;
  }


  public function sendTest(NodeInterface $node, $recipient) {


    $node_type_id = $node->getType();
    $sender = $this->getNodeletterSender($node_type_id);
    $params = $this->getNewsletterParams($node, []);

    $sending_entity = $this->createSendingEntity($sender, 'test', $node, $params);
    $sending_entity->setTestRecipient($recipient);

    try {

      // ACTION !
      $sending_id = $sender->sendTest($recipient, $params);

      $sending_entity->setSendingId($sending_id);
      $sending_entity->setSendingStatus(SendingStatus::SENT);

    } catch (NodeletterSendException $e) {
      $sending_entity->setSendingStatus(SendingStatus::FAILED);
      $sending_entity->setErrorCode($e->getCode());
      $sending_entity->setErrorMessage($e->getMessage());

    } catch (\Exception $e) {
      $sending_entity->setSendingStatus(SendingStatus::FAILED);
      $sending_entity->setErrorCode(NodeletterSendException::CODE_UNDEFINED_ERROR);
      $sending_entity->setErrorMessage($e->getMessage());

    } finally {
      $sending_entity->save();
    }


    $node_url = Url::fromRoute(
      'entity.node.canonical',
      ['node' => $node->id()]
    )->toString();

    if ($sending_entity->getSendingStatus() == SendingStatus::FAILED) {
      \Drupal::logger('nodeletter')->error(
        'Test-Sending @id for <a href="{node_url}">node %node_title</a> failed: ' .
        '@error. Error message: @message',
        [
          '@id' => $sending_entity->id(),
          '%node_title' => $node->getTitle(),
          'node_url' => $node_url,
          '@error' => NodeletterSendException::describe($sending_entity->getErrorCode()),
          '@message' => $sending_entity->getErrorMessage(),
        ]
      );
    } else {
      \Drupal::logger('nodeletter')->notice(
        'Test-Sending @id for <a href="{node_url}">node %node_title</a> ' .
        'scheduled for immediate delivery',
        [
          '@id' => $sending_entity->id(),
          '%node_title' => $node->getTitle(),
          'node_url' => $node_url,
        ]
      );
    }

    return $sending_entity;

  }



  public function renderTemplateVariables( NodeInterface $node, NodeTypeSettings $settings ) {

    $tpl_vars = $settings->getTemplateVariables();

    $rendered_vars = [];

    foreach($tpl_vars as $tpl_var) {

      $field_name = $tpl_var->getField();

      if ( ! $node->hasField($field_name)) {
        continue;
      }

      $field_definition = $node->getFieldDefinition($field_name);
      $field = $node->get($field_name);

      $formatter_id = $tpl_var->getFormatter();
      $formatter_settings = $tpl_var->getFormatterSettings();

      $formatter = $this->fieldFormatterManager->getInstance([
        'field_definition' => $field_definition,
        'view_mode' => 'nodeletter',
        'configuration' => [
          'label' => 'hidden',
          'type' => $formatter_id,
          'settings' => is_array($formatter_settings) ? $formatter_settings : [],
        ],
      ]);

      $formatter->prepareView(array($node->id() => $field));
      $field_view = $formatter->view($field);

      $output = \Drupal::service('renderer')->renderPlain($field_view);

      $rendered_var = new RenderedTemplateVariable(
        $tpl_var->getVariableName(),
        $output
      );
      $rendered_vars[ $tpl_var->getVariableName() ] = $rendered_var;
    }

    return $rendered_vars;

  }

}
