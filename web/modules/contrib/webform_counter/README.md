To add a Webform Counter to a webform:

- Go to webform's "Settings > Third party settings > Submissions Counter" and fill in the settings
- Add a new "Advanced HTML/Text" element to webform using "Full HTML" text format.
- Add one of these tokens: `[site:webform-counter:?]` or `[site:webform-counter-progress:?]` (replace `?` with the webform machine name, for example `[site:webform-counter-progress:my_webform]`)

The tokens can be used in any other place supporting tokens.
