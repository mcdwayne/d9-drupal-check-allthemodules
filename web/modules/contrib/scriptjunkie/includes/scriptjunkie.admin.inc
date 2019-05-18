<?php
/**
 * @file
 * Administrative page for overview of scripts on the site.
 */

/**
 * Menu Callback function for overview page.
 * @param array $form_state
 * @return array
 */
function scriptjunkie_overview($form_state) {
  $header = array(
    t('Script Title'),
    t('Edit'),
    t('Delete')
  );
  $str_desc = t('Description');
  $str_name = t('Namespace');
  $rows = array();
  $scripts = scriptjunkie_get_scripts('all', 'info');
  foreach ($scripts as $name => $script) {
    $description = (!empty($script['general']['description'])) ? '<br />' . $str_desc . ': <em>' . $script['general']['description'] . '</em>' : '';
    $script_details = <<<EOS
<dl>
  <dt>
    {$script['general']['title']}
  </dt>
  <dd>
    {$str_name}: {$script['name']}{$description}
  </dd>
</dl>
EOS;
    array_push($rows,
      array(
        $script_details,
        l(t('edit'), 'admin/settings/scriptjunkie/edit/' . $script['name']),
        l(t('delete'), 'admin/settings/scriptjunkie/delete/' . $script['name']),
      )
    );
  }
  $form['script_list'] = array(
    '#value' => theme('table', $header, $rows)
  );
  return $form;
}
