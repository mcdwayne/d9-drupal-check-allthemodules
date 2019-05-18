<?php


/**
 * @file
 * Contains \Drupal\nodeletter\Entity\NodeletterSending.
 */

namespace Drupal\nodeletter\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\node\Entity\Node;
use Drupal\nodeletter\Plugin\NodeletterSender\RenderedTemplateVariable;
use Drupal\nodeletter\SendingStatus;
use Drupal\user\UserInterface;

/**
 * Defines the Contact entity.
 *
 *
 * @ContentEntityType(
 *   id = "nodeletter_sending",
 *   label = @Translation("Nodeletter sending"),
 *   base_table = "nodeletter_sending",
 *   admin_permission = "administer nodeletter_sending entity",
 *   fieldable = TRUE,
 *   handlers = {
 *     "view_builder" = "Drupal\nodeletter\Controller\NodeletterSendingViewBuilder",
 *     "list_builder" = "Drupal\nodeletter\Controller\NodeletterSendingListBuilder",
 *     "access" = "Drupal\nodeletter\NodeletterSendingAccessControlHandler",
 *     "form" = {
 *       "delete" = "Drupal\nodeletter\Form\NodeletterSendingDeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/nodeletter/sendings/{nodeletter_sending}",
 *     "delete-form" = "/admin/nodeletter/sendings/{nodeletter_sending}/delete"
 *   },
 * )
 *
 */
class NodeletterSending extends ContentEntityBase implements
  NodeletterSendingInterface {

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller,
                                   array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  public function label() {
    return "Nodeletter Sending Label [ " . $this->get('id')->value . "]";
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTime($timestamp) {
    $this->set('changed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTimeAcrossTranslations() {
    $changed = $this->getUntranslated()->getChangedTime();
    foreach ($this->getTranslationLanguages(FALSE) as $language) {
      $translation_changed = $this->getTranslation($language->getId())
        ->getChangedTime();
      $changed = max($translation_changed, $changed);
    }
    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeId() {
    return $this->get('node_id')->value;
  }

  /**
   * @return Node|null
   */
  public function getNode() {
    return Node::load($this->getNodeId());
  }

  /**
   * {@inheritdoc}
   *
   * @param integer $node_id
   */
  public function setNodeId($node_id) {
    $this->set('node_id', $node_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getComment() {
    return $this->get('comment')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setComment($comment) {
    $this->set('comment', $comment);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject() {
    return $this->get('subject')->value;
  }

  /**
   * {@inheritdoc}
   *
   * @param string $subject
   */
  public function setSubject($subject) {
    $this->set('subject', $subject);
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public function getServiceProvider() {
    return $this->get('service_provider')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMode() {
    return $this->get('mode')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestRecipient() {
    return $this->get('test_recipient')->value;
  }

  public function setTestRecipient($recipient) {
    $this->set('test_recipient', $recipient);
    return $this;
  }

  /**
   * {@inheritdoc}
   * @return RenderedTemplateVariable[]
   */
  public function getVariables() {
    $vars = [];
    foreach ($this->get('tpl_vars') as $delta => $item) {
      $vars[] = new RenderedTemplateVariable($item->name, $item->value);
    }
    return $vars;
  }

  /**
   *
   * @param string $var_name
   * @return integer Delta of field item of -1 if not found.
   */
  public function getVariableDelta($var_name) {
    $vars = $this->get('tpl_vars')->getValue();
    foreach ($vars as $delta => $var) {
      if ($var['name'] == $var_name) {
        return $delta;
      }
    }
    return -1;
  }

  /**
   *
   * @param string $var_name
   * @return boolean
   */
  public function hasVariable($var_name) {
    return $this->getVariableDelta($var_name) != -1;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariable($var_name) {
    foreach ($this->get('tpl_vars') as $item) {
      if ($item->name == $var_name) {
        return new RenderedTemplateVariable($item->name, $item->value);
      }
    }
    throw new \Exception("Sending variable \"$var_name\" not found");
  }

  /**
   * {@inheritdoc}
   */
  public function addVariable(RenderedTemplateVariable $variable) {
    if ($this->hasVariable($variable->getName())) {
      throw new \Exception("Sending variable \"{$variable->getName()}\" " .
        "already exists");
    }
    $this->setVariable($variable->getName(), $variable->getValue());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addVariables(array $variables) {
    foreach ($variables as $variable) {
      $this->addVariable($variable);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeVariable(RenderedTemplateVariable $variable) {
    $delta = $this->getVariableDelta($variable->getName());
    if ($delta == -1) {
      throw new \Exception("Sending variable \"{$variable->getName()}\" not " .
        "found");
    }
    $this->get('tpl_vars')->offsetUnset($delta);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function clearVariables() {
    $this->set('tpl_vars', []);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setVariable($var_name, $rendered_value) {
    $item_value = [
      'name' => $var_name,
      'value' => $rendered_value
    ];
    $existing_delta = $this->getVariableDelta($var_name);
    if ($existing_delta > -1) {
      $this->get('tpl_vars')->offsetSet($existing_delta, $item_value);
    }
    else {
      $this->get('tpl_vars')->appendItem($item_value);
    }
    return $this;
  }

  public function getListId() {
    return $this->get('list_id')->value;
  }

  public function getRecipientSelectorIds() {
    return $this->get('recipient_selector_ids');
  }

  public function getSendingId() {
    return $this->get('sending_id')->value;
  }

  public function setSendingId($id) {
    $this->set('sending_id', $id);
    return $this;
  }

  public function getSendingStatus() {
    return $this->get('sending_status')->value;
  }

  public function setSendingStatus($sending_status) {
    $def = $this->getFieldDefinition('sending_status');
    $allowed_values = array_keys($def->getSetting('allowed_values'));
    if (!in_array($sending_status, $allowed_values)) {
      throw new \InvalidArgumentException("Invalid sending status");
    }
    $this->set('sending_status', $sending_status);
    return $this;
  }

  public function getErrorCode() {
    return $this->get('error_code')->value;
  }

  public function setErrorCode($code) {
    $this->set('error_code', $code);
    return $this;
  }

  public function getErrorMessage() {
    return $this->get('error_message')->value;
  }

  public function setErrorMessage($message) {
    $this->set('error_message', $message);
    return $this;
  }


  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(
    EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Contact entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Contact entity.'))
      ->setReadOnly(TRUE);

    $fields['node_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Node ID'))
      ->setDescription(t('ID of node this nodeletter sending is based on.'));

    $fields['node_changed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Node changed'))
      ->setDescription(t('Nodes changed timestamp at the moment this sending ' .
        'was created.'));

    $fields['mode'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Sendig mode'))
      ->setDescription(t('Wheter this sending was a real newsletter sending ' .
        'or just a test sending.'))
      ->setSettings(array(
        'allowed_values' => array(
          'real' => t('Real Sending'),
          'test' => t('Test Sending'),
        ),
      ))
      ->setDefaultValue('real');

    $fields['test_recipient'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Recipient of test sending'))
      ->setDescription(t('Recipient address of a sending in mode "test".'))
      ->setSettings(array(
        'max_length' => 255,
      ));

    $fields['comment'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Comment'));

    $fields['service_provider'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nodeletter service provider'))
      ->setDescription(t('Service Provider this sending was handled by.'))
      ->setSettings(array(
        'max_length' => 255,
      ));

    $fields['list_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Recipient list ID'))
      ->setDescription(t('ID of the recipient list used for this sending.'))
      ->setSettings(array(
        'max_length' => 255,
      ));

    $fields['recipient_selector_ids'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Recipient list ID'))
      ->setDescription(t('The gender of the Contact entity.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSettings(array(
        'max_length' => 255,
      ));

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Newsletter Subject'))
      ->setDescription(t('E-mail subject line used for this sending.'))
      ->setSettings(array(
        'max_length' => 255,
      ));

    $fields['tpl_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Newsletter template ID'))
      ->setDescription(t('ID of serivce providers newsletter template used ' .
        'for this sending.'))
      ->setSettings(array(
        'max_length' => 255,
      ));

    $fields['tpl_vars'] = BaseFieldDefinition::create(
      'nodeletter_sending_variable')
      ->setLabel(t('Template variables'))
      ->setDescription(t('T.B.D.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of the newsletter.'));

    $fields['sending_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sending ID'))
      ->setDescription(t('ID of sending as defined by serivce provider'))
      ->setSettings(array(
        'max_length' => 255,
      ));

    $fields['sending_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Sending ID'))
      ->setDescription(t('ID of sending as defined by serivce provider'))
      ->setSettings(array(
        'allowed_values' => array(
          SendingStatus::NOT_CREATED =>
            t('Not yet pushed to sender service provider'),
          SendingStatus::CREATED =>
            t('Sender service provider created sending'),
          SendingStatus::SCHEDULED =>
            t('Scheduled for sending out'),
          SendingStatus::SENDING =>
            t('Sender service provider is sending'),
          SendingStatus::PAUSED =>
            t('Paused by sender service provider'),
          SendingStatus::SENT =>
            t('Sender service provider completed sending'),
          SendingStatus::FAILED =>
            t('Sending failed')
        ),
      ))
      ->setDefaultValue('not created')
      ->setRequired(TRUE);

    $fields['error_code'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Error Code'))
      ->setDescription(t('Type of error if sending failed'));

    $fields['error_message'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Error Message'))
      ->setDescription(t('Error message if sending failed'));

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User Name'))
      ->setDescription(t('The Name of the associated user.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }
}
