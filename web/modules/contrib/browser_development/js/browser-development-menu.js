(function ($) {
  Drupal.behaviors.browser_development = {
    attach: function (context, settings) {

      var ignoreIdString = '.block-browser-development, .block-browser-development *, ' +
        '#toolbar-administration, #toolbar-administration *, ' +
        '.browser-development-menu, .browser-development-menu * ' +
        '.coffee-form-wrapper, .coffee-form-wrapper *' +
        '.ui-widget-content , .ui-widget-content  *';


/**
 * Finds ids in DOM so that a link can be added
 */
$('*[id]', context).once('browser_development').not($(ignoreIdString)).each(function () {
  var idVar = $(this).attr('id');
  $('#' + idVar).prepend(
    '<div class="browser-development-menu-wrapper"><ul  class="browser-development-menu" >' +
    '<a href="#" class="browser-development-menu-a" title="' + idVar + '" id="' + idVar + '">' +
    '<i class="browser-development-cog fa fa-cog"></i>' +
    '</a>' +
    '<li class="hide browser-development-menu-drop-down">' +
    '<ul>' +
    ' <li><a href="#" class="id-background-color" title="' + idVar + '" id="' + idVar + '">Background color</a></li>' +
    ' <li><a href="#" class="id-text-color" title="' + idVar + '" id="' + idVar + '">Text color</a></li>' +
    ' <li><a href="#" class="id-background-image" title="' + idVar + '" id="' + idVar + '">Background image</a></li>' +
    ' <li class="hide">' +
    '<a href="#" class="id-link-colors">Link colors</a>' +
    '<ul>' +
    '<li><a href="#" class="id-link-color" title="' + idVar + '" id="' + idVar + '">Link color</a></li>' +
    '<li><a href="#" class="id-link-background-color" title="' + idVar + '" id="' + idVar + '">Link background color</a></li>' +
    '<li><a href="#" class="id-link-hover-color" title="' + idVar + '" id="' + idVar + '">Hover link color</a></li>' +
    '<li><a href="#" class="id-link-hover-background-color" title="' + idVar + '" id="' + idVar + '">Hover background link color</a></li>' +
    '</ul>' +
    '</li>' +
    ' <li class="hide">' +
    '<a href="#" class="id-font">Typography</a>' +
    '<ul>' +
    '<a href="#" class="id-font-size" title="' + idVar + '" id="' + idVar + '">Font size</a>' +
    '<a href="#" class="id-font-weight" title="' + idVar + '" id="' + idVar + '">Font weight</a>' +
    '<a href="#" class="id-font-type" title="' + idVar + '" id="' + idVar + '">Font type</a>' +
    '<a href="#" class="id-letter-spacing" title="' + idVar + '" id="' + idVar + '">Letter spacing</a>' +
    '<a href="#" class="id-line-height" title="' + idVar + '" id="' + idVar + '">Line height</a>' +
    '</ul>' +
    '</li>' +
    ' <li class="hide">' +
    '<a href="#" class="id-position">Position</a>' +
    '<ul>' +
    '<li><a href="#" class="id-link-left" title="' + idVar + '" id="' + idVar + '">Left</a></li>' +
    '<li><a href="#" class="id-link-right" title="' + idVar + '" id="' + idVar + '">Right</a></li>' +
    '<li><a href="#" class="id-link-center" title="' + idVar + '" id="' + idVar + '">Center</a></li>' +
    '</ul>' +
    '</li>' +
    '<li class="hide">' +
    '<a href="#" class="id-position">Border</a>' +
    '<ul>' +
    '<li><a href="#" class="id-border-left" title="' + idVar + '" id="' + idVar + '">Border left</a></li>' +
    '<li><a href="#" class="id-border-right" title="' + idVar + '" id="' + idVar + '">Border right</a></li>' +
    '<li><a href="#" class="id-border-center" title="' + idVar + '" id="' + idVar + '">Border top</a></li>' +
    '<li><a href="#" class="id-border-center" title="' + idVar + '" id="' + idVar + '">Border bottom</a></li>' +
    '</ul>' +
    '</li>' +
    '</ul>' +
    '</li>' +
    '</ul>' +
    '<input type="text" class="hide-spectrum-input spectrum-' + idVar + '"/></div>');
});

}
};
})(jQuery);
