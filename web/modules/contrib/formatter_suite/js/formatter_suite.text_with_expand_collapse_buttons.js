/**
 * @file
 * Text with expand/collapse buttons behavior.
 *
 * The DOM contains a <div> that wraps additional <div>s for a text field of
 * arbitrary length, and expand and collapse buttons.
 *
 * On page load:
 *
 * * If the formatted field is malformed or lacks needed attributes, the
 *   full text is shown and the buttons hidden.
 *
 * * Otherwise use the formatted field's collapsed height to shrink the
 *   text area and add an expand button that, when clicked, expands the
 *   text area to full height and shows a collapse button. When that button
 *   is clicked, the text area shrinks back down and shows the expand button.
 *
 * * Optionally, the expand and collapse can be animated.
 */
(($, Drupal) => {
  /**
   * Initialize the text display and attach button behaviors.
   *
   * All marked text blocks are found and processed. For each one, the
   * initial display shows the text block at its full height, including
   * the effects of content, styling, and page space constraints. This
   * function gets that height, and the configured collapsed height,
   * then collapses the text to that height, shows an expansion button,
   * and attaches behaviors to expand and later collapse the text block.
   *
   * If the collapse height is not set, or if the fully expanded height
   * is less than or equal to the collapse height, then the text block
   * is shown at full height and no buttons are shown and no behaviors
   * are installed.
   *
   * Initially, the expand and collapse buttons are both hidden.
   */
  Drupal.behaviors.formatter_suite_text_with_expand_collapse_buttons = {
    attach(pageContext) {
      $(".formatter_suite-text-with-expand-collapse-buttons", pageContext)
        .once("formatter_suite-text-with-expand-collapse-buttons")
        .each((index, value) => {
          // Get handles on all elements that should be children, and get
          // all text block data values that should be there.
          const $textBlock = $(".formatter_suite-text", $(value));
          const $expandButton = $(
            ".formatter_suite-text-expand-button",
            $(value)
          );
          const $collapseButton = $(
            ".formatter_suite-text-collapse-button",
            $(value)
          );

          if (
            $textBlock.length === 0 ||
            $expandButton.length === 0 ||
            $collapseButton.length === 0
          ) {
            // Abort. Missing essential elements. Show full text.
            return;
          }

          const collapsedHeight = $textBlock.attr(
            "data-formatter_suite-collapsed-height"
          );
          let animationDuration = $textBlock.attr(
            "data-formatter_suite-animation-duration"
          );

          if (
            typeof collapsedHeight === "undefined" ||
            collapsedHeight === ""
          ) {
            // Abort. Missing essential setting. Show full text.
            return;
          }

          if (
            typeof animationDuration === "undefined" ||
            animationDuration === ""
          ) {
            animationDuration = 0;
          } else {
            animationDuration = parseInt(animationDuration, 10);
          }

          // Get the full height, in pixels.
          const expandedHeightPx = $textBlock.outerHeight(false);

          // Set the full height to the collapsed height, in pixels.
          $textBlock.height(collapsedHeight);

          // Get the collapsed height, in pixels.
          const collapsedHeightPx = $textBlock.height();

          if (expandedHeightPx <= collapsedHeightPx) {
            // Abort. Text is short, so no point in buttons.
            return;
          }

          // Add button behaviors, without and with animation.
          if (animationDuration === 0) {
            // No animation.
            $expandButton.click(() => {
              $textBlock.height(expandedHeightPx);
              $expandButton.hide();
              $collapseButton.show();
            });

            $collapseButton.click(() => {
              $textBlock.height(collapsedHeightPx);
              $expandButton.show();
              $collapseButton.hide();
            });
          } else {
            // With animation.
            //
            // On expand click, animate then hide button and show collapse.
            const hideshow = () => {
              $expandButton.hide();
              $collapseButton.show();
            };
            $expandButton.click(() => {
              $textBlock.animate(
                {
                  height: expandedHeightPx
                },
                animationDuration,
                hideshow
              );
            });

            // On collapse click, animate then hide button and show expand.
            const showhide = () => {
              $expandButton.show();
              $collapseButton.hide();
            };
            $collapseButton.click(() => {
              $textBlock.animate(
                {
                  height: collapsedHeightPx
                },
                animationDuration,
                showhide
              );
            });
          }

          $expandButton.show();
          $collapseButton.hide();
        });
    }
  };
})(jQuery, Drupal);
