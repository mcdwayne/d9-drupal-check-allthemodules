<?php

namespace Drupal\form_delegate;

use Drupal\Core\Entity\EntityTypeManager as OriginalEntityTypeManager;

/**
 * Replacement of the original entity type manager.
 *
 * @package Drupal\form_delegate
 */
class EntityTypeManager extends OriginalEntityTypeManager {

  /**
   * {@inheritdoc}
   */
  public function getFormObject($entityType, $operation) {
    $originalClass = $this->getDefinition($entityType)->getFormClass($operation);
    $delegateClass = $this->createFormDelegateClass($originalClass);

    $form_object = $this->classResolver->getInstanceFromDefinition($delegateClass);

    $operationMode = $this->container->get('current_route_match')
      ->getRouteObject()->getDefault('_entity_form_operation') ?: $operation;

    return $form_object
      ->setDelegateManager(\Drupal::service('plugin.manager.form_delegate'))
      ->setStringTranslation($this->stringTranslation)
      ->setModuleHandler($this->moduleHandler)
      ->setEntityTypeManager($this)
      ->setOperation($operationMode)
      ->setEntityManager(\Drupal::entityManager());
  }

  /**
   * Creates dynamically the form delegation class for a specific entity.
   *
   * @param string $originalClass
   *   The original class namespace and name.
   *
   * @return string
   *   The created class namespace and name.
   */
  public function createFormDelegateClass($originalClass) {
    $lastSeparator = strrpos($originalClass, '\\');
    $originalClassNamespace = substr($originalClass, 0, $lastSeparator);
    $extendedClassName = substr($originalClass, $lastSeparator + 1) . 'Delegating';
    $extendedClass = "$originalClassNamespace\\$extendedClassName";

    // Prevent duplicate creation and unnecessary reflection if the class was
    // already created.
    if (class_exists($extendedClass)) {
      return $extendedClass;
    }

    $reflection = new \ReflectionMethod($originalClass, 'buildForm');
    $extension = "";

    // Form build is allowed to have more than the interface arguments. The
    // form controller determines the arguments with the controller resolver
    // from the request. In these cases we can't use the trait implemented
    // build form. We will recreate the method to use the right arguments.
    if (($paramCount = $reflection->getNumberOfParameters()) > 2) {
      $parameters = $reflection->getParameters();
      $paramDeclarations = "";
      $paramNames = "";

      // Get all additional parameters with type and default value.
      for ($i = 2; $i < $paramCount; ++$i) {
        $paramNames .= ', $' . $parameters[$i]->getName();
        $paramDeclarations .= ', ' . $parameters[$i]->getType() . ' $' . $parameters[$i]->getName();

        if ($parameters[$i]->isDefaultValueAvailable()) {
          if ($parameters[$i]->isDefaultValueConstant()) {
            $paramDeclarations .= ' = ' . $parameters[$i]->getDefaultValueConstantName();
          }
          else {
            $values = [NULL => 'NULL', FALSE => 'FALSE', TRUE => 'TRUE'];
            $default = $parameters[$i]->getDefaultValue();
            $paramDeclarations .= ' = ' . (isset($values[$default]) ? $values[$default] : $default);
          }
        }
      }

      // Create the replacement method string.
      $extension = "public function buildForm(array \$form, \Drupal\Core\Form\FormStateInterface \$form_state$paramDeclarations) {
        return call_user_func_array('parent::buildForm', [\$form, \$form_state$paramNames]);
        \$this->delegateFormMethod('buildForm', \$form, \$form_state);
        return \$form;
      }";
    }

    // This is the hackiest thing you will see. We create a class dynamically
    // that extends the original entity form (so that we keep original
    // functionality), but that implements our trait which will replace the
    // form methods and delegate them to the plugins.
    eval("namespace $originalClassNamespace {
      class $extendedClassName extends \\$originalClass {
        use \\Drupal\\form_delegate\\Form\\EntityFormDelegationTrait;
        $extension
      }
    }");

    return $extendedClass;
  }

}
