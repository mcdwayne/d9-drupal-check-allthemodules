/**
 * Embed parsing
 */
(function($) {
  /*
  * Tiny tokenizer from https://gist.github.com/borgar/451393
  *
  * - Accepts a subject string and an object of regular expressions for parsing
  * - Returns an array of token objects
  *
  * tokenize('this is text.', { word:/\w+/, whitespace:/\s+/, punctuation:/[^\w\s]/ }, 'invalid');
  * result => [{ token="this", type="word" },{ token=" ", type="whitespace" }, Object { token="is", type="word" }, ... ]
  *
  */
  var tokenize = function(s, parsers, default_type) {
    var m, r, l, t, tokens = [];
    while ( s ) {
      t = null;
      m = s.length;
      for ( var key in parsers ) {
        r = parsers[ key ].exec( s );
        // try to choose the best match if there are several
        // where "best" is the closest to the current starting point
        if ( r && ( r.index < m ) ) {
          t = {
            token: r[ 0 ],
            type: key,
            matches: r.slice( 1 )
          }
          m = r.index;
        }
      }
      if ( m ) {
        // there is text between last token and currently 
        // matched token - push that out as default or "unknown"
        tokens.push({
          token : s.substr( 0, m ),
          type  : default_type || 'unknown'
        });
      }
      if ( t ) {
        // push current token onto sequence
        tokens.push( t ); 
      }
      s = s.substr( m + (t ? t.token.length : 0) );
    }
    return tokens;
  }

  var cleanup = function(html) {
    // TODO: ehhh...
    html = html.trim();
    html = html.toLowerCase();
    // remove newlines
    html = html.replace(/\n/g, "");
    // remove whitespace (space and tabs) before tags
    html = html.replace(/[\t ]+\</g, "<");     
    // remove whitespace between tags
    html = html.replace(/\>[\t ]+\</g, "><");
    // remove whitespace after tags
    html = html.replace(/\>[\t ]+$/g, ">");
    return html;
  }

  var parseMarkupTokens = function(embed, markup) {
    embed = cleanup(embed);
    markup = cleanup(markup);

    // tokenize into tokens or other markup
    var tokens = tokenize(markup, { token: /\[[a-zA-Z_\-\:]+\]/ }, 'other');

    replacements = {};

    var before, after;
    for (var i=0; i<tokens.length; i++) {
      if (tokens[i].type == 'token') {
        // get before and after
        // TODO: ensure these are non-tokens
        before = (i > 0) ? tokens[i-1].token : '';
        after = (i < (tokens.length - 1)) ? tokens[i+1].token : '';
        // find pos of before and after
        before_pos = (before) ? embed.indexOf(before) : 0;
        after_pos = (after) ? embed.indexOf(after) : embed.length - 1;

        // add on the length of the string to get the snip points
        before_pos += before.length;

        // chop out the value
        replacements[tokens[i].token] = embed.substr(before_pos, after_pos - before_pos);
      }
    }
    return replacements;
  };

  $(document).on('click', '.embederator__paste-launch', function(e) {
    e.preventDefault();
    $(this).closest('.embederator__preview-wrapper').find('.embederator__paste-box').toggleClass('embederator__hidden');
    var $preview = $(this).closest('.embederator__preview-wrapper').find('.embederator__preview');
    $preview.toggleClass('embederator__hidden');
    var launch_text = $preview.hasClass('embederator__hidden') ? $(this).data('show-tokens') : $(this).data('show-paste');
    $(this).text(launch_text);
  });

  $(document).on('keyup', '.embederator__paste-box textarea, .embederator__paste-box input', function(e) {
    var $wrapper = $(this).closest('.embederator__preview-wrapper');
    var $outerwrapper = $wrapper.closest('.embederator-token-form');
    var markup = $wrapper.find('.embederator__preview--unhighlighted').text();
    var fieldmap = parseMarkupTokens($(this).val(), markup);
    $.each(fieldmap, function(k, v) {
      if (v) {
        $outerwrapper.find('[data-embederator-token="' + k + '"]').find('input').val(v);
      }
    });
  });
})(jQuery);