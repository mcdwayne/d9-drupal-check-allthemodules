/**
 * @file
 * Ordered List form element.
 */

(function ($, Drupal) {

  function OrderedList(element) {
    this.element = $(element);
    this.class = 'selected';
    this.input = $('input', element);
    this.disabled = this.input.is(':disabled');
    this.controls = {
      select: $('a.control-select', element),
      deselect: $('a.control-deselect', element),
      moveUp: $('a.control-moveup', element),
      moveDown: $('a.control-movedown', element)
    };
    this.items = {
      available: $('.items-available ul', element),
      selected: $('.items-selected ul', element)
    };
    return element.orderedList = this.init();
  }

  OrderedList.prototype.init = function () {
    this.setDisabled(this.disabled);
    this.initClick();
    this.initSelection();
    this.initControls();
    return this;
  };

  OrderedList.prototype.initClick = function () {
    var self = this;
    this.items.selected.add(this.items.available).find('li').click(function () {
      self.disabled || $(this).toggleClass(self.class);
    });
  };

  OrderedList.prototype.initSelection = function () {
    var self = this
      , select = null
      , parent = null
      , first = null
      , state = null;
    var mouseDown = function (e) {
      e.preventDefault();
      if (!self.disabled) {
        select = !e.shiftKey && !e.metaKey;
        var $target = $(e.target);
        if ($target.is('li')) {
          parent = $target.parent()[0];
          first = e.target;
          state = $target.hasClass(self.class);
        }
        else {
          parent = e.target;
          first = state = null;
        }
        $(document).mousemove(mouseMove).mouseup(mouseUp);
      }
    };
    var mouseMove = function (e) {
      var $target = $(e.target);
      if ($target.is('li') && $target.parent()[0] === parent) {
        $target.toggleClass(self.class, select);
      }
    };
    var mouseUp = function (e) {
      e.target === first && $(first).toggleClass(self.class, state);
      $(this).unbind('mousemove', mouseMove).unbind('mouseup', mouseUp);
    };
    this.items.selected.add(this.items.available).mousedown(mouseDown);
  };

  OrderedList.prototype.initControls = function () {
    var self = this
      , controls = this.controls;
    this.initControlSelect(controls.select);
    this.initControlDeselect(controls.deselect);
    this.initControlMoveUp(controls.moveUp);
    this.initControlMoveDown(controls.moveDown);
    controls.select.add(controls.deselect).add(controls.moveUp).add(controls.moveDown).click(function () {
      if (!self.disabled) {
        var values = [];
        $('li', self.items.selected).each(function () {
          values.push($(this).data('value'));
        });
        self.input.val(values.join(','));
      }
    });
  };

  OrderedList.prototype.initControlSelect = function (control) {
    var self = this;
    control.click(function () {
      self.disabled || $('li.' + self.class, self.items.available)
        .removeClass(self.class).appendTo(self.items.selected);
      return false;
    });
  };

  OrderedList.prototype.initControlDeselect = function (control) {
    var self = this;
    control.click(function () {
      self.disabled || $('li.' + self.class, self.items.selected).each(function () {
        var item = $(this).removeClass(self.class).detach()
          , delta = item.data('delta');
        $('li', self.items.available).each(function () {
          if (delta < $(this).data('delta')) {
            item.insertBefore($(this));
            return false;
          }
        });
        item.parent().length || item.appendTo(self.items.available);
      });
      return false;
    });
  };

  OrderedList.prototype.initControlMoveUp = function (control) {
    var self = this;
    control.click(function () {
      self.disabled || $('li.' + self.class, self.items.selected).each(function () {
        var item = $(this);
        item.prevAll().not('.' + self.class).length && item.prev().insertAfter(item);
      });
      return false;
    });
  };

  OrderedList.prototype.initControlMoveDown = function (control) {
    var self = this;
    control.click(function () {
      self.disabled || $($('li.' + self.class, self.items.selected).get().reverse()).each(function () {
        var item = $(this);
        item.nextAll().not('.' + self.class).length && item.next().insertBefore(item);
      });
      return false;
    });
  };

  OrderedList.prototype.setDisabled = function (state) {
    this.disabled = !!state;
    this.element.toggleClass('form-disabled', this.disabled);
    this.input.prop('disabled', this.disabled);
  };

  OrderedList.prototype.disable = function () {
    this.setDisabled(true);
  };

  OrderedList.prototype.enable = function () {
    this.setDisabled(false);
  };

  Drupal.behaviors.orderedList = {
    attach: function () {
      var cls = 'ordered-list-processed';
      $('.form-type-ordered-list').not('.' + cls).addClass(cls).each(function () {
        new OrderedList(this);
      });
    }
  };

})(jQuery, Drupal);
