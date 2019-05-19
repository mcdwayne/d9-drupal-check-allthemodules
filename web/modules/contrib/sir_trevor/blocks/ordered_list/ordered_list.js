/*
 Ordered List
 */
(function($) {
  SirTrevor.Blocks.OrderedList = (function() {

    return SirTrevor.Blocks.List.extend({

      type: "ordered_list",
      icon_name: 'ordered_list',
      editorHTML: '<ol class="st-list-block__list"></ol>',

      setupListVariables: function() {
        this.ul = this.inner.querySelector('ol');
      }
    });

  })();
})(jQuery);