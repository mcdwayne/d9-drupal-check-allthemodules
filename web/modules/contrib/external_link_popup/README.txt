External Link Pop-up
================================
External Link Pop-up module provides pop-ups on external
link click. The module supports multiple and have tools to
allow some links don't show pop-ups.

The module uses `core/drupal.dialog` library, which is build on `jquery.ui.dialog`
library.

Configuration
================================
The configuration page is in "Administation -> Configuration -> Content Authoring"
menu. The path is `/admin/config/content/external_link_popup`.

Global configuration includes domains whitelist field. Whitelist configuration is described below
in "Excluding Links" section. Each of pop-ups has next options:

- "Show close icon" checkbox, check to chow close ([X]) icon in pop-up header.
- "Title" - fill with text displayed in pop-up title area.
- "Body" - pop-up main text.
- "Yes button" - text on button which allows user follow the link.
- "No button" - text on button which cancels jump and closes pop-up.
- "Domains" - white-space separated list of top-level domains
to show this pop-up. Domain 'domain.com' matches 'domain.com' and '*.domain.com'.

ATTENTION: Pop-up link target isn't configured in the module, pop-up uses
"target" attribute of the link to open link same/new window.

Excluding Links
================================
The module provides ability to exlude external domains from pop-up show. To do it
fill "Don't show pop-ups on domain" field in configuration with white-space
separated list of domains to excude. Each domain you defined also matches for it
subdomains, e.g. 'domain.com' matches 'domain.com' and '*.domain.com'.

The module provide ability to exclude specific link from pop-up show. Just
add to the link `external-link-popup-disabled` CSS class.

Theming
================================
The pop-ups are based on `jquery.ui.dialog` and uses JQuery UI styling. The module
pop-up has the next additional classes on a dialog wrapper:
- `external-link-popup` - global class for all dialogs,
- `external-link-popup-id-%id%` - class for particular pop-up, where %id% is it's machine name,
e.g. `external-link-popup-id-default` for default pop-up,
- `external-link-popup-body` - class for Body section inside of the pop-up content,
- `external-link-popup-subheader` - class for Subheader section inside of the pop-up content.

See jQuery UI styling framework for full information, here are some typical cases:
- Width. JQuery Dialog requires pop-up width on creation and it equeals to 85%
of document width for responsive purposes. You can control `min-width` and `max-width`
of the pop-up with CSS, e.g.
```css
.external-link-popup {
  min-width: 320px;
  max-width: 600px;
}
```
- Header. To style header use `.external-link-popup .ui-dialog-titlebar` CSS rule.
- Content. To style content use `.external-link-popup .ui-dialog-content` CSS rule.
- Buttons Area. To style the area use `.external-link-popup .ui-dialog-buttonpane`
CSS rule.
- Button. To style button use `.external-link-popup .ui-dialog-buttonpane .ui-button`
CSS rule. Use `:first-child` and `:last-child` pseudo-classes to style specific button.

References
================================
- http://api.jqueryui.com/dialog/ - JQuery UI Dialog documentation.
- http://api.jqueryui.com/theming/css-framework/ - JQuery UI styling framework.

Author/Maintainers
================================
Denis Rudoi <den.rudoi@gmail.com>

Supporting organizations/Sponsors
================================
EPAM Systems <https://www.epam.com/>
