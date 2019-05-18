<?php

namespace Drupal\aliyunossfordrupal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\image\ImageStyleInterface;
use Symfony\Component\HttpFoundation\Request;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aliyunossfordrupal_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['aliyunossfordrupal.settings', 'image.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('aliyunossfordrupal.settings');
    $region_map = [
      'oss-cn-hangzhou'    => '华东 1 (oss-cn-hangzhou)',
      'oss-cn-shanghai'    => '华东 2 (oss-cn-shanghai)',
      'oss-cn-qingdao'     => '华北 1 (oss-cn-qingdao)',
      'oss-cn-beijing'     => '华北 2 (oss-cn-beijing)',
      'oss-cn-zhangjiakou' => '华北 3 (oss-cn-zhangjiakou)',
      'oss-cn-huhehaote'   => '华北 5 (oss-cn-huhehaote)',
      'oss-cn-shenzhen'    => '华南 1 (oss-cn-shenzhen)',
      'oss-cn-hongkong'    => '香港 (oss-cn-hongkong)',
      'oss-us-west-1'      => '美国西部 1 （硅谷） (oss-us-west-1)',
      'oss-us-east-1'      => '美国东部 1 （弗吉尼亚） (oss-us-east-1)',
      'oss-ap-southeast-1' => '亚太东南 1 （新加坡） (oss-ap-southeast-1)',
      'oss-ap-southeast-2' => '亚太东南 2 （悉尼） (oss-ap-southeast-2)',
      'oss-ap-southeast-3' => '亚太东南 3 （吉隆坡） (oss-ap-southeast-3)',
      'oss-ap-northeast-1' => '亚太东北 1 （日本） (oss-ap-northeast-1)',
      'oss-eu-central-1'   => '欧洲中部 1 （法兰克福） (oss-eu-central-1)',
      'oss-me-east-1'      => '中东东部 1 （迪拜） (oss-me-east-1)',
    ];

    $form['access_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Aliyun access key ID'),
      '#default_value' => $config->get('access_key'),
      '#required' => TRUE,
    ];
    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Aliyun access key secret'),
      '#default_value' => $config->get('secret_key'),
      '#required' => TRUE,
    ];
    $form['bucket'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bucket name'),
      '#default_value' => $config->get('bucket'),
      '#required' => TRUE,
    ];
    $form['region'] = [
      '#type' => 'select',
      '#options' => $region_map,
      '#title' => $this->t('Region'),
      '#default_value' => $config->get('region'),
      '#description' => $this->t(
        'The region in which your bucket resides. Be careful to specify this accurately,
      as you are likely to see strange or broken behavior if the region is set wrong.<br>'
      ),
      '#required' => TRUE,
    ];
    $form['cname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CNAME (or CDN domain)'),
      '#default_value' => $config->get('cname'),
      '#description' => 'Do not add the HTTP protocol or trailing slash.',
    ];
    $form['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path prefix'),
      '#default_value' => $config->get('prefix'),
      '#description' => $this->t(
        'Uses the path prefix as the root of the file system within your bucket (if blank, the bucket root is used).<br>
      This setting is case sensitive. Do not add a leading or trailing slashes.<br>'
      ),
    ];
    $form['internal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable internal address'),
      '#default_value' => $config->get('internal'),
      '#description' => $this->t('Use an internal HTTP address for communication between ECS and OSS within same region.'),
    ];

    if (\Drupal::moduleHandler()->moduleExists('image')) {
      $form['suppress_itok_output'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Suppress the itok query string for image derivatives'),
        '#default_value' => $this->config('image.settings')->get('suppress_itok_output'),
        '#description' => $this->t('It is recommended to enable this option if you are setting OSS as the upload destination for all image fields.'),
      ];

      $form['styles'] = [
        '#type' => 'table',
        '#caption' => $this->t('Style names mapping'),
        '#header' => [$this->t('Name'), $this->t('Drupal'), $this->t('OSS')],
      ];
      $form['styles_note'] = [
        '#markup' => $this->t('Set image style names mapping between Drupal and OSS (<a href=":img_service">IMG Service</a>).', [
          ':img_service' => 'https://help.aliyun.com/document_detail/44686.html',
        ]),
      ];

      $styles = $config->get('styles');
      foreach ($this->loadImageStyles() as $name => $label) {
        $form['styles'][$name] = [
          'label' => ['#plain_text' => $label],
          'drupal' => ['#plain_text' => $name],
          'oss' => [
            '#type' => 'textfield',
            '#title' => $this->t('OSS image style'),
            '#title_display' => 'invisible',
            '#default_value' => isset($styles[$name]) ? $styles[$name] : NULL,
            '#required' => TRUE,
          ],
        ];
      }
    }

    $form['note'] = [
      '#markup' => $this->t('<div><strong>Note:</strong> need to <a href=":href">Clear all caches</a> to have these settings take effect for cached content.</div>', [
        ':href' => Url::fromRoute('system.performance_settings')->toString(),
      ]),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Loads image styles.
   *
   * @return array
   *   An array of style labels keyed by style name.
   */
  protected function loadImageStyles() {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage('image_style');
    $names = $storage->getQuery()->sort('name')->execute();
    $styles = $storage->loadMultiple($names);
    return array_map(function (ImageStyleInterface $style) {
      return $style->label();
    }, $styles);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if (($cname = trim($form_state->getValue('cname'))) && (strpos($cname, '://') !== FALSE || substr($cname, -1) === '/')) {
      $form_state->setErrorByName('cname', $this->t('CNAME is malformed.'));
    }

    if (($prefix = trim($form_state->getValue('prefix'))) && (strpos($prefix, '/') === 0 || substr($prefix, -1) === '/')) {
      $form_state->setErrorByName('prefix', $this->t('Path prefix is malformed.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $styles = empty($values['styles']) ? [] : array_map(function ($value) {
      return $value['oss'];
    }, $values['styles']);

    $this->config('aliyunossfordrupal.settings')
      ->set('access_key', trim($values['access_key']))
      ->set('secret_key', trim($values['secret_key']))
      ->set('bucket', trim($values['bucket']))
      ->set('region', trim($values['region']))
      ->set('cname', trim($values['cname']))
      ->set('prefix', trim($values['prefix']))
      ->set('internal', (bool) $values['internal'])
      ->set('styles', $styles)
      ->save();

    $this->config('image.settings')
      ->set('suppress_itok_output', (bool) $values['suppress_itok_output'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
