<?php
/******************************************************
 * casaa_global.tpl.php
 * 
 * @param: 
 *  - $plugins - an array of registered plug-ins and
 * 							 their global values
 *  - $active_plugin - The plug-in to view values for.
 *  - $plugin_form - The form for the selected plugin.
 *  - $domain - The domain id for the domain settings
 * 							being viewed.
 ******************************************************/
?>
<?php 

drupal_add_css(drupal_get_path('module', 'casaa') . '/themes/css/casaa.css', 'module', 'all', TRUE); 
?>
<div id="casaa-section-global">

	<div id="casaa-plugins">
		<?php if (is_array($domains) && !empty($domains)) : ?>
			<?php $i=0; foreach ($domains as $domain) :?>
			<dl<?php print ($active_domain == $domain['domain_id'])?' class="active"':'';?>>
				<dt><?php print l($domain['sitename'], 'admin/structure/casaa/globals/' . $domain['domain_id']);?></dt>
				<dd>
					<ul>
						<?php $i = 0;	foreach ($plugins as $key => $plugin) : ?>
							<?php if (!$active_plugin && $i == 0) {$active_plugin = $key;} ?>
							<li<?php print ($active_plugin == $key)?' class="active"':''; ?>>
								<?php print l($plugin['name'], 'admin/structure/casaa/globals/' . $domain['domain_id'] . '/' . $key); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</dd>
			</dl>
			<?php endforeach; ?>
		<?php else: ?>
			<p>Something ain't right. Call your administrator!</p>
		<?php endif; ?>
	</div>
	
	<div id="casaa-section-form">
		<?php if ($plugin_form !== NULL): ?>
				<?php print drupal_get_form('casaa_status_form');?>
			<?php
				print $plugin_form;
			?>
		<?php else: ?>
			<p>Choose a plugin for the domain.(i need work here.)</p>
		<?php endif;?>
	</div>
	
</div>