<?php

/**
 * Admin settings form, helper for hook_menu().
 */
function mytube_admin_settings() {
  $form['mytube_text'] = array(
    '#type' => 'textfield',
    '#title' => "'Privacy info' text",
    '#default_value' => variable_get('mytube_text', ''),
    '#description' => t('Optional: Customize privacy info description below thumbnail. Use <code>!</code><code>embed</code> and <code>!</code><code>domain</code> for embed link and top-level domain, respectively.'),
  );
  $form['mytube_url'] = array(
    '#type' => 'textfield',
    '#title' => "'Privacy info' link target",
    '#default_value' => variable_get('mytube_url', ''),
    '#description' => t('Specify path where \'Privacy info\' link points to. If left blank, link will point to <a href="@eff">EFF page</a>.', array('@eff' => 'https://www.eff.org/deeplinks/2008/02/embedded-video-and-your-privacy'))
  );
  $form['mytube_ytparams'] = array(
    '#type' => 'textfield',
    '#title' => 'Custom YouTube parameters',
    '#default_value' => variable_get('mytube_ytparams', ''),
    '#description' => t('Parameters for <a href="@google">customizing</a> YouTube videos. Note: <code>autoplay=1</code> is added automatically.', array('@google' => 'http://code.google.com/apis/youtube/player_parameters.html'))
  );
  $form['mytube_trusted_domains'] = array(
    '#type' => 'textarea',
    '#title' => "Trusted domains",
    '#default_value' => variable_get('mytube_trusted_domains', ''),
    '#description' => t('Domains whose content MyTube should allow through instead of filtering, one per line. For example: example.com, myotherdomain.org'),
  );
  $form['mytube_default_width'] = array(
    '#type' => 'textfield',
    '#title' => 'Default width',
    '#default_value' => variable_get('mytube_default_width', ''),
    '#description' => t('Width for all MyTube videos and thumbnails. Default is 320 if left empty.'),
    '#size' => 10,
    '#maxlength' => 4
  );
  $form['mytube_default_height'] = array(
    '#type' => 'textfield',
    '#title' => 'Default height',
    '#default_value' => variable_get('mytube_default_height', ''),
    '#description' => t('Height for all MyTube videos and thumbnails. Default is 240 if left empty.'),
    '#size' => 10,
    '#maxlength' => 4
  );
  $form['mytube_allow_custom_size'] = array(
    '#type' => 'checkbox',
    '#title' => 'Users can override default video sizes.',
    '#default_value' => variable_get('mytube_allow_custom_size', 0),
    '#description' => t('If checked, users will be able to set the width and height for <code>&lt;embed&gt;</code>, <code>&lt;iframe&gt;</code> and <code>&lt;object&gt;</code> tags. If not, MyTube will set the width and height attributes of these tags, and their replacement thumbnail images, to the values defined above (or MyTube defaults if not defined).'),
  );
  $form['mytube_allow_remote_thumb'] = array(
    '#type' => 'checkbox',
    '#title' => 'Users may embed remote images.',
    '#default_value' => variable_get('mytube_allow_remote_thumb', 0),
    '#description' => t('If checked, users will be able to set <abbr class="mytubehelp" title="Hosted somewhere else, i.e. not on this server.">remote</abbr> thumbs with the <code>thumb=</code> attribute. Since such remote thumbs would send ' . l(t('referer headers'), 'https://secure.wikimedia.org/wikipedia/en/wiki/HTTP_referer') . ', it could defeat the purpose of MyTube. If unchecked, remote images in MyTube objects will be replaced with the <a href="@default">default.gif</a> thumbnail.', array(
      '@referer' => url('https://secure.wikimedia.org/wikipedia/en/wiki/HTTP_referer'),
      '@default' => file_create_url(drupal_get_path('module', 'mytube') . '/default.gif')
    ))
  );
  $form['mytube_enable_js_api'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable the <a href="https://developers.google.com/youtube/js_api_reference">YouTube Javascript API</a>.'),
    '#default_value' => variable_get('mytube_enable_js_api', 0),
    '#description' => t('If checked, the iFrame Player API code will be loaded asynchronously and the API will be instantiated when a video thumbnail is clicked.')
  );
  return system_settings_form($form);
}
