/**
 * @file
 * Plugin definition and settings.
 */
'use strict';

CKEDITOR.plugins.add('responsivearea', {
  icons: 'responsivearea',
  init: pluginInit
});

function pluginInit(editor) {
  editor.responsivearea_path =  this.path;

  editor.ui.addButton('AddResponsiveArea', {
    label: Drupal.t('Insert a responsive area'),
    command: 'beResponsive',
    icon: this.path + 'icons/responsivearea.png'
  });


  editor.addCommand('beResponsive', {
    exec: function (editor, data) {
      editor.openDialog('AddResponsiveAreaDialog' + editor.name);
    }
  });

  CKEDITOR.dialog.add('AddResponsiveAreaDialog' + editor.name,beResponsive);
}

function beResponsive(editor,data) {
  return {
    title: 'Responsive Areas',
    minWidth: 400,
    minHeight: 200,
    contents: [
      {
        id: 'tab-basic',
        label: 'Basic Settings',
        elements: [
          {
            type: 'radio',
            id: 'layout',
            label: '<p><strong>Please choose the layout you want</strong></p><br />',
            items: [
              ['<br /><em>1 area - 100%</em><br /><br /><img src="' + editor.responsivearea_path + '/images/1_100.png" />', '1_100'],
              ['<br /><em>2 areas - 50% - 50%</em><br /><br /><img src="' + editor.responsivearea_path + '/images/2_50_50.png" />', '2_50_50'],
              ['<br /><em>2 areas - 75% - 25%</em><br /><br /><img src="' + editor.responsivearea_path + '/images/2_75_25.png" />', '2_75_25'],
              ['<br /><em>2 areas - 25% - 75%</em><br /><br /><img src="' + editor.responsivearea_path + '/images/2_25_75.png" />', '2_25_75'],
              ['<br /><em>3 areas - 33% - 34% - 33%</em><br /><br /><img src="' + editor.responsivearea_path + '/images/3_33_34_33.png" />', '3_33_34_33']
            ],
            style: 'display: block;text-align:center',
            default: '2_50_50'
          }
        ]
      }
    ],
    onOk: function () {
      var dialog = this;
      var mode = dialog.getValueOf('tab-basic', 'layout');
      var tpl = responsiveness_get_template(mode);
      if (tpl !== "") {
        editor.insertHtml(tpl);
      }
    }
  };
}

function responsiveness_get_template(tpl) {
  'use strict';
  var grid = "";
  switch (tpl) {
    case '1_100':
      grid = '<div class="ckeditor-col-container clearfix">';
      grid += '<div class="grid-12 twelvecol first-col"><p>lorem ipsum</p></div>';
      grid += '</div><br />';
      break;

    case '2_50_50':
      grid = '<div class="ckeditor-col-container clearfix">';
      grid += '<div class="grid-6 sixcol first-col"><p>lorem ipsum</p></div>';
      grid += '<div class="grid-6 sixcol last-col"><p>lorem ipsum</p></div>';
      grid += '</div><br />';
      break;

    case '2_75_25':
      grid = '<div class="ckeditor-col-container clearfix">';
      grid += '<div class="grid-8 eightcol first-col"><p>lorem ipsum</p></div>';
      grid += '<div class="grid-4 fourcol last-col"><p>lorem ipsum</p></div>';
      grid += '</div><br />';
      break;

    case '2_25_75':
      grid = '<div class="ckeditor-col-container clearfix">';
      grid += '<div class="grid-4 fourcol first-col"><p>lorem ipsum</p></div>';
      grid += '<div class="grid-8 eightcol last-col"><p>lorem ipsum</p></div>';
      grid += '</div><br />';
      break;

    case '3_33_34_33':
      grid = '<div class="ckeditor-col-container clearfix">';
      grid += '<div class="grid-4 fourcol first-col"><p>lorem ipsum</p></div>';
      grid += '<div class="grid-4 fourcol"><p>lorem ipsum</p></div>';
      grid += '<div class="grid-4 fourcol last-col"><p>lorem ipsum</p></div>';
      grid += '</div><br />';
      break;

  }
  return grid;
}
