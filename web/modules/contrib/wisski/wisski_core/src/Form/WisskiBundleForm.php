<?php

namespace Drupal\wisski_core\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

use Drupal\wisski_core\Entity\WisskiBundle;


class WisskiBundleForm extends EntityForm {
  
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['#entity'] = $bundle = $this->entity;

    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add bundle');
    }
    elseif ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit %label bundle', array('%label' => $bundle->label()));
    }

    $form['label'] = array(
      '#title' => t('Label'),
      '#type' => 'textfield',
      '#default_value' => $bundle->label(),
      '#description' => t('The human-readable name of this bundle.'),
      '#required' => TRUE,
      '#size' => 30,
    );

    // @todo: '#disabled' not always FALSE.
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $bundle->id(),
      '#maxlength' => 32,
      '#disabled' => FALSE,
      '#machine_name' => array(
        'exists' => array('\Drupal\wisski_core\Entity\WisskiBundle', 'exists'),
        'source' => array('label'),
      ),
      '#description' => t('A unique machine-readable name for this bundle.'),
    );

    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $bundle->get('description'),
      '#description' => t('Describe this bundle. The text will be displayed on the <em>Add new WisskiEntity</em> page.'),
    );
    
    $menus = WisskiBundle::getWissKIMenus();
    foreach ($menus as $mid => $foo) {
      if ($menu = entity_load('menu', $mid)) {
        $menus[$mid] = $menu->label();
      } else {
        unset($menus[$mid]);
      }
    }
    $form['create_menu_items'] = array(
      '#title' => t('Create menu items'),
      '#type' => 'checkboxes',
      '#options' => $menus,
      '#default_value' => array_keys($bundle->getCreateMenuItems(NULL, WisskiBundle::MENU_CREATE)),
    );

    $form['enable_menu_items'] = array(
      '#title' => t('Enable menu items'),
      '#type' => 'checkboxes',
      '#options' => $menus,
      '#default_value' => array_keys($bundle->getCreateMenuItems(NULL, WisskiBundle::MENU_ENABLE)),
    );
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save bundle');
    $actions['delete']['#value'] = t('Delete bundle');
    $actions['delete']['#access'] = $this->entity->access('delete');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
#  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
#    /** @var \Drupal\wisski_core\WisskiBundleInterface $entity */
#    parent::copyFormValuesToEntity($entity, $form, $form_state);
#    
#  }
  

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var  \Drupal\wisski_core\WisskiBundleInterface $bundle */
    $bundle = $this->entity;

    $menu_items = array();
    foreach ($bundle->create_menu_items as $mid => $v) {
      $menu_items[$mid] = $v ? WisskiBundle::MENU_CREATE : 0;
    }
    foreach ($bundle->enable_menu_items as $mid => $v) {
      if ($v) $menu_items[$mid] |= WisskiBundle::MENU_ENABLE;
    }
    unset($bundle->create_menu_items);
    unset($bundle->enable_menu_items);
    $bundle->setCreateMenuItems($menu_items);
    $status = $bundle->save();

    $t_args = array('%name' => $bundle->label());
    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The bundle %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message(t('The bundle %name has been added.', $t_args));
    }

    $form_state->setRedirectUrl($bundle->urlInfo('list'));
  }
  
}
