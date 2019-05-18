<?php
/**
 * @file
 * Administrative form for the G2 Glossary module.
 *
 * @copyright Copyright (C) 2005-2011 Frederic G. MARAND for Ouest Systèmes Informatiques (OSInet)
 *
 * @license Licensed under the CeCILL, version 2 and General Public License version 2 and later
 *
 * License note: G2 is distributed by OSInet to its customers under the
 * CeCILL 2.0 license. OSInet support services only apply to the module
 * when distributed by OSInet, not by any third-party further down the
 * distribution chain.
 *
 * If you obtained G2 from drupal.org, that site received it under the
 * GPLv2 license and can therefore distribute it under the GPLv2, and
 * so can you and just anyone down the chain as long as the GPLv2 terms
 * are abided by, the module distributor in that case being the
 * drupal.org organization or the downstream distributor, not OSInet.
 */

/**
 * Implement the former hook_settings().
 *
 * @return array
 */
function g2_admin_settings() {
  $form['main'] = array(
    '#type'          => 'fieldset',
    '#title'         => t('Main page'),
  );
  $form['main'][G2VARMAIN] = array(
    '#type'          => 'textfield',
    '#title'         => t('Node or function used for the main page of G2 on this site'),
    '#default_value' => variable_get(G2VARMAIN, 0),
    '#description'   => t('<p>When themeing for G2 is supplied by a non-G2-aware theme, the <code>theme_g2_main</code> function will use content derived from this value as the main page for the G2 glossary, wrapped between alphabars. The value can be either a node nid (typically for an unpublished page or story node), or a function name (typically from a site-specific module). G2-aware themes can provide their own home page, using this page as a basis, or not.</p>'),
  );
  $form['main'][G2VARPATHMAIN] = array(
    '#type'          => 'textfield',
    '#title'         => t('Path for the main page of G2 on this site'),
    '#default_value' => variable_get(G2VARPATHMAIN, G2DEFPATHMAIN),
    '#description'   => t('<p>This setting allows site admins to modify the URL at which the home page will be found.
         This will typically be done to replace the standard path ("g2") by a SEO-engineered path.</p>'),
  );
  $form['main'][G2VARPAGETITLE] = array(
    '#type'          => 'textfield',
    '#title'         => t('Override for site title on G2 pages'),
    '#default_value' => variable_get(G2VARPAGETITLE, G2DEFPAGETITLE),
    '#description'   => t('<p>This setting allows site admins to modify the site title on G2 page, typically for SEO purposes. The title can include !token, which will be replaced by the standard Drupal site title. If set to empty, the default site name will not be overridden.</p>',
      array('!token' => '@title')),
  );
  $form['main'][G2VARTOOLTIPS] = array(
    '#type'          => 'checkbox',
    '#title'         => t('Enable tooltips on definitions'),
    '#default_value' => variable_get(G2VARTOOLTIPS, G2DEFTOOLTIPS),
    '#description'   => t('Set the title attribute on definition links to the teaser of the target definition, if only one exists, or to the number of definitions, if several exist. This mechanism is only enabled on non-remote glossaries, and cause one additional query per definition link on pages where containg them.'),
  );

  $form['automation'] = array(
    '#type'          => 'fieldset',
    '#title'         => t('Site automation')
  );
  $form['automation'][G2VARHIDDENTITLE] = array(
    '#type'          => 'checkbox',
    '#title'         => t('Embed a CSS-masked version of the definition titles in the node rendition'),
    '#default_value' => variable_get(G2VARHIDDENTITLE, G2DEFHIDDENTITLE),
    '#description'   => t('This setting enables search to work better on definition titles, but can be frowned upon by search engines.'),
  );
  $form['automation'][G2VARNOFREETAGGING] = array(
    '#type'          => 'checkbox',
    '#title'         => t('Hide terms in free-tagging vocabularies from users without G2 administrator permissions.'),
    '#default_value' => variable_get(G2VARNOFREETAGGING, TRUE),
    '#description'   => t('Terms bound to a G2 node belonging in a free tagging vocabulary are typically not meant for end-users.'),
  );
  $form['remoting'] = array(
    '#type'          => 'fieldset',
    '#title'         => t('Remoting'),
  );
  $def = variable_get(G2VARREMOTEG2, G2DEFREMOTEG2);
  if (empty($def)) {
    $def = G2DEFREMOTEG2 ;
  }
  $form['remoting'][G2VARREMOTEG2] = array(
    '#type'          => 'textfield',
    '#title'         => t('Use the URL of this remote G2 glossary, or !local for a local glossary',
      array(
        '!local' => '<code>' . check_plain(G2DEFREMOTENO) . '</code>',
      )
    ),
    '#default_value' => $def,
    '#description'   => t('Using G2 remoting, you can supply automatic links from your site to a glossary on another site, without having to maintain anything locally. You will just need to enable the "G2 Glossary Filter" at !url for each format using it. Use !local to maintain a glossary on your site. Erase the field and save TWICE to restore the default value, the Riff dictionary of computing.',
      array(
        '!url' => l(t('input formats'), 'admin/settings/filters'),
        '!local' => '<code>' . check_plain(G2DEFREMOTENO) . '</code>',
      )
    ),
  );
  $form['homonyms'] = array(
    '#type'          => 'fieldset',
    '#title'         => t('Homonyms processing')
  );
  $disambiguation_url = url('g2/entries', array('absolute' => TRUE));
  $form['homonyms'][G2VARHOMONYMS] = array(
    '#type'          => 'textfield',
    '#title'         => t('Node used for the homonyms disambiguation page of G2 on this site'),
    '#default_value' => variable_get(G2VARHOMONYMS, G2DEFHOMONYMS),
    '#description'   => t('If this node is non-zero, this is the page used to build the disambiguation
          page for G2 entries at <code>!link/&lt;some entry&gt;</code>.
          Otherwise, a default page is used.', array('!link' => $disambiguation_url)),
  );
  $form['homonyms'][G2VARGOTOSINGLE] = array(
    '#type'          => 'checkbox',
    '#title'         => t('Enable auto-jump to single match on entry'),
    '#default_value' => variable_get(G2VARGOTOSINGLE, TRUE),
    '#description'   => t('This setting enables automatic redirection from
      <code>!link/&lt;some entry&gt;</code> to the entry page if only one match exists for "some entry".
      This is especially useful for links generated from content nodes.',
      array('!link' => $disambiguation_url)),
  );
  $form['homonyms'][G2VARHOMONYMSREDIRECT] = array(
    '#type'          => 'select',
    '#title'         => t('The type of HTTP redirection to be used if auto-jump is enabled'),
    '#default_value' => variable_get(G2VARHOMONYMSREDIRECT, G2DEFHOMONYMSREDIRECT),
    '#options'       => array(
      '301'                 => t('Permanent redirect (HTTP 301)'),
      G2DEFHOMONYMSREDIRECT => t('Temporary redirect (HTTP 302)'),
    ),
    '#description'   => t('If the "auto-jump to single match on entry" feature is enabled,
      choose which type of redirection is generated. Technically, a temporary redirect (!see302)
      makes more sense, especially is the glossary is often updated, but if the glossary
      is stable, or if the site admin has reason to fear a 302 search engine blacklisting,
      this setting makes it possible to use a permanent redirect (!see301).',
      array(
        '!see302' => '<a href="http://tools.ietf.org/html/rfc2616#section-10.3.3">RFC 2616 §10.3.3</a>',
        '!see301' => '<a href="http://tools.ietf.org/html/rfc2616#section-10.3.2">RFC 2616 §10.3.2</a>'
      )
    ),
  );

  $ar_voc = array('0'  => t('None'));
  foreach (taxonomy_get_vocabularies(G2NODETYPE) as $voc) {
    $ar_voc[$voc->vid] = $voc->name;
  }
  $form['homonyms'][G2VARHOMONYMSVID] = array(
    '#type'          => 'select',
    '#title'         => t('Vocabulary to be used on the homonyms disambiguation page'),
    '#default_value' => variable_get(G2VARHOMONYMSVID, G2DEFHOMONYMSVID),
    '#description'   => t('If a vocabulary is chosen here, the terms bound to ambiguous entries (homonyms)
       will be displayed on the defaut disambiguation page.'),
    '#options'       => $ar_voc,
  );

  $form['advanced'] = array(
    '#type'          => 'fieldset',
    '#title'         => t('Advanced settings (for programmers only)'),
    '#collapsible'   => TRUE,
    '#collapsed'     => TRUE,
  );
  $form['advanced'][G2VARXMLRPC] = array(
    '#type'          => 'checkbox',
    '#title'         => t('Enable G2 remote XML-RPC services'),
    '#default_value' => variable_get(G2VARXMLRPC, G2DEFXMLRPC),
    '#description'   => t('This setting enables remote anonymous use of the G2 glossary through its XML-RPC services.
       Enabling it without additional access control is typically not a good idea.
       Local XML-RPC is not enabled by default.'),
  );

  $form['advanced'][G2VARRPCTHROTTLE] = array(
    '#type'          => 'textfield',
    '#title'         => t('RPC throttle'),
    '#default_value' => variable_get(G2VARRPCTHROTTLE, G2DEFRPCTHROTTLE),
    '#description'   => t('This setting is a coefficient applied to the Top and Latest block for their XML-RPC services.
        It defines the maximum number of entries an XML-RPC client is allowed to return
        in just one call as the product of the block setting and this coefficient.'),
  );
  $form['advanced'][G2VERSION] = array(
    '#value'         => '<p>'
      . t('This site is running G2 version %version. See the <a href="!page" title="G2 Glossary project page">G2 project page</a> on Drupal.org.',
        array(
          '%version' => G2VERSION,
          '!page'    => 'http://drupal.org/project/g2',
        )
      )
      . '</p>',
  );

  $form['wipe_submit'] = array(
    '#type'          => 'submit',
    '#value'         => G2TITLEREFERERWIPE,
    '#weight'        => 2,
    '#submit'        => array('g2_referer_wipe_button_submit'),
    '#callback'      => '_g2_referer_wipe',
  );

  // Explicit handler needed: the explicit submit handler declaration in
  // system_settings_form() prevents implicit handlers from running.
  $form['#submit'] = array('g2_admin_settings_submit');
  $ret = system_settings_form($form);
  return $ret;
  }

/**
 * Rebuild menu if the G2 main page changed.
 *
 * This is needed because cache_clear_all() in
 * system_settings_form_submit() only clears {cache_block} and
 * {cache_page}. The forced cache clear is also needed in some combinations of
 * core Pressflow and Cacherouter handler.
 *
 * @param array $form
 * @param array $form_state
 *
 * @return void
 */
function g2_admin_settings_submit(&$form, &$form_state) {
  if ($form_state['values'][G2VARPATHMAIN] != variable_get(G2VARPATHMAIN, G2DEFPATHMAIN)) {
    cache_clear_all('variables', 'cache');
    variable_set('menu_rebuild_needed', TRUE);
  }
}

/**
 * Make sure the node chosen for the main page is valid.
 *
 * @param array $form
 * @param array $form_state
 *
 * @return void
 */
function g2_admin_settings_validate($form, &$form_state) {
  $main = $form_state['values']['g2_main'];
  if (is_numeric($main)) {
    if ($main != 0 && !is_object(node_load($main))) {
      form_set_error('g2_main', t('The node chosen for the main page must be a valid one, or 0: "@nid" is not a valid node id.',
        array('@nid' => $main)));
    }
  }
  elseif (!function_exists($main)) {
    form_set_error('g2_main', t('The function chosen for the main page must visible to G2: "@function" is not a valid function name.',
      array('@function' => $main)));
  }

  $nid = $form_state['values']['g2_homonyms'];
  if ($nid != 0 && !is_object(node_load($nid))) {
    form_set_error('g2_homonyms', t('The node chosen for the homonyms disambiguation page must be a valid one, or 0: "@nid" is not a valid node id.',
      array('@nid' => $nid)));
  }

  // Sanitize RPC throttle
  $form_state['values']['g2_rpc_throttle'] = (int) $form_state['values']['g2_rpc_throttle'];

  // Make sure tooltips are not enabled for remote glossaries
  if ($form_state['values'][G2VARTOOLTIPS] && $form_state['values'][G2VARREMOTEG2] != G2DEFREMOTENO) {
    form_set_error(G2VARTOOLTIPS, t('Tooltips are only available on local glossaries'));
  }
}

