(function ($, Drupal, CKEDITOR) {

  'use strict';

  CKEDITOR.plugins.add('html_codesniffer', {
    icons: 'html_codesniffer',

    init: function (editor) {

      // Add styles to override Adminimal styling
      var css = document.createElement('style');
      css.type = 'text/css';
      css.innerHTML = '' +
          '#HTMLCS-wrapper label {' +
          '  font-weight: normal;' +
          '  cursor: default; ' +
          '}' +
          '#HTMLCS-wrapper #HTMLCS-settings-use-standard > select {' +
          '  background: #fff;' +
          '  min-height: none;' +
          '  cursor: auto;' +
          '  color: #000;' +
          '  -webkit-appearance: menulist-button;' +
          '}';
      document.body.appendChild(css);

      editor.addCommand('runAccessibilityAuditor', {
        exec: function (editor) {

          // Load bookmarklet code.
          (function (editorData, baseUrl, standard) {
            var _p = baseUrl;
            var _i = function (s, cb) {
              var sc = document.createElement('script');
              sc.onload = function () {
                sc.onload = null;
                sc.onreadystatechange = null;
                cb.call(this);
              };
              sc.onreadystatechange = function () {
                if (/^(complete|loaded)$/.test(this.readyState) === true) {
                  sc.onreadystatechange = null;
                  sc.onload();
                }
              };
              sc.src = s;
              if (document.head) {
                document.head.appendChild(sc);
              }
              else {
                document.getElementsByTagName('head')[0].appendChild(sc);
              }
            };
            var options = {
              path: _p
            };
            _i(_p + 'HTMLCS.js', function () {
              HTMLCSAuditor.run(standard, editorData, options);
            });
          })(editor.getData(), editor.config['html_codesniffer_base_url'], editor.config['html_codesniffer_standard']);
        }
      });

      editor.ui.addButton('HTML_CodeSniffer', {
        label: 'Run Accessibility Auditor on content in editor',
        command: 'runAccessibilityAuditor'
      });

    }
  });
})(jQuery, Drupal, CKEDITOR);
