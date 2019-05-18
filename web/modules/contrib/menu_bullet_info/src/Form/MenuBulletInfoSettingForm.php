<?php
/**
* @file
* Contains \Drupal\menu_bullet_info\Form\MenuBulletInfoSettingForm.
*/

namespace Drupal\menu_bullet_info\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;
use Drupal\Core\Menu\MenuTreeStorage;
use Drupal\views\Views;

/**
 * Implements an example form.
 */
class MenuBulletInfoSettingForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_bullet_info_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

$menus = Menu::loadMultiple();
    $options = array();

    $bullets = \Drupal::state()->get('bullet_info') ?: array();
    $storage = $form_state->getStorage();
//dsm($storage);
    $storage['bullets'] =
      isset($storage['bullets']) ? $storage['bullets'] : count($bullets);

    $form['bullets'] = array(
        '#type' => 'container',
        '#tree' => TRUE,
        '#prefix' => '<div id="bullets">',
        '#suffix' => '</div>',
      );
//dsm($storage);
$form_state->setStorage($storage);
    if ($storage['bullets']) {
      for ($i = 0; $i < $storage['bullets']; $i++) {

        $form['bullets']['items'][$i] = array(
          '#type' => 'fieldset',
          '#tree' => TRUE,
          '#title' => t('Bullet Info #'.($i+1)),
        );

        // Create default select field with default selected menu options.
        $form['bullets']['items'][$i]['link'] = array(
          '#type' => 'select',
          '#empty_option' => '- ' . t('Select a menu links') . ' -',
          '#default_value' => (current($bullets))?key($bullets):'',
          '#options' => $this->_bullet_get_items_list_options(),
          '#prefix' => '<div id="menu-items-list-'.$i.'">',
          '#suffix' => '</div>',
          '#title' => t('Menu'),
        );

        $form['bullets']['items'][$i]['view'] = array(
          '#type' => 'select',
          '#empty_option' => '- ' . t('Select a view') . ' -',
          '#default_value' => (current($bullets))?current($bullets):'',
          '#options' => $this->_bullet_get_views_list_options(),
          '#title' => t('View'),
        );
        next($bullets);
      }
    }

    $form['bullets']['add_bullet'] = array(
      '#type' => 'submit',
      '#value' => t('Add one bullet'),
      '#submit' => array('::bulletInfoAjaxAddBullet'),
      '#ajax' => array(
        'callback' => '::bulletInfoRebuildCallback',
        'wrapper' => 'bullets',
    ),
    );
    if ($storage['bullets'] > 0) {
      $form['bullets']['remove_bullet'] = array(
        '#type' => 'submit',
        '#value' => t('Remove last bullet'),
        '#submit' => array('::bulletInfoAjaxRemoveBullet'),
        '#ajax' => array(
          'callback' => '::bulletInfoRebuildCallback',
          'wrapper' => 'bullets',
        ),
      );
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save configuration'),
    );

    return $form;
  }

  /**
  * Callback for both ajax-enabled buttons.
  *
  * Selects and returns the fieldset with the names in it.
  */
  public function bulletInfoRebuildCallback(array &$form, FormStateInterface  &$form_state) {
    return $form['bullets'];
  }

  /**
  * Submit handler for the "add one" button.
  *
  * Increments the max counter and causes a form rebuild.
  */
  public function bulletInfoAjaxAddBullet(array &$form, FormStateInterface &$form_state) {
    $storage = $form_state->getStorage();
    $storage['bullets']++;
    $form_state->setStorage($storage);
    $form_state->setRebuild(TRUE);
  }

  /**
  * Submit handler for the "remove one" button.
  *
  * Decrements the max counter and causes a form rebuild.
  */
  public function bulletInfoAjaxRemoveBullet($form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    if ($storage['bullets'] > 0) {
      $storage['bullets']--;
    }
    $form_state->setStorage($storage);
    $form_state->setRebuild(TRUE);
  }

  /**
  * Function to return menu's items list.
  */
  private function _bullet_get_items_list_options() {
    $menus = Menu::loadMultiple();
    $options = array();
    foreach($menus as $menu_id => $menu){
      $menu_tree = \Drupal::menuTree();
      // Load the tree based on this set of parameters.
      $tree = $menu_tree->load($menu_id, new \Drupal\Core\Menu\MenuTreeParameters());
      $manipulators = array(
        // Only show links that are accessible for the current user.
        array('callable' => 'menu.default_tree_manipulators:checkAccess'),
        // Use the default sorting of menu links.
        array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
        array('callable' => 'toolbar_menu_navigation_links'),
      );
      $tree = $menu_tree->transform($tree, $manipulators);
      // Finally, build a renderable array from the transformed tree.
      $menu_data = $menu_tree->build($tree);
      $items_list = array();
      $indent = '-';
      $this->_bullet_all_items($menu_tree, $tree, $items_list,$indent);
      $options[$menu_id] = $items_list;
    }
    return $options;
  }

  /**
  * Function recursive to return items list.
  */
  private function _bullet_all_items($menu_tree, $tree, &$items_list,$indent){
    foreach ($tree as $item) {
      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $item->link;
      $items_list[$link->getBaseId().':'.$link->getDerivativeId()] = $indent.' '.$link->getTitle();
      if ($item->subtree) {
        $this->_bullet_all_items($menu_tree, $item->subtree, $items_list, $indent.'-');
      }
    }
  }

  /**
  * Function to return View's items list.
  */
  public function _bullet_get_views_list_options(){
    $views = array();
    $all_views = Views::getEnabledViews();
    foreach ($all_views as $name => $view) {
      foreach($view->get('display') as $display_name => $display_data){
        $views[$name."#".$display_name] = '['.$view->get('label').'] '.$display_data['display_title'];
      }
    }
    return $views;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $bullets = $form_state->getValue('bullets');
    $bullets = isset($bullets['items'])?$bullets['items']:array();
    foreach($bullets as $index => $bullet){
      if(empty($bullet['link'])) $form_state->setErrorByName('bullets][items]['.$index.'][link', $this->t('Select a menu link for BULLET INFO #'.($index+1)));
      if(empty($bullet['view'])) $form_state->setErrorByName('bullets][items]['.$index.'][view', $this->t('Select a view for BULLET INFO #'.($index+1)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bullets = $form_state->getValue('bullets');
    $var = array();
    if(isset($bullets['items'])){
      foreach($bullets['items'] as $bullet){
        $var[$bullet['link']] = $bullet['view'];
      }
      \Drupal::state()->set('bullet_info', $var);
    }else{
      \Drupal::state()->set('bullet_info', array());
    }

    drupal_set_message($this->t('Configuration has saved.'));
  }

}
