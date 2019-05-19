/**
 * @file
 * Share content using embed markup.
 */

(($, { behaviors }, { socialHub }) => {
  behaviors.socialHubShareEmbed = {
    attach() {
      const { instances } = socialHub;
      for (let i = 0; i < instances.length; i++) {
        $(instances[i])
          .once('social_hub--share__embed')
          .each((j, x) => {
            const $elem = $(x);
            $elem.on('click', this.toggleEmbed);
            $(`[data-referenced-by="${$elem.attr('id')}"]`)
              .once('social_hub--clip_it')
              .each((k, y) => {
                $(y).clickToCopy();
              });
          });
      }
    },
    /**
     * Toggle embed element.
     *
     * @param {Event} e Event instance.
     */
    toggleEmbed(e) {
      const $this = $(e.target);
      const $embed = $(`[data-referenced-by="${$this.attr('id')}"]`);
      $embed.toggleClass('element-invisible');
      $this.toggleClass('active');
    },
  };
})(jQuery, Drupal, drupalSettings);
