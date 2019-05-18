const config = drupalSettings.devel.codemirror;

config.mode = 'text/x-php';
config.tabSize = 2;

CodeMirror.fromTextArea(document.getElementById('edit-code'), config);
