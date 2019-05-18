// Propaganistas provides the Rot13 in the global namespace.
// So it has been adapted here an wrapped for Drupal.
// The inline <script> has been replaced as well.
(function ($, Drupal) {
  'use strict';

  function transformRot13(rot13Email) {
    return rot13Email.replace( /[A-Za-z]/g , function(c) {
      return String.fromCharCode( c.charCodeAt(0) + ( c.toUpperCase() <= "M" ? 13 : -13 ) );
    } );
  }

  function init(element) {
    // Remove the css fallback
    $(element).find('.js-disabled').remove();
    // Transform back the rot 13
    var rot13Element = $(element).find('.js-enabled');
    rot13Element.show();
    var transformedEmail = transformRot13(rot13Element.text());
    rot13Element.html('<a href="mailto:'+transformedEmail+'">'+transformedEmail+'</a>');
  }

  Drupal.behaviors.obfuscateRot13 = {
    attach: function (context) {
      $(context).find('.boshfpngr-e13').once('obfuscateRot13').each(function () {
        init(this);
      });
    }
  };
})(jQuery, Drupal);
