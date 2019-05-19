/**
 * @file
 * This file provides webfontloader with the appropriate font definitions.
 */
if (typeof drupalSettings.google_webfont_loader_api.font_config !== 'undefined') {
  WebFontConfig = drupalSettings.google_webfont_loader_api.font_config;
}
// This adds the active class back in the event the page is set to hidden and
// the fonts have not loaded. This seems to be a partial possibility with self
// hosted fonts.
setTimeout(function() {
  var el = document.querySelectorAll('html')[0];
  loadingClass = 'wf-loading';
  activeClass = 'wf-active';
  if (el.classList && google_webfont_loader_api_html_has_class(el, loadingClass)) {
    el.classList.remove(loadingClass);
    el.classList.add(activeClass);
  }
  else if (google_webfont_loader_api_html_has_class(el, loadingClass)) {
    el.className = el.className.replace(new RegExp('(^|\\b)' + 'wf-loading'.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
    el.className += ' ' + activeClass;
  }
}, 3000);

function google_webfont_loader_api_html_has_class(el, className) {
  if (el.classList)
    return el.classList.contains(className);
  else
    return new RegExp('(^| )' + className + '( |$)', 'gi').test(el.className);
}
