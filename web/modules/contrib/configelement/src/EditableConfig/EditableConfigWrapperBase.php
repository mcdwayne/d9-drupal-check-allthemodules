<?php

namespace Drupal\configelement\EditableConfig;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Schema\ArrayElement;
use Drupal\Core\Config\Schema\Mapping;
use Drupal\Core\Config\Schema\TypedConfigInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;

abstract class EditableConfigWrapperBase implements EditableConfigWrapperInterface {

  /** @var \Drupal\Core\Config\Config | \Drupal\language\Config\LanguageConfigOverride */
  protected $config;

  /** @var \Drupal\Core\Config\TypedConfigManagerInterface */
  protected $typedConfigManager;

  /**
   * EditableConfigWrapper constructor.
   *
   * @internal Use EditableConfigItemFactory::get
   *
   * @param \Drupal\Core\Config\Config | \Drupal\language\Config\LanguageConfigOverride $config
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   */
  public function __construct($config, TypedConfigManagerInterface $typedConfigManager) {
    $this->config = $config;
    $this->typedConfigManager = $typedConfigManager;
    $this->populateMappings();
  }

  /**
   * Get value.
   *
   * @param string $key
   *
   * @return array|mixed|null
   */
  public function get($key) {
    return $this->config->get($key);
  }

  /**
   * Set value.
   *
   * @param string $key
   *   The key.
   * @param mixed $value
   *   The value.
   */
  public function set($key, $value) {
    $this->config->set($key, $value);
  }

  /**
   * @inheritDoc
   */
  public function has($key) {
    try {
      $this->getSchemaWrapper()->get($key);
    } catch (\InvalidArgumentException $e) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Trigger autosave.
   *
   * @internal Use EditableConfigItemFactory::triggerAutosave
   */
  public function save() {
    $this->unpopulateMappings();
    if ($this->config->get() !== $this->getOriginalData()) {
      $this->config->save();
    }
    $this->populateMappings();
  }

  abstract protected function getOriginalData();

  /**
   * Get schema wrapper / typed data of the wrapped config.
   *
   * @see \Drupal\Core\Config\StorableConfigBase::getSchemaWrapper (protected)
   * @todo Upstream: That function should be public.
   *
   * @return \Drupal\Core\Config\Schema\TypedConfigInterface
   *
   */
  public function getSchemaWrapper($propertyPath = '') {
    if ($propertyPath) {
      $parents = explode('.', $propertyPath);
      if ($parents === ['']) {
        $parents = [];
      }
      if (!NestedArray::keyExists($this->getConfigData(), $parents)) {
        $savedConfigData = $this->getConfigData();
        $this->config->set($propertyPath, '');
      }
    }
    $schemaWrapper = $this->typedConfigManager->createFromNameAndData($this->config->getName(), $this->getConfigData());
    if (!$schemaWrapper instanceof TypedConfigInterface) {
      throw new \InvalidArgumentException(sprintf('Config %s does not have a schema.', $this->config->getName()));
    }
    if ($propertyPath) {
      $schemaWrapper = $schemaWrapper->get($propertyPath);
      if (isset($savedConfigData)) {
        $this->config->setData($savedConfigData);
      }
    }
    return $schemaWrapper;
  }

  /**
   * @return array
   */
  abstract protected function getConfigData();


  /**
   * Validate config.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   */
  public function validate() {
    return $this->getSchemaWrapper()->validate();
  }

  private function populateMappings($propertyPath = '') {
    $parents = explode('.', $propertyPath);
    if ($parents === ['']) {
      $parents = [];
    }
    $schemaWrapper = $this->getSchemaWrapper($propertyPath);
    if ($schemaWrapper instanceof ArrayElement) {
      if ($schemaWrapper instanceof Mapping) {
        $definition = $schemaWrapper->getDataDefinition();
        foreach ($definition['mapping'] as $key => $itemDefinition) {
          $itemParents = array_merge($parents, [$key]);
          if (!NestedArray::keyExists($this->getConfigData(), $itemParents)) {
            $this->populateMappings(implode('.', $itemParents));
          }
        }
      }
      else {
        foreach ($schemaWrapper->getElements() as $key => $element) {
          $this->populateMappings("$propertyPath.$key");
        }
      }
    }
    else {
      $definition = $schemaWrapper->getDataDefinition();
      if (
        !NestedArray::keyExists($this->getConfigData(), $parents)
        && $this->relevantMappingDefinition($definition)
      ) {
        $this->config->set($propertyPath, '');
      }
    }
  }

  private function unpopulateMappings($propertyPath = '') {
    $parents = explode('.', $propertyPath);
    if ($parents === ['']) {
      $parents = [];
    }
    $schemaWrapper = $this->getSchemaWrapper($propertyPath);
    if ($schemaWrapper instanceof ArrayElement) {
      foreach ($schemaWrapper->getElements() as $key => $element) {
        $itemParents = array_merge($parents, [$key]);
        $this->unpopulateMappings(implode('.', $itemParents));
      }
    }
    else {
      $data = $this->getConfigData();
      if (is_null(NestedArray::getValue($data, $parents))) {
        NestedArray::unsetValue($data, $parents);
        $this->config->setData($data);
      }
    }
  }

  protected function relevantMappingDefinition($definition) {
    return empty($definition['internal']) && empty($definition['computed']);
  }

}
