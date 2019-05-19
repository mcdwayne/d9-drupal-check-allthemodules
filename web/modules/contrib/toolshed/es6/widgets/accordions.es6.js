
Drupal.Toolshed = Drupal.Toolshed || {};

(($, Toolshed) => {
  /**
   * Accordion Object definition.
   */
  Toolshed.Accordion = class {
    /**
     * Create an accordion of expandable and collapsible elements.
     *
     * @param {jQuery} $elem
     *   jQuery wrapped element to turn into an accordion item.
     * @param {Object} configs
     *   A configuration options which determine the options:
     *   {
     *     // The selector to use when locating each of the accordion items.
     *     itemSelector: '.accordion-item'
     *     // The selector to find the element to toggle the collapse and expand.
     *     toggleSelector: '.accordion-item__toggle'
     *     // The selector to use to find the item body within the item context.
     *     bodySelector: '.accordion-item__body'
     *     // Accordion only has one item open at a time.
     *     exclusive: false,
     *     // Should accordion panels start of initially open.
     *     initOpen: false,
     *   }
     */
    constructor($elem, configs) {
      this.config = configs;
      this.elem = $elem;
      this.items = [];

      this.onClickItem = Toolshed.Accordion[configs.exclusive ? 'onClickExclusiveOpen' : 'onClickToggleOpen'];

      const children = $(configs.itemSelector, $elem);
      for (let i = 0; i < children.length; ++i) {
        const $childElem = $(children[i]);
        const $childBody = $(configs.bodySelector, $childElem);

        const item = new Toolshed.AccordionItem($childElem, $childBody, this);
        this.items.push(item);
        $(configs.toggleSelector, $childElem).click(this.onClickItem.bind(item));
      }

      // In either of these cases, most accordion items should appear as empty.
      if (configs.exclusive || !configs.initOpen) {
        // If exclusive and initally open, only open the first accordion item.
        for (let i = (configs.initOpen) ? 1 : 0; i < this.items.length; ++i) {
          this.items[i].hide();
        }
      }
    }

    static onClickToggleOpen(event) {
      event.preventDefault();

      if (this.isActive()) {
        this.deactivate();
      }
      else {
        this.activate();
      }
    }

    // Handle the click event
    static onClickExclusiveOpen(event) {
      event.preventDefault();

      if (this.isActive()) {
        this.deactivate();
      }
      else {
        const parentItems = this.accordion.items;

        // Only activate after the currently active item is closed.
        for (let i = 0; i < parentItems.length; ++i) {
          if (parentItems[i].isActive()) {
            parentItems[i].deactivate();
            break;
          }
        }

        this.activate();
      }
    }
  };

  /**
   * Accordion Item object definition.
   */
  Toolshed.AccordionItem = class {
    /**
     * Class representing a single expandable and collapsible item of
     * an accordion. This item maintains its components and the state
     * of the open and close states.
     *
     * @param {jQuery} $elem
     *   The whole accordion item.
     * @param {jQuery} $body
     *   The content area of the accordion.
     * @param {Drupal.Toolshed.Accordion} accordion
     *   Parent accordion instance.
     */
    constructor($elem, $body, accordion) {
      this.elem = $elem;
      this.body = $body;
      this.accordion = accordion;
    }

    isActive() {
      return !this.elem.hasClass('accordion-item--collapsed');
    }

    hide() {
      this.elem.addClass('accordion-item--collapsed');
      this.body.hide();
    }

    activate() {
      this.elem.removeClass('accordion-item--collapsed');
      this.body.slideDown(300);
    }

    deactivate() {
      this.elem.addClass('accordion-item--collapsed');
      this.body.slideUp(300);
    }

    destroy() {
      this.elem.removeClass('accordion-item--collapsed');
      this.body.show();
    }
  };
})(jQuery, Drupal.Toolshed);
