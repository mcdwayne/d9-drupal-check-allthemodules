<?php
/**
 * @file
 * Contains \Drupal\xwechat_menu\Form\MenuConfigForm.
 */

namespace Drupal\xwechat_menu\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Pyramid\Component\WeChat\WeChat;
use Pyramid\Component\WeChat\Request;
use Pyramid\Component\WeChat\Response;

/**
 * Contribute form.
 */
class MenuConfigForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xwechat_menu_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $xwechat_config = NULL) {
    $wechat = new WeChat($xwechat_config);
    $wechat->getAccessToken();
    try {
      $menu = $wechat->getMenu();
    } catch (Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
    
    $form['xmenus'] = array(
      '#type' => 'table',
      '#header' => array(array('data' => $this->t('Name'), 'colspan' => 4), $this->t('Type'), $this->t('Url/Key'), $this->t('Widget')),
      '#empty' => $this->t('No menus available.'),
      '#attributes' => array(
        'id' => 'x-wechat-menu',
      ),
    );

    $form['xmenus']['#tabledrag'][] = array(
      'action' => 'match',
      'relationship' => 'parent',
      'group' => 'xmenu-parent',
      'subgroup' => 'xmenu-parent',
      'source' => 'xmenu-id',
      'hidden' => FALSE,
    );
    $form['xmenus']['#tabledrag'][] = array(
      'action' => 'depth',
      'relationship' => 'group',
      'group' => 'xmenu-depth',
      'hidden' => FALSE,
    );
    $form['xmenus']['#tabledrag'][] = array(
      'action' => 'order',
      'relationship' => 'sibling',
      'group' => 'xmenu-weight',
    );

    $form['#attached']['library'][] = 'xwechat_menu/xwechat.menu';

    if (!empty($menu['menu']['button'])) {
      $delta = $this->menuTableTree($menu['menu']['button'], $form);
    }

    $count = count($menu['menu']['button']);
    $more  = min($count + 6, 16) - $count;
    if ($more > 0) {
      $this->menuTableTree(array_fill($delta, $more,array()), $form, $delta);
    }

    $form['wid'] = array(
      '#type' => 'hidden',
      '#value' => $xwechat_config->wid,
    );

    $form['actions'] = array('#type' => 'actions', '#tree' => FALSE);
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = xwechat_config_load($form_state->getValue('wid'));
    $items = $form_state->getValue('xmenus');
    $menus  = array();
    foreach ($items as $key => $item) {
      $name = $item['name'];
      $depth = $item['depth'];
      $type  = $item['type'];
      $url = $item['url'];

      if (empty($name) || ($depth > 0 && ($type == '' || $url == ''))) {
        continue;
      }

      $menu = array('name' => $name,'type' => strtolower($type));
      if ($menu['type'] == 'view') {
        $menu['url'] = $url;
      } else {
        $menu['key'] = $url;
      }
      if ($depth == 0) {
        $menus[$key] = $menu;
        $lastRoot = $key;
      } elseif (isset($menus[$lastRoot])) {
        $menus[$lastRoot]['sub_button'][] = $menu;
      }
    }

    $menus = array_values($menus);

    foreach ($menus as $k => $v) {
      if (!empty($v['sub_button'])) {
        unset($menus[$k]['type'], $menus[$k]['key']);
      }
    }

    $data = array('button' => $menus);

    db_update('xwechat_config')
      ->fields(array('data_menu' => json_encode($data)))
      ->condition('wid', $config->wid)
      ->execute();

    $wechat = new WeChat($config);

    try {
      $wechat->setMenu($data);
      drupal_set_message(t('Menu has been updated.'));
    } catch (Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      $form_state->setRebuild();
    }
  }

  /**
   * Build table menu item.
   */  
  function menuTableTree(array $items, array &$form, $delta = 0, $depth = 0) {
      static $types = array(
        '' => '',
        'click' => '点击推事件 (click)',
        'view' => '跳转URL (view)',
        'scancode_push' => '扫码推事件 (scancode_push)',
        'scancode_waitmsg' => '扫码推事件且弹出提示 (scancode_waitmsg)',
        'pic_sysphoto' => '弹出系统拍照发图 (pic_sysphoto)',
        'pic_photo_or_album' => '弹出拍照或者相册发图 (pic_photo_or_album)',
        'pic_weixin' => '弹出微信相册发图器 (pic_weixin)',
        'location_select' => '弹出地理位置选择器 (location_select)',
        'media_id' => '下发消息[除文本] (media_id)',
        'view_limited' => '跳转图文消息URL (view_limited)',
      );

      foreach($items as $menu_item){
        $indentation = array();
        if ($depth > 0) {
          $indentation = array(
            '#theme' => 'indentation',
            '#size' => $depth,
          );
        }

        $form['xmenus'][$delta]['name'] = array(
          '#prefix' => !empty($indentation) ? drupal_render($indentation) : '',
          '#type' => 'textfield',
          '#default_value' => isset($menu_item['name']) ? $menu_item['name'] : '',
          '#size' => 30,
        );
        $form['xmenus'][$delta]['id'] = array(
          '#type' => 'hidden',
          '#value' => $delta,
          '#attributes' => array(
            'class' => array('xmenu-id'),
          ),
        );
        $form['xmenus'][$delta]['parent'] = array(
          '#type' => 'hidden',
          '#default_value' => $delta-1,
          '#attributes' => array(
            'class' => array('xmenu-parent'),
          ),
        );
        $form['xmenus'][$delta]['depth'] = array(
          '#type' => 'hidden',
          '#default_value' => $depth,
          '#attributes' => array(
            'class' => array('xmenu-depth'),
          ),
        );
        $form['xmenus'][$delta]['type'] = array(
          '#type' => 'select',
          '#options' => $types,
          '#default_value' => isset($menu_item['type']) ? $menu_item['type'] : '',
        );
        $form['xmenus'][$delta]['url'] = array(
          '#type' => 'textfield',
          '#default_value' => isset($menu_item['url']) ? $menu_item['url'] : (isset($menu_item['key']) ? $menu_item['key'] : ''),
        );

        $form['xmenus'][$delta]['weight'] = array(
          '#type' => 'weight',
          '#delta' => $delta,
          '#title' => $this->t('Weight for menu'),
          '#title_display' => 'invisible',
          '#default_value' => $delta,
          '#attributes' => array(
            'class' => array('xmenu-weight'),
          ),
        );

        $form['xmenus'][$delta]['#attributes']['class'][] = 'draggable';        

        if(!empty($menu_item['sub_button'])){
          $delta = $delta+1;
          $this->menuTableTree($menu_item['sub_button'], $form, $delta, 1);
        }
        $delta++;
      }

      return $delta;
    }
}
