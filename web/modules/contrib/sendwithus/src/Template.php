<?php

declare(strict_types = 1);

namespace Drupal\sendwithus;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Provides a context to store required template data.
 */
final class Template implements \IteratorAggregate {

  protected $templateId;
  protected $variables;

  /**
   * Constructs a new instance.
   *
   * @param string $templateId
   *   The template id.
   */
  public function __construct(string $templateId) {
    $this->templateId = $templateId;
    $this->variables = new ParameterBag([]);
  }

  /**
   * Gets the template id.
   *
   * @return string
   *   The template id.
   */
  public function getTemplateId() : string {
    return $this->templateId;
  }

  /**
   * Sets the template id.
   *
   * @param string $templateId
   *   The template id.
   *
   * @return \Drupal\sendwithus\Template
   *   The self.
   */
  public function setTemplateId(string $templateId) : self {
    $this->templateId = $templateId;
    return $this;
  }

  /**
   * Sets the variable.
   *
   * @param string $key
   *   The key.
   * @param mixed $value
   *   The value.
   *
   * @return \Drupal\sendwithus\Template
   *   The self.
   */
  public function setVariable(string $key, $value) : self {
    $this->variables->set($key, $value);
    return $this;
  }

  /**
   * Sets the template variable.
   *
   * @param string $key
   *   The key.
   * @param mixed $value
   *   The value.
   *
   * @return \Drupal\sendwithus\Template
   *   The self.
   */
  public function setTemplateVariable(string $key, $value) : self {
    $data = $this->getVariable('template_data', []);
    $data[$key] = $value;

    return $this->setVariable('template_data', $data);
  }

  /**
   * Gets the variable.
   *
   * @param string $key
   *   The variable key.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The variable or mixed.
   */
  public function getVariable(string $key, $default = NULL) {
    return $this->variables->get($key, $default);
  }

  /**
   * Gets the variables.
   *
   * @return \Symfony\Component\HttpFoundation\ParameterBag
   *   The parameter bag.
   */
  public function getVariables() : ParameterBag {
    return $this->variables;
  }

  /**
   * Converts variables to an array.
   *
   * @return array
   *   The array of variables.
   */
  public function toArray() : array {
    return iterator_to_array($this, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return $this->variables;
  }

}
