<?php

namespace Drupal\inmail;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\user\UserInterface;

/**
 * Contains default analyzer result.
 *
 * @ingroup analyzer
 */
class DefaultAnalyzerResult implements AnalyzerResultInterface {

  /**
   * Identifies this class in relation to other analyzer results.
   *
   * Use this as the $topic argument for ProcessorResultInterface methods.
   *
   * @see \Drupal\inmail\ProcessorResultInterface
   */
  const TOPIC = 'default';

  /**
   * An array of collected contexts for this analyzer result.
   *
   * It contains information provided by analyzers that are
   * not part of default properties.
   *
   * @var \Drupal\Core\Plugin\Context\ContextInterface[]
   */
  protected $contexts = [];

  /**
   * The sender.
   *
   * @var string
   */
  protected $sender;

  /**
   * The account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * The analyzed body of the message.
   *
   * @var string
   */
  protected $body;

  /**
   * The message footer
   *
   * @var string
   */
  protected $footer;

  /**
   * The message subject.
   *
   * @var string
   */
  protected $subject;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return t('Default Result');
  }

  /**
   * Returns a function closure that in turn returns a new class instance.
   *
   * @return callable
   *   A factory closure that returns a new DefaultAnalyzerResult object
   *   when called.
   */
  public static function createFactory() {
    return function() {
      return new static();
    };
  }

  /**
   * Sets the sender mail address.
   *
   * @param string $sender
   *   The address of the sender.
   */
  public function setSender($sender) {
    $this->sender = $sender;
  }

  /**
   * Returns the sender of the message.
   *
   * @return string|null
   *   The address of the sender, or NULL if it is not found.
   */
  public function getSender() {
    return $this->sender;
  }

  /**
   * Sets the account.
   *
   * @param \Drupal\user\UserInterface $account
   *   The new user.
   */
  public function setAccount(UserInterface $account) {
    $this->account = $account;
  }

  /**
   * Returns a user object.
   *
   * @return \Drupal\user\UserInterface
   *   The user object.
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * Determines if the user is authenticated.
   *
   * @return bool
   *   TRUE if user is authenticated. Otherwise, FALSE.
   */
  public function isUserAuthenticated() {
    return $this->account ? $this->account->isAuthenticated() : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function summarize() {
    $summary = [];
    if ($this->getSender()) {
      $summary['sender'] = $this->getSender();
    }
    if ($this->getSubject()) {
      $summary['subject'] = $this->getSubject();
    }
    if ($this->getAllContexts()) {
      $summary['contexts'] = t('The result contains @contexts contexts.', ['@contexts' => implode(', ', array_keys($this->getAllContexts()))]);
    }

    return $summary;
  }

  /**
   * Returns the analyzed body of the message.
   *
   * @return string
   *   The analyzed body of the message.
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * Sets the analyzed message body.
   *
   * @param string $body
   *   The analyzed body of the message.
   */
  public function setBody($body) {
    $this->body = $body;
  }

  /**
   * Returns the message footer.
   *
   * @return string
   *   The footer of the message.
   */
  public function getFooter() {
    return $this->footer;
  }

  /**
   * Sets the message footer.
   *
   * @param string $footer
   *   The message footer.
   */
  public function setFooter($footer) {
    $this->footer = $footer;
  }

  /**
   * Returns the analyzed message subject.
   *
   * @return string
   *   The message subject.
   */
  public function getSubject() {
    return $this->subject;
  }

  /**
   * Sets the actual message subject.
   *
   * @param string $subject
   *   The analyzed message subject.
   */
  public function setSubject($subject) {
    $this->subject = $subject;
  }

  /**
   * Sets the condition context for a given name.
   *
   * @param string $name
   *   The name of the context.
   * @param \Drupal\Core\Plugin\Context\ContextInterface $context
   *   The context to set.
   */
  public function setContext($name, ContextInterface $context) {
    $this->contexts[$name] = $context;
  }

  /**
   * Gets an array of all collected contexts for this analyzer result.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   An array of set contexts, keyed by context name.
   */
  public function getAllContexts() {
    return $this->contexts;
  }

  /**
   * Returns whether context exists.
   *
   * @param string $name
   *   The name of the context.
   *
   * @return bool
   *   TRUE if the context exists. Otherwise, FALSE.
   */
  public function hasContext($name) {
    return isset($this->contexts[$name]);
  }

  /**
   * Gets the specific context from the list of available contexts.
   *
   * @param string $name
   *   The name of the context to return.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface
   *   Requested context object or NULL if not found.
   *
   * @throws \InvalidArgumentException
   *   Throws an exception if requested context does not exist.
   */
  public function getContext($name) {
    if (!isset($this->contexts[$name])) {
      throw new \InvalidArgumentException('Context "' . $name . '" does not exist.');
    }

    return $this->contexts[$name];
  }

  /**
   * Returns the contexts of the given type.
   *
   * @param string $type
   *   The context type.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   Contexts array of the given data type, keyed by context name.
   */
  public function getContextsWithType($type) {
    $filtered_contexts = [];

    foreach ($this->contexts as $context_name => $context) {
      if ($context->getContextDefinition()->getDataType() == $type) {
        $filtered_contexts[$context_name] = $context;
      }
    }

    return $filtered_contexts;
  }

  /**
   * Ensures there is a context of the given data type and provides its data.
   *
   * @param string $name
   *   The name of the context.
   * @param string $data_type
   *   The context data type.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The typed data of given context.
   *
   * @throws \InvalidArgumentException|\Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Returns an exception in case of invalid data type.
   */
  public function ensureContext($name, $data_type) {
    if ($this->hasContext($name)) {
      $context_data_type = $this->getContext($name)->getContextData()->getDataDefinition()->getDataType();
      if ($data_type != $context_data_type) {
        throw new \InvalidArgumentException('Invalid data type ' . $data_type . ' has been given.');
      }
      $data = $this->getContext($name)->getContextData();
    }
    else {
      /** @var \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager */
      $typed_data_manager = \Drupal::service('typed_data_manager');
      $data_definition = $typed_data_manager->createDataDefinition($data_type);
      $configuration = [
        'data_definition' => $data_definition,
        'name' => NULL,
        'parent' => NULL,
      ];
      $data = $typed_data_manager->createInstance($data_type, $configuration);
      $context = new Context(new ContextDefinition($data_type), $data);
      $this->setContext($name, $context);
    }

    return $data;
  }

}
