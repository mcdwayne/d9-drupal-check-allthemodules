<?php


namespace Drupal\nodeletter\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Configuration entity that specifies nodeletter settings per node types.
 *
 * @ConfigEntityType(
 *   id = "nodeletter_node_type",
 *   label = @Translation("Nodeletter Configuration"),
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id"
 *   },
 *   config_prefix = "configuration",
 *   config_export = {
 *     "id",
 *     "node_type",
 *     "service_provider",
 *     "template_name",
 *     "template_variables",
 *     "list_id",
 *   }
 * )
 */
class NodeTypeSettings extends ConfigEntityBase {


  protected $id;

  /**
   * The service provider name.
   *
   * This is the name of the property under which the field values are placed in
   * an entity: $entity->{$field_name}. The maximum length is
   * Field:NAME_MAX_LENGTH.
   *
   * Example: mailchimp, sendgrid.
   *
   * @var string
   */
  protected $service_provider;

  /**
   * The name of the node type the nodeletter settings apply to.
   *
   * @var string
   */
  protected $node_type;

  /**
   * Template name.
   * @TODO: rename "template_name" to "template_id" (see new NewsletterTemplateInterface)
   *
   * @var string
   */
  protected $template_name;

  /**
   * Template variable settings.
   *
   * An array describing template variables as defined in schema.yml.
   *
   * @see NodeTypeSettings::preSave()
   * @var array
   */
  protected $template_variables = [];

  /**
   * Recipient List ID.
   *
   * @var string
   */
  protected $list_id;

  /**
   * @see NodeTypeSettings::preSave()
   * @var TemplateVariableSetting[]
   */
  protected $_tpl_var_settings = [];


  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    if (!isset($values['node_type']) || !isset($values['service_provider'])) {
      throw new \InvalidArgumentException('Missing required properties for an Nodeletter configuration entity.');
    }
    parent::__construct($values, $entity_type);

    if (empty($this->id)) {
      $this->id = $this->id();
    }
    if (!empty($this->template_variables)) {
      foreach($this->template_variables as $tpl_var_array)
        $this->addTemplateVariable(TemplateVariableSetting::fromArray($tpl_var_array));
    }
  }

  /**
   * The nodeletter settings ID.
   *
   * The ID consists of 2 parts: the node type and the nodeletter service provider.
   *
   * Example: article.mailchimp, book.sendgrid.
   *
   * @throws \Exception
   * @return string
   */
  public function id() {
    if (empty($this->node_type) || empty($this->service_provider))
      throw new \Exception("ID for nodletter settings can not be calculated");
    return $this->node_type . '.' . $this->service_provider;

  }

  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    $this->template_variables = [];
    foreach($this->_tpl_var_settings as $tpl_var_setting)
      $this->template_variables[] = $tpl_var_setting->toArray();
  }


  public function getNodeTypeId() {
    return $this->node_type;
  }

  public function getServiceProvider() {
    return $this->service_provider;
  }

  public function getTemplateName() {
    return $this->template_name;
  }
  public function setTemplateName( $name ) {
    $this->template_name = $name;
    return $this;
  }

  public function getTemplateVariables() {
    return $this->_tpl_var_settings;
  }

  /**
   * @param TemplateVariableSetting[] $template_variables
   * @return $this
   */
  public function setTemplateVariables( array $template_variables ) {
    $this->_tpl_var_settings = $template_variables;
    return $this;
  }

  public function getTemplateVariablesMaxWeight() {
    if (empty($this->_tpl_var_settings))
      return 0;

    $max_weight = NULL;
    foreach($this->_tpl_var_settings as $tpl_var) {
      if (is_null($max_weight))
        $max_weight = $tpl_var->getWeight();
      else if ($tpl_var->getWeight() > $max_weight)
        $max_weight = $tpl_var->getWeight();
    }
    return $max_weight;
  }

  public function getTemplateVariable( $variable_name ) {
    foreach($this->_tpl_var_settings as $tpl_var) {
      if ($tpl_var->getVariableName() == $variable_name)
        return $tpl_var;
    }
    return NULL;
  }

  public function addTemplateVariable( TemplateVariableSetting $tpl_var ) {
    if ( ! $this->getTemplateVariable( $tpl_var->getVariableName() )) {
      $this->_tpl_var_settings[] = $tpl_var;
    }
    return $this;
  }

  public function removeTemplateVariable( $variable_name ) {
    foreach($this->_tpl_var_settings as $i => $tpl_var) {
      if ($tpl_var->getVariableName() == $variable_name) {
        unset($this->_tpl_var_settings[$i]);
        $this->_tpl_var_settings = array_values($this->template_variables);
      }
    }
    return $this;
  }

  public function getListID() {
    return $this->list_id;
  }
  public function setListID( $id ) {
    $this->list_id = $id;
    return $this;
  }
}

