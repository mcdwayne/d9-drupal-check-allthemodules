/*
 Two columns
 */

(function($) {
  SirTrevor.Blocks.TwoColumns = (function() {

    return SirTrevor.Block.extend({

      type: "two_columns",
      icon_name: 'columns',
      multi_editable: true,
      editorHTML: '',
      columnEditorHTML: "<div class='st-block__editor'></div>",
      textable: true,

      scribeOptions: {
        allowBlockElements: true,
        tags: {
          p: true,
          h1: true,
          blockquote: true
        }
      },

      configureScribe: function(scribe) {
        scribe.use(scribeQuotePlugin());
        scribe.use(scribeHeadingPlugin(1, 'heading'));
      },

      initialize: function() {
        this.editorIds = [];
      },

      loadData: function(data) {
        var block = this;

        if (data.columns.length) {
          data.columns.forEach(function(column) {
            block.addColumnItem(column.content);
          });
        } else {
          this.onBlockRender();
        }
      },

      addColumnItem: function(content) {
        content = content || '';
        if (content.trim() === "<br>") { content = ''; }

        var editor = this.newTextEditor(this.columnEditorHTML, content);

        this.inner.appendChild(editor.node);
        this.editorIds.push(editor.id);

        !content && this.focusOn(editor);
      },

      focusOn: function(editor) {
        var scribe = editor.scribe;
        var selection = new scribe.api.Selection();
        var lastChild = scribe.el.lastChild;
        var range;
        if (selection.range) {
          range = selection.range.cloneRange();
        }

        editor.el.focus();

        if (range && lastChild) {
          range.setStartAfter(lastChild, 1);
          range.collapse(false);
        }
      },

      focusOnFirst: function() {
        this.focusOn(this.getTextEditor(this.editorIds[0]));
      },

      onBlockRender: function() {
        while (this.editorIds.length < 2) {
          this.addColumnItem();
        }
        this.focusOnFirst();
      },

      _serializeData: function() {
        var data = {format: 'html', columns: []};

        this.editorIds.forEach(function(editorId) {
          var column = {content: this.getTextEditor(editorId).scribe.getContent()};
          data.columns.push(column);
        }.bind(this));

        return data;
      }

    });

  })();
})(jQuery);