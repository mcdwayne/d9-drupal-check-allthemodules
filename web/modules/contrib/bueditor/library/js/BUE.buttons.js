(function($, BUE) {
'use strict';

/**
 * @file
 * Defines default buttons.
 */

/**
 * Register default button definitions.
 */
BUE.registerButtons('BUE', function() {
  var buttons = {
    // Separator
    '-': {
      id: '-',
      template: '<span class="bue-separator"></span>'
    },
    // New line
    '/': {
      id: '/',
      template: '<span class="bue-newline"></span>'
    },
    // Bold
    bold: {
      id: 'bold',
      label: BUE.t('Bold'),
      cname: 'ficon-bold',
      code: '<strong>|</strong>',
      shortcut: 'Ctrl+B'
    },
    // Italic
    italic: {
      id: 'italic',
      label: BUE.t('Italic'),
      cname: 'ficon-italic',
      code: '<em>|</em>',
      shortcut: 'Ctrl+I'
    },
    // Underline
    underline: {
      id: 'underline',
      label: BUE.t('Underline'),
      cname: 'ficon-underline',
      code: '<ins>|</ins>',
      shortcut: 'Ctrl+U'
    },
    // Strikethrough
    strike: {
      id: 'strike',
      label: BUE.t('Strikethrough'),
      cname: 'ficon-strike',
      code: '<del>|</del>'
    },
    // Quote
    quote: {
      id: 'quote',
      label: BUE.t('Quote'),
      cname: 'ficon-quote',
      code: '<blockquote>|</blockquote>'
    },
    // Code
    code: {
      id: 'code',
      label: BUE.t('Code'),
      cname: 'ficon-code',
      code: '<code>|</code>'
    },
    // Bulleted list
    ul: {
      id: 'ul',
      label: BUE.t('Bulleted list'),
      cname: 'ficon-ul',
      code: BUE.editorInsertUL
    },
    // Numbered list
    ol: {
      id: 'ol',
      label: BUE.t('Numbered list'),
      cname: 'ficon-ol',
      code: BUE.editorInsertOL
    },
    // Link
    link: {
      id: 'link',
      label: BUE.t('Link'),
      cname: 'ficon-link',
      code: BUE.editorInsertLink
    },
    // Image
    image: {
      id: 'image',
      label: BUE.t('Image'),
      cname: 'ficon-image',
      code: BUE.editorInsertImage
    },
    // Undo
    undo: {
      id: 'undo',
      label: BUE.t('Undo'),
      cname: 'ficon-undo',
      shortcut: 'Ctrl+Z',
      code: BUE.editorUndo
    },
    // Undo
    redo: {
      id: 'redo',
      label: BUE.t('Redo'),
      cname: 'ficon-redo',
      shortcut: 'Ctrl+Y',
      code: BUE.editorRedo
    }
  };

  // Heading 1-6
  for (var id, i = 1; i < 7; i++) {
    id = 'h' + i;
    buttons[id] = {
      id: id,
      label: BUE.t('Heading !n', {'!n': i}),
      text: 'H' + i,
      code: '<' + id + '>|</' + id + '>'
    };  
  }

  return buttons;
});

/**
 * Bulleted list button callback.
 */
BUE.editorInsertUL = function(E) {
  E.tagLines('li', 'ul');
};

/**
 * Numbered list button callback.
 */
BUE.editorInsertOL = function(E) {
  E.tagLines('li', 'ol');
};

/**
 * Link button callback.
 */
BUE.editorInsertLink = function(E) {
  E.tagDialog('a', [
    {name: 'href', title: BUE.t('URL'), required: true, suffix: E.browseButton('href', 'link')},
    {name: 'html', title: BUE.t('Text')}
  ], BUE.t('Link'));
};

/**
 * Image button callback.
 */
BUE.editorInsertImage = function(E) {
  E.tagDialog('img', [
    {name: 'src', title: BUE.t('URL'), required: true, suffix: E.browseButton('src', 'image')},
    {name: 'width', title: BUE.t('Width x Height'), suffix: ' x ', getnext: true, attributes: {size: 3}},
    {name: 'height', attributes: {size: 3}},
    {name: 'alt', title: BUE.t('Alternative text'), empty: ''}
  ], BUE.t('Image'));
};

})(jQuery, BUE);
