<?php

/**
 * @file
 * Contains \Drupal\xwechat_material\Form\MaterialImportForm.
 */

namespace Drupal\xwechat_material\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Pyramid\Component\WeChat\WeChat;
use Pyramid\Component\WeChat\Request;
use Pyramid\Component\WeChat\Response;

/**
 * Configure xwechat settings for this site.
 */
class MaterialImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xwechat_import_message';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $xwechat_config = NULL) {
    $types = node_type_get_names();

    $form['import_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Content type for import materia'),
      '#default_value' => 'xwechat_material',
      '#options' => $types,
      '#required' => TRUE,
      '#disabled' => TRUE,
    );
    $form['wid'] = array(
      '#type' => 'hidden',
      '#value' => $xwechat_config->wid,
    );
    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Import'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('import_type'))) {
      $form_state->setErrorByName('import_type', $this->t('Please select a import content type.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $xwechat_config = xwechat_config_load($form_state->getValue('wid'));
    $wechat = new WeChat($xwechat_config);
    $wechat->getAccessToken();
    $news = $wechat->getMaterialList(array('type'=>'news', 'offset' => 0, 'count' => 20));

    if(isset($news['item']) && !empty($news['item'])){
      foreach($news['item'] as $key => $item){
        $node = array();
        
        $node['type'] = $form_state->getValue('import_type');
        $node['field_media_id'] = $item['media_id'];
        $node['field_material_type'] = 'news';
        $node['changed'] = $item['update_time'];

        if(!empty($item['content']['news_item'])){
          foreach($item['content']['news_item'] as $item_key => $list_item){
            $node['title'] = $list_item['title'];
            $node['body'] = $list_item['content'];
            $node['field_content_source_url'] = $list_item['content_source_url'];
            $node['field_thumb_media_id'] = $list_item['thumb_media_id'];
            $node['field_news_url'] = $list_item['url'];
          }
        }
        
        $xwechat_meteria_node = entity_create('node', $node);
        if($xwechat_meteria_node->save()){
          drupal_set_message(t('导入成功！'));
          $url = new Url('xwechat.material.list', ['xwechat_config' => $form_state->getValue('wid')]);
          $form_state->setRedirectUrl($url);
        }else{
          drupal_set_message(t('导入失败！'), 'error');
        }
      }
    }else{
      drupal_set_message(t('导入失败，可能您今天已经超过使用导入接口的限制，请明天再试！'), 'warning');
    }
  }

}

