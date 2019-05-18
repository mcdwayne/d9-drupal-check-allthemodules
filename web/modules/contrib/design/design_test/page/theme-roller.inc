<?php

/**
 * @file
 * jQuery UI Theme Roller.
 */

/**
 * Tests jQuery UI theme roller.
 */
function design_test_page_theme_roller() {
  // Load all the libraries
  drupal_add_library('system', 'ui.accordion');
  drupal_add_library('system', 'ui.autocomplete');
  drupal_add_library('system', 'ui.datepicker');
  drupal_add_library('system', 'ui.dialog');
  drupal_add_library('system', 'ui.draggable');
  drupal_add_library('system', 'ui.droppable');
  drupal_add_library('system', 'ui.progressbar');
  drupal_add_library('system', 'ui.resizable');
  drupal_add_library('system', 'ui.selectable');
  drupal_add_library('system', 'ui.slider');
  drupal_add_library('system', 'ui.sortable');
  drupal_add_library('system', 'ui.tabs');

  // Add the demo files
  drupal_add_js(drupal_get_path('module', 'design_test') . '/page/theme-roller.js');
  drupal_add_css(drupal_get_path('module', 'design_test') . '/page/theme-roller.css');

  $output = <<<EOT
<div id="components" class="clearfix">
  <div id="compGroupA" class="clearfix">
    <!-- Accordion -->
    <h2 class="demoHeaders">Accordion</h2>
    <div id="accordion">
      <h3><a href="#">Section 1</a></h3>
      <div>
        <p>Mauris mauris ante, blandit et, ultrices a, suscipit eget, quam. Integer ut neque. Vivamus nisi metus, molestie vel, gravida in, condimentum sit amet, nunc. Nam a nibh. Donec suscipit eros. Nam mi. Proin viverra leo ut odio. Curabitur malesuada. Vestibulum a velit eu ante scelerisque vulputate.</p>
      </div>
      <h3><a href="#">Section 2</a></h3>
      <div>
        <p>Sed non urna. Donec et ante. Phasellus eu ligula. Vestibulum sit amet purus. Vivamus hendrerit, dolor at aliquet laoreet, mauris turpis porttitor velit, faucibus interdum tellus libero ac justo. Vivamus non quam. In suscipit faucibus urna. </p>
      </div>
      <h3><a href="#">Section 3</a></h3>
      <div>
        <p>Nam enim risus, molestie et, porta ac, aliquam ac, risus. Quisque lobortis. Phasellus pellentesque purus in massa. Aenean in pede. Phasellus ac libero ac tellus pellentesque semper. Sed ac felis. Sed commodo, magna quis lacinia ornare, quam ante aliquam nisi, eu iaculis leo purus venenatis dui. </p>
        <ul>
          <li>List item one</li>
          <li>List item two</li>
          <li>List item three</li>
        </ul>
      </div>
    </div>
    <!-- Tabs -->
    <h2 class="demoHeaders">Tabs</h2>
    <div id="tabs">
      <ul>
        <li><a href="#tabs-1">First</a></li>
        <li><a href="#tabs-2">Second</a></li>
        <li><a href="#tabs-3">Third</a></li>
      </ul>
      <div id="tabs-1">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</div>
      <div id="tabs-2">Phasellus mattis tincidunt nibh. Cras orci urna, blandit id, pretium vel, aliquet ornare, felis. Maecenas scelerisque sem non nisl. Fusce sed lorem in enim dictum bibendum.</div>
      <div id="tabs-3">Nam dui erat, auctor a, dignissim quis, sollicitudin eu, felis. Pellentesque nisi urna, interdum eget, sagittis et, consequat vestibulum, lacus. Mauris porttitor ullamcorper augue.</div>
    </div>
    <!-- Dialog -->
    <h2 class="demoHeaders">Dialog</h2>
    <p><a href="#" id="dialog_link" class="ui-state-default ui-corner-all"><span class="ui-icon ui-icon-newwin"></span>Open Dialog</a></p>
    <h2 class="demoHeaders">Overlay and Shadow Classes</h2>
    <div style="position: relative; width: 96%; height: 200px; padding:1% 4%; overflow: hidden;" class="fakewindowcontain">
      <p>Lorem ipsum dolor sit amet,  Nulla nec tortor. Donec id elit quis purus consectetur consequat. </p><p>Nam congue semper tellus. Sed erat dolor, dapibus sit amet, venenatis ornare, ultrices ut, nisi. Aliquam ante. Suspendisse scelerisque dui nec velit. Duis augue augue, gravida euismod, vulputate ac, facilisis id, sem. Morbi in orci. </p><p>Nulla purus lacus, pulvinar vel, malesuada ac, mattis nec, quam. Nam molestie scelerisque quam. Nullam feugiat cursus lacus.orem ipsum dolor sit amet, consectetur adipiscing elit. Donec libero risus, commodo vitae, pharetra mollis, posuere eu, pede. Nulla nec tortor. Donec id elit quis purus consectetur consequat. </p><p>Nam congue semper tellus. Sed erat dolor, dapibus sit amet, venenatis ornare, ultrices ut, nisi. Aliquam ante. Suspendisse scelerisque dui nec velit. Duis augue augue, gravida euismod, vulputate ac, facilisis id, sem. Morbi in orci. Nulla purus lacus, pulvinar vel, malesuada ac, mattis nec, quam. Nam molestie scelerisque quam. </p><p>Nullam feugiat cursus lacus.orem ipsum dolor sit amet, consectetur adipiscing elit. Donec libero risus, commodo vitae, pharetra mollis, posuere eu, pede. Nulla nec tortor. Donec id elit quis purus consectetur consequat. Nam congue semper tellus. Sed erat dolor, dapibus sit amet, venenatis ornare, ultrices ut, nisi. Aliquam ante. </p><p>Suspendisse scelerisque dui nec velit. Duis augue augue, gravida euismod, vulputate ac, facilisis id, sem. Morbi in orci. Nulla purus lacus, pulvinar vel, malesuada ac, mattis nec, quam. Nam molestie scelerisque quam. Nullam feugiat cursus lacus.orem ipsum dolor sit amet, consectetur adipiscing elit. Donec libero risus, commodo vitae, pharetra mollis, posuere eu, pede. Nulla nec tortor. Donec id elit quis purus consectetur consequat.</p>
      <!-- ui-dialog -->
      <div class="ui-overlay"><div class="ui-widget-overlay"></div><div class="ui-widget-shadow ui-corner-all" style="width: 302px; height: 152px; position: absolute; left: 50px; top: 30px;"></div></div>
      <div style="position: absolute; width: 280px; height: 130px; left: 50px; top: 30px; padding: 10px;" class="ui-widget ui-widget-content ui-corner-all">
          <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
      </div>
    </div>
    <!-- ui-dialog -->
    <div id="dialog" title="Dialog Title">
      <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
    </div>
    <h2 class="demoHeaders">Framework Icons (content color preview)</h2>
    <ul id="icons" class="ui-widget ui-helper-clearfix">
EOT;

  $icons = array(
    'ui-icon-carat-1-n',
    'ui-icon-carat-1-ne',
    'ui-icon-carat-1-e',
    'ui-icon-carat-1-se',
    'ui-icon-carat-1-s',
    'ui-icon-carat-1-sw',
    'ui-icon-carat-1-w',
    'ui-icon-carat-1-nw',
    'ui-icon-carat-2-n-s',
    'ui-icon-carat-2-e-w',
    'ui-icon-triangle-1-n',
    'ui-icon-triangle-1-ne',
    'ui-icon-triangle-1-e',
    'ui-icon-triangle-1-se',
    'ui-icon-triangle-1-s',
    'ui-icon-triangle-1-sw',
    'ui-icon-triangle-1-w',
    'ui-icon-triangle-1-nw',
    'ui-icon-triangle-2-n-s',
    'ui-icon-triangle-2-e-w',
    'ui-icon-arrow-1-n',
    'ui-icon-arrow-1-ne',
    'ui-icon-arrow-1-e',
    'ui-icon-arrow-1-se',
    'ui-icon-arrow-1-s',
    'ui-icon-arrow-1-sw',
    'ui-icon-arrow-1-w',
    'ui-icon-arrow-1-nw',
    'ui-icon-arrow-2-n-s',
    'ui-icon-arrow-2-ne-sw',
    'ui-icon-arrow-2-e-w',
    'ui-icon-arrow-2-se-nw',
    'ui-icon-arrowstop-1-n',
    'ui-icon-arrowstop-1-e',
    'ui-icon-arrowstop-1-s',
    'ui-icon-arrowstop-1-w',
    'ui-icon-arrowthick-1-n',
    'ui-icon-arrowthick-1-ne',
    'ui-icon-arrowthick-1-e',
    'ui-icon-arrowthick-1-se',
    'ui-icon-arrowthick-1-s',
    'ui-icon-arrowthick-1-sw',
    'ui-icon-arrowthick-1-w',
    'ui-icon-arrowthick-1-nw',
    'ui-icon-arrowthick-2-n-s',
    'ui-icon-arrowthick-2-ne-sw',
    'ui-icon-arrowthick-2-e-w',
    'ui-icon-arrowthick-2-se-nw',
    'ui-icon-arrowthickstop-1-n',
    'ui-icon-arrowthickstop-1-e',
    'ui-icon-arrowthickstop-1-s',
    'ui-icon-arrowthickstop-1-w',
    'ui-icon-arrowreturnthick-1-w',
    'ui-icon-arrowreturnthick-1-n',
    'ui-icon-arrowreturnthick-1-e',
    'ui-icon-arrowreturnthick-1-s',
    'ui-icon-arrowreturn-1-w',
    'ui-icon-arrowreturn-1-n',
    'ui-icon-arrowreturn-1-e',
    'ui-icon-arrowreturn-1-s',
    'ui-icon-arrowrefresh-1-w',
    'ui-icon-arrowrefresh-1-n',
    'ui-icon-arrowrefresh-1-e',
    'ui-icon-arrowrefresh-1-s',
    'ui-icon-arrow-4',
    'ui-icon-arrow-4-diag',
    'ui-icon-extlink',
    'ui-icon-newwin',
    'ui-icon-refresh',
    'ui-icon-shuffle',
    'ui-icon-transfer-e-w',
    'ui-icon-transferthick-e-w',
    'ui-icon-folder-collapsed',
    'ui-icon-folder-open',
    'ui-icon-document',
    'ui-icon-document-b',
    'ui-icon-note',
    'ui-icon-mail-closed',
    'ui-icon-mail-open',
    'ui-icon-suitcase',
    'ui-icon-comment',
    'ui-icon-person',
    'ui-icon-print',
    'ui-icon-trash',
    'ui-icon-locked',
    'ui-icon-unlocked',
    'ui-icon-bookmark',
    'ui-icon-tag',
    'ui-icon-home',
    'ui-icon-flag',
    'ui-icon-calculator',
    'ui-icon-cart',
    'ui-icon-pencil',
    'ui-icon-clock',
    'ui-icon-disk',
    'ui-icon-calendar',
    'ui-icon-zoomin',
    'ui-icon-zoomout',
    'ui-icon-search',
    'ui-icon-wrench',
    'ui-icon-gear',
    'ui-icon-heart',
    'ui-icon-star',
    'ui-icon-link',
    'ui-icon-cancel',
    'ui-icon-plus',
    'ui-icon-plusthick',
    'ui-icon-minus',
    'ui-icon-minusthick',
    'ui-icon-close',
    'ui-icon-closethick',
    'ui-icon-key',
    'ui-icon-lightbulb',
    'ui-icon-scissors',
    'ui-icon-clipboard',
    'ui-icon-copy',
    'ui-icon-contact',
    'ui-icon-image',
    'ui-icon-video',
    'ui-icon-script',
    'ui-icon-alert',
    'ui-icon-info',
    'ui-icon-notice',
    'ui-icon-help',
    'ui-icon-check',
    'ui-icon-bullet',
    'ui-icon-radio-off',
    'ui-icon-radio-on',
    'ui-icon-pin-w',
    'ui-icon-pin-s',
    'ui-icon-play',
    'ui-icon-pause',
    'ui-icon-seek-next',
    'ui-icon-seek-prev',
    'ui-icon-seek-end',
    'ui-icon-seek-first',
    'ui-icon-stop',
    'ui-icon-eject',
    'ui-icon-volume-off',
    'ui-icon-volume-on',
    'ui-icon-power',
    'ui-icon-signal-diag',
    'ui-icon-signal',
    'ui-icon-battery-0',
    'ui-icon-battery-1',
    'ui-icon-battery-2',
    'ui-icon-battery-3',
    'ui-icon-circle-plus',
    'ui-icon-circle-minus',
    'ui-icon-circle-close',
    'ui-icon-circle-triangle-e',
    'ui-icon-circle-triangle-s',
    'ui-icon-circle-triangle-w',
    'ui-icon-circle-triangle-n',
    'ui-icon-circle-arrow-e',
    'ui-icon-circle-arrow-s',
    'ui-icon-circle-arrow-w',
    'ui-icon-circle-arrow-n',
    'ui-icon-circle-zoomin',
    'ui-icon-circle-zoomout',
    'ui-icon-circle-check',
    'ui-icon-circlesmall-plus',
    'ui-icon-circlesmall-minus',
    'ui-icon-circlesmall-close',
    'ui-icon-squaresmall-plus',
    'ui-icon-squaresmall-minus',
    'ui-icon-squaresmall-close',
    'ui-icon-grip-dotted-vertical',
    'ui-icon-grip-dotted-horizontal',
    'ui-icon-grip-solid-vertical',
    'ui-icon-grip-solid-horizontal',
    'ui-icon-gripsmall-diagonal-se',
    'ui-icon-grip-diagonal-se',
  );
  foreach ($icons as $icon) {
    $output .= '<li class="ui-state-default ui-corner-all" title="' . $icon . '"><span class="ui-icon ' . $icon . '"></span></li>';
  }

    $output .= <<<EOT
    </ul>
  </div><!-- /#compGroupA -->
  <div id="compGroupB" class="clearfix">
    <!-- Button -->
    <h2 class="demoHeaders">Button</h2>
    <button id="button">A button element</button>
    <h2 class="demoHeaders">Button set</h2>
    <form>
      <div id="radioset">
        <input type="radio" id="radio1" name="radio"><label for="radio1">Choice 1</label>
        <input type="radio" id="radio2" name="radio" checked="checked"><label for="radio2">Choice 2</label>
        <input type="radio" id="radio3" name="radio"><label for="radio3">Choice 3</label>
      </div>
    </form>
    <!-- Autocomplete -->
    <h2 class="demoHeaders">Autocomplete</h2>
    <div>
      <input id="auto-complete" style="z-index: 100; position: relative" type="text" title="type &quot;a&quot;">
    </div>
    <!-- Slider -->
    <h2 class="demoHeaders">Slider</h2>
    <div id="slider"></div>
    <!-- Datepicker -->
    <h2 class="demoHeaders">Datepicker</h2>
    <div id="datepicker"></div>
    <!-- Progressbar -->
    <h2 class="demoHeaders">Progressbar</h2>
    <div id="progressbar"></div>
    <!-- Highlight / Error -->
    <h2 class="demoHeaders">Highlight / Error</h2>
    <div class="ui-widget">
      <div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;">
        <p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
        <strong>Hey!</strong> Sample ui-state-highlight style.</p>
      </div>
    </div>
    <br />
    <div class="ui-widget">
      <div class="ui-state-error ui-corner-all" style="padding: 0 .7em;">
        <p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
        <strong>Alert:</strong> Sample ui-state-error style.</p>
      </div>
    </div>
  </div>
</div>
EOT;

  return $output;
}