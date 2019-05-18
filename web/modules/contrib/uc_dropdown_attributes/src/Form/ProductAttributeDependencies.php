<?php

namespace Drupal\uc_dropdown_attributes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Administrative form for specifying the product attribute dependencies.
 */
class ProductAttributeDependencies extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_dropdown_attributes_product';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['uc_dropdown_attributes.product.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $form['intro'] = array(
      '#markup' => '<p>' . t('Since drop down attributes may not appear, they cannot be always required.  The required checkbox applies only when the dropdown attribute appears.  Any dropdown attribute is also checked under the attributes table to make sure it is not required there as this would cause validation errors.') . '</p><p>' . t('Unless you know what you are doing, all dependent (child) attributes should be marked as required on this page.') . '</p>',
    );
    $attributes = uc_product_get_attributes($node->id());
    $fields = array('aid', 'parent_aid', 'parent_values', 'required');
    $dependencies = \Drupal::database()->select('uc_dropdown_products', 'products')
      ->condition('products.nid', $node->id())
      ->fields('products', $fields)
      ->execute();

    $form['product'] = array(
      '#type' => 'hidden',
      '#value' => $node->id(),
    );
    $parent = array();
    $values = array();
    $required = array();
    $values = $form_state->getValues();
    if (count($values)) {
      foreach ($values['attributes'] as $key => $attribute) {
        $parent[$key] = $attribute['parent'];
        $values[$key] = $attribute['values'];
        $required[$key] = $attribute['required'];
      }
    }
    else {
      foreach ($dependencies as $item) {
        $parent[$item->aid] = $item->parent_aid;
        $values[$item->aid] = unserialize($item->parent_values);
        $required[$item->aid] = $item->required;
      }
    }

    $form['attributes'] = array(
      '#type' => 'table',
      '#header' => array(
        t('Attribute'),
        t('Depends on'),
        t('With values'),
        t('Required'),
      ),
    );
    foreach ($attributes as $attribute) {
      $form['attributes'][$attribute->aid]['attribute'] = array(
        '#markup' => $attribute->name,
      );

      $options = array();
      $options[0] = 'None';
      foreach ($attributes as $option) {
        if ($option->aid != $attribute->aid) {
          $options[$option->aid] = $option->name;
        }
      }
      $selected = array_key_exists($attribute->aid, $parent) ?
                  $parent[$attribute->aid] : 0;
      $form['attributes'][$attribute->aid]['parent'] = array(
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $selected,
        '#ajax' => array(
          'callback' => 'uc_dropdown_attributes_dependent_callback',
          'wrapper' => 'dropdown-' . $attribute->aid . '-replace',
        ),
      );

      $options = array();
      if ($selected == 0) {
        $type = 'select';
      }
      else {
        $parent_attributes = uc_attribute_load($selected);
        if (count($parent_attributes->options) == 0) {
          $type = 'textfield';
        }
        else {
          $type = 'select';
          foreach ($parent_attributes->options as $oid => $option) {
            $options[$oid] = $option->name;
          }
        }
      }
      if ($type == 'select') {
        $form['attributes'][$attribute->aid]['values'] = array(
          '#type' => 'select',
          '#multiple' => TRUE,
          '#prefix' => '<div id="dropdown-' . $attribute->aid . '-replace">',
          '#suffix' => '</div>',
          '#options' => $options,
        );
        if (array_key_exists($attribute->aid, $values)) {
          $form['attributes'][$attribute->aid]['values']['#default_value']
            = $values[$attribute->aid];
        }
      }
      else {
        $form['attributes'][$attribute->aid]['values'] = array(
          '#type' => 'textfield',
          '#prefix' => '<div id="dropdown-' . $attribute->aid . '-replace">',
          '#suffix' => '</div>',
        );
      }

      $form['attributes'][$attribute->aid]['required'] = array(
        '#type' => 'checkbox',
        '#returned_value' => 1,
        '#default_value' => array_key_exists($attribute->aid, $required) ? $required[$attribute->aid] : 0,
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($values['attributes'] as $aid => $attribute) {
      \Drupal::database()->merge('uc_dropdown_products')
        ->key(array(
          'nid' => $values['product'],
          'aid' => $aid,
        ))
        ->fields(array(
          'parent_aid' => $attribute['parent'],
          'parent_values' => serialize($attribute['values']),
          'required' => $attribute['required'],
        ))
        ->execute();
    }
    parent::submitForm($form, $form_state);
  }

}
