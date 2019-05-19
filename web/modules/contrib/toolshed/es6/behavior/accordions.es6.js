
(($, Toolshed) => {
  Drupal.behaviors.toolshedAccordions = {
    accordions: [],

    attach(context, settings) {
      $('.use-accordion', context).once('accordion').each((i, accordion) => {
        this.accordions.push(new Toolshed.Accordion($(accordion), {
          ...settings.Toolshed.accordions,
          exclusive: true,
          initOpen: false,
        }));
      });
    },
  };
})(jQuery, Drupal.Toolshed);
