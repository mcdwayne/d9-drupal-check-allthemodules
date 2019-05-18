(function (exports) {
'use strict';

/**
 * @file
 * Modify default Drupal tabledrag functionality
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Override Drupal's default, the only difference is,
   * that we pass a third arguments to the tableDrag constructor
   */
  Drupal.behaviors.tableDrag = {
    attach: function (context, settings) {
      function initTableDrag(table, base) {
        if (table.length) {
          // Create the new tableDrag instance. Save in the Drupal variable
          // to allow other scripts access to the object.
          Drupal.tableDrag[base] = new Drupal.tableDrag(table[0], settings.tableDrag[base], base);
        }
      }

      for (var base in settings.tableDrag) {
        if (settings.tableDrag.hasOwnProperty(base)) {
          initTableDrag($(context).find('#' + base).once('tabledrag'), base);
        }
      }
    }
  };

  // References to the original constructor and dragStart handler
  var TableDrag = Drupal.tableDrag;
  var _dragStart = TableDrag.prototype.dragStart;

  /**
   * Override Drupal's default constructor
   * We call the original, and replace the dragStart handler if it's the paragraph list
   */
  Drupal.tableDrag = function (table, tableSettings, base) {
    var tableDrag = new TableDrag(table, tableSettings);

    if (base.indexOf('parade-paragraphs-values' === 0)) {
      tableDrag.dragStart = dragStart;
    }

    return tableDrag;
  };

  /**
   * The modified dragStart handler, which prevents dragging if services are open
   * otherwise call the original handler
   */
	var dragStart = function() {
    var numberOfOpenSections = this.$table.find('.paragraphs-subform').length;

    if (numberOfOpenSections) {
      return;
    }

    _dragStart.apply(this, arguments);
	};
})(jQuery, Drupal);

}((this.LaravelElixirBundle = this.LaravelElixirBundle || {})));
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjpudWxsLCJzb3VyY2VzIjpbIkQ6L2RldmRlc2t0b3AvdGNzLmxvYy93ZWIvdGhlbWVzL3RpZXRvX2FkbWluL3NyYy9zY3JpcHRzL3RhYmxlZHJhZy5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyIvKipcbiAqIEBmaWxlXG4gKiBNb2RpZnkgZGVmYXVsdCBEcnVwYWwgdGFibGVkcmFnIGZ1bmN0aW9uYWxpdHlcbiAqL1xuXG4oZnVuY3Rpb24gKCQsIERydXBhbCkge1xuXG4gICd1c2Ugc3RyaWN0JztcblxuICAvKipcbiAgICogT3ZlcnJpZGUgRHJ1cGFsJ3MgZGVmYXVsdCwgdGhlIG9ubHkgZGlmZmVyZW5jZSBpcyxcbiAgICogdGhhdCB3ZSBwYXNzIGEgdGhpcmQgYXJndW1lbnRzIHRvIHRoZSB0YWJsZURyYWcgY29uc3RydWN0b3JcbiAgICovXG4gIERydXBhbC5iZWhhdmlvcnMudGFibGVEcmFnID0ge1xuICAgIGF0dGFjaDogZnVuY3Rpb24gKGNvbnRleHQsIHNldHRpbmdzKSB7XG4gICAgICBmdW5jdGlvbiBpbml0VGFibGVEcmFnKHRhYmxlLCBiYXNlKSB7XG4gICAgICAgIGlmICh0YWJsZS5sZW5ndGgpIHtcbiAgICAgICAgICAvLyBDcmVhdGUgdGhlIG5ldyB0YWJsZURyYWcgaW5zdGFuY2UuIFNhdmUgaW4gdGhlIERydXBhbCB2YXJpYWJsZVxuICAgICAgICAgIC8vIHRvIGFsbG93IG90aGVyIHNjcmlwdHMgYWNjZXNzIHRvIHRoZSBvYmplY3QuXG4gICAgICAgICAgRHJ1cGFsLnRhYmxlRHJhZ1tiYXNlXSA9IG5ldyBEcnVwYWwudGFibGVEcmFnKHRhYmxlWzBdLCBzZXR0aW5ncy50YWJsZURyYWdbYmFzZV0sIGJhc2UpO1xuICAgICAgICB9XG4gICAgICB9XG5cbiAgICAgIGZvciAodmFyIGJhc2UgaW4gc2V0dGluZ3MudGFibGVEcmFnKSB7XG4gICAgICAgIGlmIChzZXR0aW5ncy50YWJsZURyYWcuaGFzT3duUHJvcGVydHkoYmFzZSkpIHtcbiAgICAgICAgICBpbml0VGFibGVEcmFnKCQoY29udGV4dCkuZmluZCgnIycgKyBiYXNlKS5vbmNlKCd0YWJsZWRyYWcnKSwgYmFzZSk7XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9XG4gIH07XG5cbiAgLy8gUmVmZXJlbmNlcyB0byB0aGUgb3JpZ2luYWwgY29uc3RydWN0b3IgYW5kIGRyYWdTdGFydCBoYW5kbGVyXG4gIHZhciBUYWJsZURyYWcgPSBEcnVwYWwudGFibGVEcmFnO1xuICB2YXIgX2RyYWdTdGFydCA9IFRhYmxlRHJhZy5wcm90b3R5cGUuZHJhZ1N0YXJ0O1xuXG4gIC8qKlxuICAgKiBPdmVycmlkZSBEcnVwYWwncyBkZWZhdWx0IGNvbnN0cnVjdG9yXG4gICAqIFdlIGNhbGwgdGhlIG9yaWdpbmFsLCBhbmQgcmVwbGFjZSB0aGUgZHJhZ1N0YXJ0IGhhbmRsZXIgaWYgaXQncyB0aGUgcGFyYWdyYXBoIGxpc3RcbiAgICovXG4gIERydXBhbC50YWJsZURyYWcgPSBmdW5jdGlvbiAodGFibGUsIHRhYmxlU2V0dGluZ3MsIGJhc2UpIHtcbiAgICB2YXIgdGFibGVEcmFnID0gbmV3IFRhYmxlRHJhZyh0YWJsZSwgdGFibGVTZXR0aW5ncyk7XG5cbiAgICBpZiAoYmFzZS5pbmRleE9mKCdmaWVsZC1wYXJhZ3JhcGhzLXZhbHVlcycgPT09IDApKSB7XG4gICAgICB0YWJsZURyYWcuZHJhZ1N0YXJ0ID0gZHJhZ1N0YXJ0O1xuICAgIH1cblxuICAgIHJldHVybiB0YWJsZURyYWc7XG4gIH07XG5cbiAgLyoqXG4gICAqIFRoZSBtb2RpZmllZCBkcmFnU3RhcnQgaGFuZGxlciwgd2hpY2ggcHJldmVudHMgZHJhZ2dpbmcgaWYgc2VydmljZXMgYXJlIG9wZW5cbiAgICogb3RoZXJ3aXNlIGNhbGwgdGhlIG9yaWdpbmFsIGhhbmRsZXJcbiAgICovXG5cdHZhciBkcmFnU3RhcnQgPSBmdW5jdGlvbigpIHtcbiAgICB2YXIgbnVtYmVyT2ZPcGVuU2VjdGlvbnMgPSB0aGlzLiR0YWJsZS5maW5kKCcucGFyYWdyYXBocy1zdWJmb3JtJykubGVuZ3RoO1xuXG4gICAgaWYgKG51bWJlck9mT3BlblNlY3Rpb25zKSB7XG4gICAgICByZXR1cm47XG4gICAgfVxuXG4gICAgX2RyYWdTdGFydC5hcHBseSh0aGlzLCBhcmd1bWVudHMpO1xuXHR9O1xufSkoalF1ZXJ5LCBEcnVwYWwpOyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7QUFBQTs7Ozs7QUFLQSxDQUFDLFVBQVUsQ0FBQyxFQUFFLE1BQU0sRUFBRTs7RUFFcEIsWUFBWSxDQUFDOzs7Ozs7RUFNYixNQUFNLENBQUMsU0FBUyxDQUFDLFNBQVMsR0FBRztJQUMzQixNQUFNLEVBQUUsVUFBVSxPQUFPLEVBQUUsUUFBUSxFQUFFO01BQ25DLFNBQVMsYUFBYSxDQUFDLEtBQUssRUFBRSxJQUFJLEVBQUU7UUFDbEMsSUFBSSxLQUFLLENBQUMsTUFBTSxFQUFFOzs7VUFHaEIsTUFBTSxDQUFDLFNBQVMsQ0FBQyxJQUFJLENBQUMsR0FBRyxJQUFJLE1BQU0sQ0FBQyxTQUFTLENBQUMsS0FBSyxDQUFDLENBQUMsQ0FBQyxFQUFFLFFBQVEsQ0FBQyxTQUFTLENBQUMsSUFBSSxDQUFDLEVBQUUsSUFBSSxDQUFDLENBQUM7U0FDekY7T0FDRjs7TUFFRCxLQUFLLElBQUksSUFBSSxJQUFJLFFBQVEsQ0FBQyxTQUFTLEVBQUU7UUFDbkMsSUFBSSxRQUFRLENBQUMsU0FBUyxDQUFDLGNBQWMsQ0FBQyxJQUFJLENBQUMsRUFBRTtVQUMzQyxhQUFhLENBQUMsQ0FBQyxDQUFDLE9BQU8sQ0FBQyxDQUFDLElBQUksQ0FBQyxHQUFHLEdBQUcsSUFBSSxDQUFDLENBQUMsSUFBSSxDQUFDLFdBQVcsQ0FBQyxFQUFFLElBQUksQ0FBQyxDQUFDO1NBQ3BFO09BQ0Y7S0FDRjtHQUNGLENBQUM7OztFQUdGLElBQUksU0FBUyxHQUFHLE1BQU0sQ0FBQyxTQUFTLENBQUM7RUFDakMsSUFBSSxVQUFVLEdBQUcsU0FBUyxDQUFDLFNBQVMsQ0FBQyxTQUFTLENBQUM7Ozs7OztFQU0vQyxNQUFNLENBQUMsU0FBUyxHQUFHLFVBQVUsS0FBSyxFQUFFLGFBQWEsRUFBRSxJQUFJLEVBQUU7SUFDdkQsSUFBSSxTQUFTLEdBQUcsSUFBSSxTQUFTLENBQUMsS0FBSyxFQUFFLGFBQWEsQ0FBQyxDQUFDOztJQUVwRCxJQUFJLElBQUksQ0FBQyxPQUFPLENBQUMseUJBQXlCLEtBQUssQ0FBQyxDQUFDLEVBQUU7TUFDakQsU0FBUyxDQUFDLFNBQVMsR0FBRyxTQUFTLENBQUM7S0FDakM7O0lBRUQsT0FBTyxTQUFTLENBQUM7R0FDbEIsQ0FBQzs7Ozs7O0NBTUgsSUFBSSxTQUFTLEdBQUcsV0FBVztJQUN4QixJQUFJLG9CQUFvQixHQUFHLElBQUksQ0FBQyxNQUFNLENBQUMsSUFBSSxDQUFDLHFCQUFxQixDQUFDLENBQUMsTUFBTSxDQUFDOztJQUUxRSxJQUFJLG9CQUFvQixFQUFFO01BQ3hCLE9BQU87S0FDUjs7SUFFRCxVQUFVLENBQUMsS0FBSyxDQUFDLElBQUksRUFBRSxTQUFTLENBQUMsQ0FBQztFQUNwQyxDQUFDO0NBQ0YsQ0FBQyxDQUFDLE1BQU0sRUFBRSxNQUFNLENBQUM7OyJ9