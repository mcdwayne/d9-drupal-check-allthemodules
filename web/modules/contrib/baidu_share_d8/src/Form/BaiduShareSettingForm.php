<?php

namespace Drupal\baidu_share\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Configure book settings for this site.
 */
class BaiduShareSettingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'baidushare_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['baidu_share.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $types = node_type_get_names();
    $config = $this->config('baidu_share.settings');

    $url = Url::fromUri('http://share.baidu.com/code/advance', ['attributes' => ['target' => '_blank']]);

    $form['notice'] = [
      '#markup' => $this->t('每次更新配置之后，需要清除缓存让配置生效。该配置页参数详细说明可以参考这里：') . Link::fromTextAndUrl($this->t('百度分享专业开发版'), $url)->toString(),
    ];
    $form['common'] = [
      '#type' => 'fieldset',
      '#title' => SafeMarkup::format('@label', ['@label' => $this->t('通用设置')]),
      '#tree' => TRUE,
    ];
    // Common setting.
    $this->_setCommonForm($form['common'], $config);

    // Share button setting.
    $form['share_button'] = [
      '#type' => 'fieldset',
      '#title' => SafeMarkup::format('@label', ['@label' => $this->t('分享按钮设置')]),
      '#tree' => TRUE,
    ];
    $this->_setSharrButtonForm($form['share_button'], $config);

    // Slide setting.
    $form['slide'] = [
      '#type' => 'fieldset',
      '#title' => SafeMarkup::format('@label', ['@label' => $this->t('浮窗分享设置')]),
      '#tree' => TRUE,
    ];
    $this->_setSlideForm($form['slide'], $config);

    // Share image setting.
    $form['share_image'] = [
      '#type' => 'fieldset',
      '#title' => SafeMarkup::format('@label', ['@label' => $this->t('图片分享设置')]),
      '#tree' => TRUE,
    ];
    $this->_setShareImageForm($form['share_image'], $config);

    // Select share setting.
    $form['select_share'] = [
      '#type' => 'fieldset',
      '#title' => SafeMarkup::format('@label', ['@label' => $this->t('划词分享设置')]),
      '#tree' => TRUE,
    ];
    $this->_setSelectShareForm($form['select_share'], $config);

    return parent::buildForm($form, $form_state);
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
    $common = $form_state->getValue('common');
    $shareButton = $form_state->getValue('share_button');
    $slide = $form_state->getValue('slide');
    $shareImage = $form_state->getValue('share_image');
    $selectShare = $form_state->getValue('select_share');
    $baidushare = array_merge($common, $shareButton, $slide, $shareImage, $selectShare);

    $destination = 'public://baidushare';
    file_prepare_directory($destination, FILE_CREATE_DIRECTORY);
    $validators = ['file_validate_extensions' => ['jpg png gif']];
    if ($file = file_save_upload('bdPic', $validators, $destination, 0)) {
      $baidushare['bdPic'] = $file->getFileUri();
    }
    $config = $this->config('baidu_share.settings');
    foreach ($baidushare as $k => $v) {
      $config->set($k, $v);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Common Settings.
   */
  private function _setCommonForm(array &$form, $config) {
    $url = Url::fromUri('http://share.baidu.com/code/advance#toid', ['attributes' => ['target' => '_blank']]);
    $form['bdText'] = [
      '#type' => 'textfield',
      '#title' => $this->t('bdText'),
      '#default_value' => $config->get('bdText'),
      '#required' => FALSE,
      '#description' => $this->t('分享的内容'),
    ];
    $form['bdDesc'] = [
      '#type' => 'textarea',
      '#title' => $this->t('bdDesc'),
      '#default_value' => $config->get('bdDesc'),
      '#required' => FALSE,
      '#description' => $this->t('分享的摘要'),
    ];
    $form['bdUrl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('bdUrl'),
      '#default_value' => $config->get('bdUrl'),
      '#required' => FALSE,
      '#description' => $this->t('分享的Url地址'),
    ];
    $imgUrl = file_create_url($config->get('bdPic'));
    $form['bdPic'] = [
      '#name' => 'files[bdPic]',
      '#type' => 'file',
      '#title' => $this->t('bdPic'),
      '#required' => FALSE,
      '#description' => $this->t('分享的图片'),
    ];
    if (!empty($config->get('bdPic'))) {
      $form['bdPic']['#default_value'] = $imgUrl;
      $form['show_bdPic'] = [
        '#markup' => '当前分享的图片：<br>
        <div class="show-bdpic"><img src="' . $imgUrl . '" /></div>',
        '#attached' => [
          'library' => [
            'baidu_share/baidushare.settings',
          ],
        ],
      ];
    }
    $form['bdSign'] = [
      '#type' => 'textfield',
      '#title' => $this->t('bdSign'),
      '#default_value' => $config->get('bdSign'),
      '#required' => FALSE,
      '#description' => $this->t("是否进行回流统计。<br>
'on': 默认值，使用正常方式挂载回流签名（#[数字签名]）<br>
'off': 关闭数字签名，不统计回流量<br>
'normal': 使用&符号连接数字签名，不破坏原始url中的#锚点"),
    ];
    $form['bdMini'] = [
      '#type' => 'number',
      '#title' => $this->t('bdMini'),
      '#default_value' => $config->get('bdMini'),
      '#required' => FALSE,
      '#description' => $this->t('下拉浮层中分享按钮的列数。可选参数：1｜2｜3'),
    ];
    $form['bdMiniList'] = [
      '#type' => 'textfield',
      '#title' => $this->t('bdMiniList'),
      '#default_value' => $config->get('bdMiniList'),
      '#required' => FALSE,
      '#description' => $this->t('自定义下拉浮层中的分享按钮类型和排列顺序。<br>
        格式：[\'qzone\',\'tsina\']
        分享媒体id对应表参考这里：') . Link::fromTextAndUrl('分享媒体id对应表', $url)->toString(),
    ];
    $form['onBeforeClick'] = [
      '#type' => 'textarea',
      '#title' => $this->t('onBeforeClick'),
      '#default_value' => $config->get('onBeforeClick'),
      '#required' => FALSE,
      '#description' => $this->t('在用户点击分享按钮时执行代码，更改配置。<br>
cmd为分享目标id，config为当前设置，返回值为更新后的设置。'),
      '#placeholder' => 'function(cmd,config){}',
    ];
    $form['onAfterClick'] = [
      '#type' => 'textarea',
      '#title' => $this->t('onAfterClick'),
      '#default_value' => $config->get('onAfterClick'),
      '#required' => FALSE,
      '#description' => $this->t('在用户点击分享按钮后执行代码，cmd为分享目标id。可用于统计等。'),
      '#placeholder' => 'function(cmd,config){}',
    ];
    $form['bdPopupOffsetLeft'] = [
      '#type' => 'number',
      '#title' => $this->t('bdPopupOffsetLeft'),
      '#default_value' => $config->get('bdPopupOffsetLeft'),
      '#required' => FALSE,
      '#description' => $this->t('下拉浮层的y偏移量，正数或者负数'),
    ];
    $form['bdPopupOffsetTop'] = [
      '#type' => 'number',
      '#title' => $this->t('bdPopupOffsetTop'),
      '#default_value' => $config->get('bdPopupOffsetTop'),
      '#required' => FALSE,
      '#description' => $this->t('下拉浮层的x偏移量'),
    ];
    $form['show_count'] = [
      '#type' => 'select',
      '#title' => $this->t('show count'),
      '#default_value' => $config->get('show_count'),
      '#required' => FALSE,
      '#description' => $this->t('是否显示分享数'),
      '#options' => [$this->t('不显示'), $this->t('显示')],
    ];
    $form['list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('list'),
      '#default_value' => $config->get('list'),
      '#required' => FALSE,
      '#description' => $this->t('分享标签列表,每行一个，对应的分享媒体id对应表参考这里：') . Link::fromTextAndUrl('分享媒体id对应表', $url)->toString(),
    ];
  }

  /**
   * Share button setting.
   */
  private function _setSharrButtonForm(array &$form, $config) {
    $form['sharetag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('tag'),
      '#default_value' => $config->get('sharetag'),
      '#required' => FALSE,
      '#description' => $this->t('表示该配置只会应用于data-tag值一致的分享按钮。<br>
如果不设置tag，该配置将应用于所有分享按钮。'),
    ];
    $form['bdSize'] = [
      '#type' => 'number',
      '#title' => $this->t('bdSize'),
      '#default_value' => $config->get('bdSize'),
      '#required' => FALSE,
      '#description' => $this->t('分享按钮的尺寸。可选参数：16｜24｜32'),
    ];
    $form['bdCustomStyle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('bdCustomStyle'),
      '#default_value' => $config->get('bdCustomStyle'),
      '#required' => FALSE,
      '#description' => $this->t('样式文件地址，自定义样式，引入样式文件'),
    ];

  }

  /**
   * Slide settng.
   */
  private function _setSlideForm(array &$form, $config) {
    $form['show_slide'] = [
      '#type' => 'select',
      '#title' => $this->t('show slide'),
      '#default_value' => $config->get('show_slide'),
      '#required' => FALSE,
      '#description' => $this->t('是否显示侧边浮窗'),
      '#options' => [$this->t('不显示'), $this->t('显示')],
    ];
    $form['bdImg'] = [
      '#type' => 'number',
      '#title' => $this->t('bdImg'),
      '#default_value' => $config->get('bdImg'),
      '#required' => FALSE,
      '#description' => $this->t('分享浮窗图标的颜色。可选参数：0｜1｜2｜3｜4｜5｜6｜7｜8'),
    ];
    $form['bdPos'] = [
      '#type' => 'textfield',
      '#title' => $this->t('bdPos'),
      '#default_value' => $config->get('bdPos'),
      '#required' => FALSE,
      '#description' => $this->t('分享浮窗的位置。可选参数：left|right'),
    ];
    $form['bdTop'] = [
      '#type' => 'number',
      '#title' => $this->t('bdTop'),
      '#default_value' => $config->get('bdTop'),
      '#required' => FALSE,
      '#description' => $this->t('分享浮窗与可是区域顶部的距离(px)'),
    ];
  }

  /**
   * Share image setting.
   */
  private function _setShareImageForm(array &$form, $config) {
    $url = Url::fromUri('http://share.baidu.com/code/advance#toid', ['attributes' => ['target' => '_blank']]);
    $form['tag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('tag'),
      '#default_value' => $config->get('tag'),
      '#required' => FALSE,
      '#description' => $this->t('表示该配置只会应用于data-tag值一致的图片。如果不设置tag，该配置将应用于所有图片。'),
    ];
    $form['viewType'] = [
      '#type' => 'textfield',
      '#title' => $this->t('viewType'),
      '#default_value' => $config->get('viewType'),
      '#required' => FALSE,
      '#description' => $this->t('图片分享按钮样式。可选参数：list｜collection'),
    ];
    $form['viewPos'] = [
      '#type' => 'textfield',
      '#title' => $this->t('viewPos'),
      '#default_value' => $config->get('viewPos'),
      '#required' => FALSE,
      '#description' => $this->t('图片分享展示层的位置。可选参数：top｜bottom'),
    ];
    $form['viewColor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('viewColor'),
      '#default_value' => $config->get('viewColor'),
      '#required' => FALSE,
      '#description' => $this->t('图片分享展示层的背景颜色。可选参数：black｜white'),
    ];
    $form['viewSize'] = [
      '#type' => 'number',
      '#title' => $this->t('viewSize'),
      '#default_value' => $config->get('viewSize'),
      '#required' => FALSE,
      '#description' => $this->t('图片分享展示层的图标大小。可选参数：16｜24｜32'),
    ];
    $form['viewList'] = [
      '#type' => 'textfield',
      '#title' => $this->t('viewList'),
      '#default_value' => $config->get('viewList'),
      '#required' => FALSE,
      '#description' => $this->t('自定义展示层中的分享按钮类型和排列顺序。<br>
        格式：[\'qzone\',\'tsina\']
        分享媒体id对应表参考这里：') . Link::fromTextAndUrl('分享媒体id对应表', $url)->toString(),
    ];
  }

  /**
   * Select share setting.
   */
  private function _setSelectShareForm(array &$form, $config) {
    $url = Url::fromUri('http://share.baidu.com/code/advance#toid', ['attributes' => ['target' => '_blank']]);
    $form['bdSelectMiniList'] = [
      '#type' => 'textfield',
      '#title' => $this->t('bdSelectMiniList'),
      '#default_value' => $config->get('bdSelectMiniList'),
      '#required' => FALSE,
      '#description' => $this->t('自定义弹出浮层中的分享按钮类型和排列顺序。<br>
        格式：[\'qzone\',\'tsina\']
        分享媒体id对应表参考这里：') . Link::fromTextAndUrl('分享媒体id对应表', $url)->toString(),
    ];
    $form['bdContainerClass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('bdContainerClass'),
      '#default_value' => $config->get('bdContainerClass'),
      '#required' => FALSE,
      '#description' => $this->t('自定义划词分享的激活区域'),
    ];
  }

}
