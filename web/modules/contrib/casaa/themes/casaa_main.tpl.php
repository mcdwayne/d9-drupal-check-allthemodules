<?php
/**************************************************************
 * casaa_main.tpl.php
 * 
 * @params:
 *  - $domains - array of available domains and their options
 *  - $options
 *  - $active_domain - 
 **************************************************************/
?>
<?php 
drupal_add_css(drupal_get_path('module', 'casaa') . "/themes/css/casaa.css", 'module', 'all', TRUE); 

ctools_include('modal');
ctools_modal_add_js();
?>
<div id="casaa-section-main">

	<?php if (is_array($domains) && !empty($domains)) : ?>
		<ul id="casaa-domains">
			<?php $i=0; foreach ($domains as $domain) :?>
				<li<?php print ($domain['domain_id'] == $active_domain) ? ' class="active"' : ''; ?>>
					<?php print l($domain['sitename'], 'admin/structure/casaa/main/' . $domain['domain_id']);?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else: ?>
		<p>Something ain't right. Call your administrator!</p>
	<?php endif; ?>
	
	<ul id="casaa-move">
		<li class="export"><a href="#">Export</a></li>
		<li class="import"><a href="#">Import</a></li>
	</ul>
	
	<?php print l('Search Mappings', 'admin/structure/casaa/mappings/nojs/search/' . $active_domain, array(
	'attributes' => array('class' => 'search ctools-use-modal')));?>
	
	<dl id="casaa-manage-term-mappings">
		<dt>Term Mappings<?php print l('+', 'admin/structure/casaa/mappings/nojs/add/' . $active_domain . '/terms'/*, array(
		'attributes' => array('class' => 'ctools-use-modal'))*/); ?></dt>
		<dd>
			<ul>
				<li><?php print l('View All', 'admin/structure/casaa/mappings/nojs/view/' . $active_domain . '/terms', array(
		 		'attributes' => array('class' => 'ctools-use-modal'))); ?></li>
			</ul>
		</dd>
	</dl>
	
	<dl id="casaa-manage-path-mappings">
		<dt>Path Mappings<?php print l('+', 'admin/structure/casaa/mappings/nojs/add/' . $active_domain . '/paths'/*, array(
		'attributes' => array('class' => 'ctools-use-modal'))*/); ?></dt>
		<dd>
			<ul>
				<li><?php print l('View All', 'admin/structure/casaa/mappings/nojs/view/' . $active_domain . '/paths', array(
				'attributes' => array('class' => 'ctools-use-modal'))); ?></li>
			</ul>
		</dd>
	</dl>
	
</div>