(function ($, Drupal) {
  Drupal.behaviors.fetchAndSetConfig = {
    attach: function (context, settings) {
      function syntaxHighlight(json) {
        if (typeof json != 'string') {
             json = JSON.stringify(json, undefined, 2);
        }
        json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
          var cls = 'number';
          if (/^"/.test(match)) {
              if (/:$/.test(match)) {
                  cls = 'key';
              } else {
                  cls = 'string';
              }
          } else if (/true|false/.test(match)) {
              cls = 'boolean';
          } else if (/null/.test(match)) {
              cls = 'null';
          }
          return '<span class="' + cls + '">' + match + '</span>';
        });
      }
      $(context).find('#configuration-preview').once('#loader-container').each( function() {
        var clip = new ClipboardJS('.btn.copy');
        clip.on('success', function(e) {
          $('.btn.copy').html('Copied!');
          setTimeout(function() {
            $('.btn.copy').html('Copy to clipboard');
          }, 2000);
          e.clearSelection();
        });
        $.get('/rest/session/token').done(function (data) {
          var csrfToken = data;
          $.ajax({
            url: '/admin/backstop_generator/backstop_configuration/export?_format=json',
            method: 'GET',
            headers: {
              'Content-Type': 'application/hal+json',
              'X-CSRF-Token': csrfToken,
            },
            success: function (node) {
              var str = syntaxHighlight(JSON.stringify(node, null, 2));
              $('#configuration-preview').html(str);
            }
          });
        });
      });
    }
  };
})(jQuery, Drupal);
