(function($, BUE) {
'use strict';

/**
 * @file
 * Defines html building methods.
 */

/**
 * Builds html string from an html object or from the given html arguments
 */
BUE.html = function(tag, innerHTML, attributes) {
  var i, attr, html, selfclosing;
  // Check html object
  if (typeof tag === 'object') {
    innerHTML = tag.html;
    attributes = tag.attributes;
    tag = tag.tag;
  }
  // Check tag
  if (!tag) {
    return innerHTML || '';
  }
  html = '<' + tag;
  if (attributes) {
    for (i in attributes) {
      attr = attributes[i];
      if (attr != null) {
        html += ' ' + i + '="' + ('' + attr).replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;') + '"';
      }
    }
  }
  if (selfclosing = BUE.selfClosing(tag)) {
    html += ' /';
  }
  html += '>' + (innerHTML || '');
  if (!selfclosing) {
    html += '</' + tag + '>';
  }
  return html;
};

/**
 * Checks if a tag is self closing.
 */
BUE.selfClosing = function (tag) {
  return /^(area|br|col|embed|hr|img|input|keygen|param|source|track|wbr)$/.test(tag);
};

/**
 * Creates a DOM element from html string.
 */
BUE.createEl = function(html){
  var el, div = BUE._div;
  if (!div) div = BUE._div = document.createElement('div');
  div.innerHTML = html;
  el = div.firstChild;
  div.removeChild(el);
  return el;
};

/**
 * Removes an element from the document.
 */
BUE.removeEl = function(el){
  var parent = el.parentNode;
  if (parent) return parent.removeChild(el);
};

/**
 * Creates a DOM element from an html object or from the given html arguments
 */
BUE.buildCreateEl = function(a, b, c) {
  return BUE.createEl(BUE.html(a, b, c));
};

/**
 * Extends an attributes object with another.
 */
BUE.extendAttr = function(A, B) {
  // Ovewrite all except the class attribute
  if (B) {
    var oldClass = A['class'];
    BUE.extend(A, B);
    if (oldClass && ('class' in B)) {
      A['class'] = oldClass + (B['class'] ? ' ' + B['class'] : '');
    }
  }
  return A;
};

/**
 * Parses a string as html(optionally of a given tag) and returns html object.
 */
BUE.parseHtml = function(str, tag) {
  var i, arr, key, html, attributes, match, re = new RegExp('^<('+ (tag || '[a-z][a-z0-9]*') +')([^>]*)>(?:([\\s\\S]*)</\\1>)?$');
  if (match = str.match(re)) {
    tag = match[1];
    html = match[3];
    if (html != null || BUE.selfClosing(tag)) {
      attributes = {};
      if (match = match[2].match(/[\w-]+="[^"]*"/g)) {
        for (i = 0; i < match.length; i++) {
          arr = match[i].split('=');
          key = arr.shift();
          attributes[key] = arr.join('=').replace(/"/g, '');
        }
      }
      return {tag: tag, attributes: attributes, html: html};
    }
  }
};

/**
 * Sanitizes html characters.
 */
BUE.plain = function (str) {
  return ('' + str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
};

/**
 * Returns emphasized plain text.
 */
BUE.emplain = function (str) {
  return '<em class="plain">' + BUE.plain(str) + '</em>';
};

/**
 * Creates a tag chooser from an array of tag data ([[tag, title, attributes],[...],...])
 */
BUE.createTagChooserEl = function(tagData, opt) {
  var i, data, htmlObj, wrpEl, baseRowEl, linkHtml, rowHtml, linkEl, rowEl;
  // Set defaults
  opt = BUE.extend({wrapEach: 'div', wrapAll: 'div', applyTag: true, onclick: BUE.eTagChooserLinkClick}, opt);
  // Create base row. Allow links to be the rows.
  linkHtml = BUE.html('a', '', {href: '#', 'class': 'choice-link'});
  rowHtml = opt.wrapEach ? BUE.html(opt.wrapEach, linkHtml, {'class': 'choice'}) : linkHtml;
  baseRowEl = BUE.createEl(rowHtml);
  // Create wrapper
  wrpEl = BUE.buildCreateEl(opt.wrapAll, '', {'class': 'bue-tag-chooser' + (opt.cname ? ' ' + opt.cname : '')});
  // Create rows
  for (i = 0; data = tagData[i]; i++) {
    rowEl = baseRowEl.cloneNode(true);
    linkEl = rowEl.firstChild || rowEl;
    htmlObj = {tag: data[0], attributes: data[2]};
    $(linkEl).data('htmlObj', htmlObj).click(opt.onclick).html(opt.applyTag ? BUE.html.apply(this, data) : data[1]);
    wrpEl.appendChild(rowEl);
  }
  return wrpEl;
};

/**
 * Click event of a tag chooser link.
 */
BUE.eTagChooserLinkClick = function(e) {
  var htmlObj = $(this).data('htmlObj'), Popup = BUE.popupOf(this);
  Popup.close();
  Popup.Editor.insertHtmlObj(htmlObj);
  e.preventDefault();
};

})(jQuery, BUE);