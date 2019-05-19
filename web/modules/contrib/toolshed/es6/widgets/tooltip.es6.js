
Drupal.Toolshed = Drupal.Toolshed || {};

(($, Toolshed) => {
  Toolshed.Tooltip = class {
    constructor(element, trigger, settings = {}) {
      this.settings = {
        event: 'click', // What action triggers the tooltip to be visible.
        position: 'absolute', // Position the tooltip relative or absolute.
        exclusive: true, // Only one exclusive tooltip open at a time.
        closeOnClickOff: true, // Close tip if user clicks else where.
        attachTo: element, // Where do we put the tooltip trigger?
        ...settings,
      };

      this.trigger = trigger;
      if (!(this.trigger && this.trigger.length)) {
        this.trigger = $('<a class="toolshed-tip__trigger" title="Click for help text." href="#">?</a>')
          .insertAfter(this.attachTo);
      }
      else if (!this.trigger.hasClass('toolshed-tip__trigger')) {
        this.trigger.addClass('toolshed-top__trigger');
      }

      this.element = element.wrap('<div class="toolshed-tip__content">').parent().hide();
      this.trigger.on(this.event, this, this.onToggle);

      // Move the scope of the tooltip help to the general document.
      if (this.settings.position === 'absolute') {
        $('body').append(this.element);
      }
    }

    adjustPosition() {
      if (!this.offsetTop || !this.offsetLeft) {
        this.offsetTop = (this.trigger.outerHeight() / 2) - 16;
        this.offsetLeft = this.trigger.outerWidth() + 32;
      }

      const pos = this.settings.position === 'absolute' ? this.trigger.offset() : this.trigger.position();
      pos.top += this.offsetTop;
      pos.left += this.offsetLeft;
      this.element.css(pos);
    }

    show() {
      if (this.exclusive) {
        if (Drupal.Toolshed.activeTooltip) {
          Drupal.Toolshed.activeTooltip.close();
        }

        // Mark this tooltip as the active tooltip.
        Drupal.Toolshed.activeTooltip = this;
      }

      this.trigger.addClass('tip--active');
      this.adjustPosition();
      this.element.show();

      // Close if user clicks somewhere else.
      $('body').on('click', this, this.onClose);
    }

    close() {
      if (this.element.is(':visible')) {
        this.element.hide();
        this.trigger.removeClass('tip--active');

        // If marked as active, remove it from the closed link.
        if (this === Drupal.Toolshed.activeTooltip) {
          Drupal.Toolshed.activeTooltip = null;
        }
      }
    }

    // --------------------
    // Handle events
    static onToggle(event) {
      const tooltip = event.data;
      event.preventDefault();
      event.stopImmediatePropagation();

      tooltip[tooltip.element.is(':visible') ? 'close' : 'show']();
    }

    static onClose(event) {
      const tooltip = event.data;

      tooltip.close();
      $('body').off('click', tooltip.close);
    }
  };
})(jQuery, Drupal.Toolshed);
