<?php
/**
 * @file
 * Contains \Drupal\monster_menus\Element\MMCatlist.
 */

namespace Drupal\monster_menus\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Plugin\MMTreeBrowserDisplay\Fallback;
use Drupal\monster_menus\Plugin\MMTreeBrowserDisplay\Groups;
use Drupal\monster_menus\Plugin\MMTreeBrowserDisplay\Nodes;
use Drupal\monster_menus\Plugin\MMTreeBrowserDisplay\Users;

/**
 * Provides a graphical chooser for MM Tree entries.
 *
 * @FormElement("mm_catlist")
 */
class MMCatlist extends FormElement {

  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input'                      => TRUE,
      '#default_value'              => array(),
      '#process'                    => [[$class, 'processGroup'],
                                        [$class, 'process']],
      '#pre_render'                 => [[$class, 'preRenderGroup'],
                                        [$class, 'preRender']],
      '#attached'                   => array('library' => array('monster_menus/mm')),
      '#mm_list_min'                => 0,                 // min number of rows
      '#mm_list_max'                => 0,                 // max number of rows
      '#mm_list_popup_start'        => '',
      '#mm_list_popup_root'         => 1,                 // if 0, show the "[top]" entry
      '#mm_list_enabled'            => '',                // in category browser, attribute of cats user can open
      '#mm_list_selectable'         => Constants::MM_PERMS_APPLY, // in category browser, attribute of cats user can choose
      '#mm_list_buttons_underneath' => FALSE,
      '#mm_list_readonly'           => FALSE,             // let the user select rows, but not edit them
      '#mm_list_no_info'            => FALSE,             // don't show an item's info when clicked
      '#mm_list_route'              => 'monster_menus.browser_load', // route to the popup tree browser
      '#mm_list_submit_on_add'      => FALSE,             // auto-submit the outer form upon first choice
      '#mm_list_initial_focus'      => '',                // form element to get focus by default
      '#mm_list_hide_left_pane'     => FALSE,             // should only be set when min==max==1
      '#mm_list_mode'               => Fallback::BROWSER_MODE_PAGE,
      '#mm_list_other_name'         => '',
      '#mm_list_other_callback'     => 'null',
      '#mm_list_field_name'         => '',                // set field_name and bundle_name when widget for a field
      '#mm_list_bundle_name'        => '',
      '#mm_list_info_func'          => [$this, 'makeEntry'],
      '#theme'                      => 'mm_catlist',
      '#theme_wrappers'             => ['form_element'],
    );
  }

  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
  }

  public static function preRender($element) {
    if (empty($element['#title'])) {
      $element['#title'] = $element['#mm_list_max'] == 1 ? t('Page:') : t('Pages:');
    }
    static::preRenderMMList($element['#mm_list_mode'], $element,
      isset($element['#mm_list_popup_root']) ? $element['#mm_list_popup_root'] : '',
      t('Path:'));
    return $element;
  }

  /**
   * Generate the form code to allow the user to add pages/groups to a node
   *
   * @param $mode
   *   The MM_BROWSER_X constant mode
   * @param array &$elt
   *   An associative array containing the properties of the element. Properties
   *   used: #default_value, #description, #mm_list_autocomplete_name,
   *   #mm_list_route, #mm_list_buttons_underneath, #mm_list_enabled,
   *   #mm_list_max, #mm_list_min, #mm_list_no_info, #mm_list_other_callback,
   *   #mm_list_other_name, #mm_list_popup_root, #mm_list_readonly,
   *   #mm_list_selectable, #mm_list_info_func, #name, #parents, #required,
   *   #title, #type, #value.
   * @param $start_mmtid
   *   The location in the tree at which to default the tree view
   * @param $info_label
   *   The label appearing above the item information line
   */
  static function preRenderMMList($mode, array &$elt, $start_mmtid, $info_label) {
    if (isset($elt['#mm_list_instance'])) {
      return;
    }
    $_mmlist_instance = &drupal_static('_mmlist_instance', 0);

    if (!\Drupal::currentUser()->hasPermission('use tree browser')) {
      $elt['#mm_list_readonly'] = TRUE;
    }
    $max = intval($elt['#mm_list_max']);
    $min = intval($elt['#mm_list_min']);
    $exactly1 = $max == 1 && $min == 1;
    $flags = array();
    $js_flags = array(
      '#mm_list_buttons_underneath' => 'action_under',
      '#mm_list_submit_on_add'      => 'submit_on_add',
      '#mm_list_initial_focus'      => 'initial_focus',
      '#mm_list_hide_left_pane'     => 'hide_left_pane',
    );
    foreach ($js_flags as $api_flag => $js_flag) {
      if (!empty($elt[$api_flag])) {
        $flags[$js_flag] = $elt[$api_flag];
      }
    }
    $select_callback = 'catSelectCallback';
    if (!empty($elt['#mm_list_readonly'])) {
      $label_above_actions = $label_add_cat = $label_replace = $label_delete = '';
      if ($elt['#mm_list_no_info']) {
        $select_callback = 'null';
      }
    }
    else {
      $label_above_actions = t('Action:');
      $label_add_cat = $max == 1 || $mode == Users::BROWSER_MODE_USER ? '' : t('Add...');
      $label_replace = $mode == Users::BROWSER_MODE_USER ? '' : ($max == 1 ? t('Choose...') : t('Replace...'));
      $label_delete = $exactly1 ? '' : ($min == 0 && $max == 1 ? t('Clear') : t('Delete'));
    }
    $label_above_info = !empty($elt['#mm_list_no_info']) ? '' : $info_label;

    if ($mode == Users::BROWSER_MODE_USER) {
      $popup_base = '';
    }
    else {
      if ($elt['#type'] == 'mm_nodelist') {
        $field_id = !empty($elt['#mm_list_field_name']) && !empty($elt['#mm_list_bundle_name']) ? $elt['#mm_list_field_name'] . ',' . $elt['#mm_list_bundle_name'] : '';
        $start = $elt['#mm_list_enabled'] . '-' . $elt['#mm_list_selectable'] . '--' . $field_id . '/';
      }
      else {
        $start = (isset($elt['#mm_list_enabled']) ? $elt['#mm_list_enabled'] : '') . '-' . $elt['#mm_list_selectable'] . '/';
      }
      $popup_base = Url::fromRoute($elt['#mm_list_route'], [], ['query' => ['_path' => "$start_mmtid-$mode-$_mmlist_instance-$start"]])->toString();
      mm_ui_modal_dialog('init', $elt);
    }
    $popup_URL = $elt['#mm_list_popup_start'];

    if ($max == 1 && !$elt['#value']) {
      if ($elt['#default_value']) {
        $elt['#value'] = $elt['#default_value'];
      }
      else {
        $msgs = array(
          Users::BROWSER_MODE_USER => t('(choose a user)'),
          Groups::BROWSER_MODE_GROUP => t('(choose a group)'),
          Nodes::BROWSER_MODE_NODE => t('(choose content)'),
          Fallback::BROWSER_MODE_PAGE => t('(choose a location)'),
        );
        $elt['#value'] = array('' => isset($msgs[$mode]) ? $msgs[$mode] : $msgs[Fallback::BROWSER_MODE_PAGE]);
      }
    }

    $adds = array();
    if (!mm_ui_is_search()) {
      $url = $info = '';
      if (is_array($elt['#value'])) {
        foreach ($elt['#value'] as $mmtid => $name) {
          if (!$mmtid || empty($elt['#mm_list_info_func'])) {
            $url = $exactly1 ? $popup_URL : '';
            $info = '';
          }
          else {
            $elt['#mm_list_info_func']($mmtid, $name, $url, $info, $popup_URL);
          }

          $adds[] = [$name, $mmtid, $url, $info];
        }
      }
      else if (!empty($elt['#value'])) {
        foreach (_mm_ui_split_mmlist($elt['#value']) as $m) {
          $name = $m[2];
          if (!empty($elt['#mm_list_info_func'])) {
            $elt['#mm_list_info_func']($m[1], $name, $url, $info, $popup_URL);
          }
          list($mmtid, $nid) = explode('/', $m[1]);

          if ($mode != Users::BROWSER_MODE_USER && !$nid && !mm_content_user_can($mmtid, $elt['#mm_list_selectable'])) {
            if ($exactly1) {
              $name = t('(choose)');
              $info = '';
            }
            else {
              if ($url == $popup_URL) {
                unset($popup_URL);
              }
              continue;
            }
          }

          $adds[] = [$name, $mmtid, $url, $info];
        }
      }
    }
    else if ($exactly1) {
      $adds[] = ['', '', '', ''];
    }

    if (empty($popup_URL)) {
      $popup_URL = $start_mmtid;
    }
    $popup_label = t('Tree Browser');

    if (isset($elt['#name'])) {
      $name = $elt['#name'];
    }
    else {
      $name = $elt['#parents'][0];
      if (count($elt['#parents']) > 1) {
        $name .= '[' . join('][', array_slice($elt['#parents'], 1)) . ']';
      }
    }

    $other_name = isset($elt['#mm_list_other_name']) ? $elt['#mm_list_other_name'] : '';
    $other_callback = isset($elt['#mm_list_other_callback']) ? $elt['#mm_list_other_callback'] : NULL;

    $class = static::addClass($elt);
    $auto = '';
    if (!empty($elt['#mm_list_autocomplete_name'])) {
      if ($elt['#mm_list_autocomplete_name'] === TRUE) {
        $auto = preg_replace('{\]$}', '-choose]', $name);
      }
      else {
        $auto = $elt['#mm_list_autocomplete_name'];
      }
    }
    $tag = $exactly1 || $elt['#mm_list_readonly'] && $elt['#mm_list_no_info'] ? 'span' : 'a';

    $settings = [
      'isSearch' => mm_ui_is_search(),
      'where' => NULL,
      'listObjDivSelector' => "div[name=mm_list_obj$_mmlist_instance]",
      'outerDivSelector' => "div[name=mm_list_obj$_mmlist_instance] + div[class=\"$class\"]",
      'hiddenName' => $name,
      'add' => $adds,
      'autoName' => $auto,
      'parms' => [
        'minRows' => $min,
        'maxRows' => $max,
        'popupBase' => $popup_base,
        'popupURL' => $popup_URL,
        'popupLabel' => $popup_label,
        'flags' => $flags,
        'addCallback' => 'catAddCallback',
        'replaceCallback' => 'catReplCallback',
        'selectCallback' => $select_callback,
        'dataCallback' => 'catDataCallback',
        'labelAboveActions' => $label_above_actions,
        'labelAddCat' => $label_add_cat,
        'labelReplace' => $label_replace,
        'labelDelete' => $label_delete,
        'labelAboveInfo' => $label_above_info,
        'updateOnChangeName' => $other_name,
        'updateOnChangeCallback' => $other_callback,
      ]
    ];
    $elt['#attached']['drupalSettings']['MM']['mmListInit'][$_mmlist_instance] = $settings;
    $elt += [
      '#mm_list_instance' => $_mmlist_instance++,
      '#mm_list_tag' => $tag,
      '#mm_list_class' => $class,
    ];
  }

  // Helper function to pre-generate an entry in the list.
  private static function makeEntry($mmtid, $name, &$url, &$info, &$popup_URL) {
    $parts = explode('/', $mmtid);
    $mmtid = isset($parts[0]) ? $parts[0] : NULL;
    $node = isset($parts[1]) ? $parts[1] : NULL;

    $parents = mm_content_get_parents($mmtid);
    array_shift($parents);  // skip root
    if ($node) {
      $parents[] = $mmtid;
    }

    $url = implode('/', $parents);
    if ($mmtid) {
      $url .= "/$mmtid";
    }

    $path = array();
    foreach ($parents as $par) {
      if (!($tree = mm_content_get($par))) {
        break;
      }
      $path[] = mm_content_get_name($tree);
    }

    if (!$node) $path[] = $name;
    $info = implode('&nbsp;&raquo; ', $path);

    if (isset($popup_URL)) {
      $top = explode('/', $popup_URL, 2);
      if (($found = strstr($url, "/$top[0]/")) !== FALSE) {
        $url = substr($found, 1);
      }
    }
    else {
      $popup_URL = $url;
    }
  }

  public static function addClass($elt) {
    $list = isset($elt['#attributes']['class']) ? $elt['#attributes']['class'] : array();
    array_unshift($list, 'form-item');
    // FIXME
    //  if ($form_state->getError($elt)) {
    //    $list[] = 'mm-list-error';
    //  }
    return implode(' ', $list);
  }

  /**
   * Split the result generated by setHiddenElt in mm.js.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @return array
   *   The form element.
   */
  public static function process(&$element, FormStateInterface $form_state) {
    if (is_string($element['#value'])) {
      $temp = $element['#value'];
      $element['#value'] = array();
      if (preg_match_all('#(\d+(?:/\d+)*)\{([^}]*)\}#', $temp, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
          $element['#value'][$match[1]] = $match[2];
        }
      }
      $form_state->setValueForElement($element, $element['#value']);
    }
    return $element;
  }

}