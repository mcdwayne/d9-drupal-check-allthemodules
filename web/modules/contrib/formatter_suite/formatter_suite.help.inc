<?php

/**
 * @file
 * Implements help hooks for the module.
 */

use Drupal\Core\Routing\RouteMatchInterface;

use Drupal\formatter_suite\Branding;

/**
 * Implements hook_help().
 *
 * This function responds to the help route for the Formatter Suite module
 * and provides a single page with multiple sections to document the
 * purpose and uses of the module.
 */
function formatter_suite_help(
  string $routeName,
  RouteMatchInterface $routeMatch) {
  //
  // The function returns a render array containing multiple help sections,
  // partly mandated by Drupal best practices.
  //
  // Check the route.
  if ($routeName !== 'help.page.formatter_suite') {
    // The route is not part of our help page. Do nothing.
    return;
  }

  // Get module information.
  $moduleTitle = \Drupal::moduleHandler()->getName('formatter_suite');
  $imagePath = '/' . drupal_get_path('module', 'formatter_suite') . '/images/';

  //
  // About
  // -----
  // This section is recommended by Drupal best practices.
  $help = '';
  $help .= '<h4>' . t('About') . '</h4>';
  $help .= '<p>' . t(
    '<strong>@moduleTitle</strong> provides a suite of field formatters to help present numbers, dates, times, text, links, entity references, files, and images. This module contains the following formatters, organized by the field types they support.',
    [
      '@moduleTitle' => $moduleTitle,
    ]) . '</p>';

  $help .= <<<EOS
<ul class="formatter_suite-toc">
  <li>Date & time<ul>
    <li><a href="#DateTimeList">Date & time list</a></li>
    <li><a href="#DateTimeCustomList">Custom date & time list</a></li>
    <li><a href="#DateTimeTimeAgoList">Time ago list</a></li>
  </ul></li>
  <li>Email<ul>
    <li><a href="#GeneralEmail">General email address</a></li>
  </ul></li>
  <li>Entity reference<ul>
    <li><a href="#GeneralEntityReference">General entity reference</a></li>
    <li><a href="#EntityReferenceRenderList">Rendered entity list</a></li>
  </ul></li>
  <li>Files<ul>
    <li><a href="#GeneralFileLink">General file link</a></li>
  </ul></li>
  <li>Images<ul>
    <li><a href="#GeneralImage">General image</a></li>
    <li><a href="#ImageEmbedData">Image with embeded data URL</a></li>
  </ul></li>
  <li>Links<ul>
    <li><a href="#GeneralLink">General link</a></li>
  </ul></li>
  <li>Numbers (Decimal, Float, and Integer)<ul>
    <li><a href="#NumberWithBytes">Bytes with KB/MB/GB suffix</a></li>
    <li><a href="#GeneralNumber">General number</a></li>
    <li><a href="#GeneralNumberWithBarIndicator">General number with bar indicator</a></li>
    <li><a href="#GeneralNumberWithMinMax">General number with min/max</a></li>
  </ul></li>
  <li>Text (String long, Text, Text long, and Text with Summary)<ul>
    <li><a href="#TextWithExpandCollapseButtons">Text with expand/collapse buttons</a></li>
  </ul></li>
  <li>Timestamp<ul>
    <li><a href="#TimestampList">Timestamp list</a></li>
    <li><a href="#TimestampTimeAgoList">Time ago list</a></li>
  </ul></li>
</ul>
EOS;

  //
  // Uses
  // ----
  // This section is recommended by Drupal best practices.
  $help .= '<h4>' . t('Uses') . '</h4>';
  $help .= '<p>' . t(
    "Web site adminstrators may use the <strong>@moduleTitle</strong> module, and its submodules, along with the core <strong>Field UI</strong> and <strong>View UI</strong> modules, to configure the way in which field data is presented.",
    [
      '@moduleTitle' => $moduleTitle,
    ]) . '</p>';
  $help .= '<p>' . t(
    "Collectively, the <strong>@moduleTitle</strong> modules offer a rich set of features that go beyond core formatters to provide detailed control over number notation styles, email address links, dates and times, images, files, and more. Special formatters add graphic indicators, large text collapse & expand, and more.",
    [
      '@moduleTitle' => $moduleTitle,
    ]) . '</p>';

  //
  // Formatters
  // ----------
  // List the formatters.
  //
  // Datetime.
  $help .= '<h4>' . t('Formatters for dates and times') . '</h4>';
  $help .= '<dl class="formatter_suite-formatter-list">';

  // -------------------------------------------------------------------.
  $help .= '<a name="DateTimeList"></a>';
  $help .= '<dt>' . t('Date & time list') . '</dt>';
  $image = '<img src="' . $imagePath . 'DateTimeList.png">';
  $help .= '<dd><em>' . $image . t('Supports: Any Date & Time field.') . '</em></dd>';
  $help .= '<dd>' . t("Settings select the time zone and one of the site's configured date formats.") . '</dd>';
  $help .= '<dd>' . t("For multi-value fields, additional settings select whether to show values side-by-side on a single line, or consecutively in numbered, bulleted, or non-bulleted blocks. When shown on a single line, an optional separator may be used.") . '</dd>';

  // -------------------------------------------------------------------.
  $help .= '<a name="DateTimeCustomList"></a>';
  $help .= '<dt>' . t('Custom date & time list') . '</dt>';
  $image = '<img src="' . $imagePath . 'DateTimeCustomList.png">';
  $help .= '<dd><em>' . $image . t('Supports: Any Date & Time field.') . '</em></dd>';
  $help .= '<dd>' . t("Settings select the time zone and a custom date and time format based upon PHP's format codes.") . '</dd>';
  $help .= '<dd>' . t("For multi-value fields, additional settings select whether to show values side-by-side on a single line, or consecutively in numbered, bulleted, or non-bulleted blocks. When shown on a single line, an optional separator may be used.") . '</dd>';

  // -------------------------------------------------------------------.
  $help .= '<a name="DateTimeTimeAgoCustomList"></a>';
  $help .= '<dt>' . t('Time ago list') . '</dt>';
  $image = '<img src="' . $imagePath . 'DateTimeTimeAgoList.png">';
  $help .= '<dd><em>' . $image . t('Supports: Any Date & Time field.') . '</em></dd>';
  $help .= '<dd>' . t("Settings select how to annotate times that are in the future or the past, and the amount of detail to show.") . '</dd>';
  $help .= '<dd>' . t("For multi-value fields, additional settings select whether to show values side-by-side on a single line, or consecutively in numbered, bulleted, or non-bulleted blocks. When shown on a single line, an optional separator may be used.") . '</dd>';

  $help .= '</dl>';

  //
  // Email address.
  $help .= '<h4>' . t('Formatters for email addresses') . '</h4>';
  $help .= '<dl class="formatter_suite-formatter-list">';

  // -------------------------------------------------------------------.
  $help .= '<a name="GeneralEmail"></a>';
  $help .= '<dt>' . t('General email address') . '</dt>';
  $image = '<img src="' . $imagePath . 'GeneralEmail.png">';
  $help .= '<dd><em>' . $image . t('Supports: Any Email field.') . '</em></dd>';
  $help .= '<dd>' . t('Settings select whether to display an email address as plain text, as the address with a "mailto" link, or as a mailto link with custom text, such as "Email me" or "Contact us". Mailto links typically cause a web browser to start up the user\'s email application when clicked.') . '</dd>';
  $help .= '<dd>' . t("For multi-value fields, additional settings select whether to show values side-by-side on a single line, or consecutively in numbered, bulleted, or non-bulleted blocks. When shown on a single line, an optional separator may be used.") . '</dd>';

  $help .= '</dl>';

  // Entity references.
  $help .= '<h4>' . t('Formatters for entity references') . '</h4>';
  $help .= '<dl class="formatter_suite-formatter-list">';

  // -------------------------------------------------------------------.
  $help .= '<a name="GeneralEntityReference"></a>';
  $help .= '<dt>' . t('General entity reference') . '</dt>';
  $image = '<img src="' . $imagePath . 'GeneralEntityReference.png">';
  $help .= '<dd><em>' . $image . t('Supports: Any Entity Reference field, including for Files, Images, Taxonomy terms, and Users.') . '</em></dd>';
  $help .= '<dd>' . t('A general-purpose formatter for entity reference fields for entities of any type, including those for files, images, taxonomy terms, and user accounts.') . '</dd>';
  $help .= '<dd>' . t("Settings select whether to display the referenced entity's ID, title, or custom text, and how to link to the entity's content. HTML5 link features are available to cause the link to open in a new tab/window or trigger a file download, and to mark the linked content using standard semantic values, such as author, help, or license information.") . '</dd>';

  // -------------------------------------------------------------------.
  $help .= '<a name="EntityReferenceRenderList"></a>';
  $help .= '<dt>' . t('Rendered entity list') . '</dt>';
  $image = '<img src="' . $imagePath . 'EntityReferenceRenderList.png">';
  $help .= '<dd><em>' . $image . t('Supports: Any Entity Reference field, including for Files, Images, Users, and Taxonomy terms.') . '</em></dd>';
  $help .= '<dd>' . t('A formatter based on the core <strong>Render</strong> entity reference formatter that also supports formatting multi-value fields as a list.') . '</dd>';
  $help .= '<dd>' . t('Settings include all settings from the <strong>Render</strong> formatter, plus those to select a list style and optional list separator, like a comma. List styles include numbered, bulleted, and unbulleted multi-line lists, and a single line list.') . '</dd>';

  $help .= '</dl>';

  // Files.
  $help .= '<h4>' . t('Formatters for files') . '</h4>';
  $help .= '<dl class="formatter_suite-formatter-list">';

  // -------------------------------------------------------------------.
  $help .= '<a name="GeneralFileLink"></a>';
  $help .= '<dt>' . t('General file link') . '</dt>';
  $image = '<img src="' . $imagePath . 'GeneralFileLink.png">';
  $help .= '<dd><em>' . $image . t('Supports: Any File field.') . '</em></dd>';
  $help .= '<dd>' . t("Settings select whether to show the file's name, file field description, or custom text as a file link title. The title may include the file's size and a MIME-type-based file icon provided by most themes.") . '</dd>';
  $help .= '<dd>' . t("Links can be set to cause a browser to open the linked file in a new tab/window or trigger a file download. For search engines, links can be marked using standard semantic values, such as author, help, or license information.") . '</dd>';
  $help .= '<dd>' . t("For multi-value fields, additional settings select whether to show values side-by-side on a single line, or consecutively in numbered, bulleted, or non-bulleted blocks. When shown on a single line, an optional separator may be used.") . '</dd>';

  $help .= '</dl>';

  // Images.
  $help .= '<h4>' . t('Formatters for images') . '</h4>';
  $help .= '<dl class="formatter_suite-formatter-list">';

  // -------------------------------------------------------------------.
  $help .= '<a name="GeneralImage"></a>';
  $help .= '<dt>' . t('General image') . '</dt>';
  $image = '<img src="' . $imagePath . 'GeneralImage.png">';
  $help .= '<dd><em>' . $image . t('Supports: Any Image field.') . '</em></dd>';
  $help .= '<dd>' . t("Settings select the image style to use, whether to include a link to the image file or the parent entity, and an optional caption above or below the image.") . '</dd>';
  $help .= '<dd>' . t("Links can be set to cause a browser to open the linked item in a new tab/window or trigger a download. Captions can include the title from the image field, the image file name, the file's size, the image's dimensions, and the image's MIME type.") . '</dd>';

  // -------------------------------------------------------------------.
  $help .= '<a name="ImageEmbedData"></a>';
  $help .= '<dt>' . t('Image with embedded data URL') . '</dt>';
  $image = '<img src="' . $imagePath . 'ImageEmbedData.png">';
  $help .= '<dd><em>' . $image . t('Supports: Any Image field.') . '</em></dd>';
  $help .= '<dd>' . t("This formatter helps when presenting thumbnail images by embeding them directly within the page using a data URL instead of a file URL. This can improve page loading times by reducing the number of files a browser has to request before it can present a page.") . '</dd>';
  $help .= '<dd>' . t("Settings select the image style to use and whether to include a link to the image file or the parent entity. Additional settings select the maximum image width and height for conversion into a data URL.") . '</dd>';

  $help .= '</dl>';

  // Links.
  $help .= '<h4>' . t('Formatters for links') . '</h4>';
  $help .= '<dl class="formatter_suite-formatter-list">';

  // -------------------------------------------------------------------.
  $help .= '<a name="GeneralLink"></a>';
  $help .= '<dt>' . t('General link') . '</dt>';
  $image = '<img src="' . $imagePath . 'GeneralLink.png">';
  $help .= '<dd><em>' . $image . t('Supports: Any link field.') . '</em></dd>';
  $help .= '<dd>' . t("Settings select whether to show the link field's title, URL, or custom text, and how to link to the URL's content.") . '</dd>';
  $help .= '<dd>' . t("Links can be set to cause a browser to open the linked item in a new tab/window or trigger a download. For search engines, links can be marked using standard semantic values, such as author, help, or license information. When the link field supports external links, 'noreferrer', 'noopener', and 'nofollow' attributes can be added.") . '</dd>';
  $help .= '<dd>' . t("For multi-value fields, additional settings select whether to show values side-by-side on a single line, or consecutively in numbered, bulleted, or non-bulleted blocks. When shown on a single line, an optional separator may be used.") . '</dd>';

  $help .= '</dl>';

  // Numeric.
  $help .= '<h4>' . t('Formatters for numbers') . '</h4>';
  $help .= '<dl class="formatter_suite-formatter-list">';

  // -------------------------------------------------------------------.
  $help .= '<a name="NumberWithBytes"></a>';
  $help .= '<dt>' . t('Bytes with KB/MB/GB suffix') . '</dt>';
  $image = '<img src="' . $imagePath . 'NumberWithBytes.png">';
  $help .= '<dd><em>' . $image . t('Supports: Any Decimal, Float, or Integer field.') . '</em></dd>';
  $help .= '<dd>' . t('Settings select whether a field\'s numeric values, in bytes, should be summarized using the international standard for <em>Kilobytes</em> (1000 bytes = 1 KB) or legacy <em>Kibibytes</em> (1024 bytes = 1 KiB). Values may be expressed with a specified number of decimal digits, and suffixes may use abbreviations, like "KB", or full words, like "Kilobyte".') . '</dd>';

  // -------------------------------------------------------------------.
  $help .= '<a name="GeneralNumber"></a>';
  $help .= '<dt>' . t('General number') . '</dt>';
  $image = '<img src="' . $imagePath . 'GeneralNumber.png">';
  $help .= '<dd><em>' . $image . t('Supports: Any Decimal, Float, or Integer field.') . '</em></dd>';
  $help .= '<dd>' . t('This formatter presents numeric values in a variety of styles, similar to those available for cells in a spreadsheet.') . '</dd>';
  $help .= '<ul>';
  $help .= '<li>' . t('<em>Basic number:</em> Formats numbers using basic settings controlling the number of decimal places and whether a thousands separator (,) is included.') . '</li>';
  $help .= '<li>' . t('<em>General number:</em> Formats numbers with detailed settings controlling the number of decimal places, the decimal separator, whether a thousands separator is included, the thousands separator, whether to pad with zeroes, the padding width, whether to show the plus sign on positive values, and whether to show the minus sign or parenthesis on negative values.') . '</li>';
  $help .= '<li>' . t('<em>Numeral system:</em> Formats numbers using a selected base, such as base 2 for binary, 8 for octal, or 16 for hex. Settings control the base and optional zero padding.') . '</li>';
  $help .= '<li>' . t('<em>Percentage:</em> Formats numbers as percentages by multiplying them by 100 and adding a percentage sign (%). Settings control the number of decimal places and whether a thousands separator (,) is included.') . '</li>';
  $help .= '<li>' . t('<em>Scientific:</em> Formats numbers using scientific notation and settings controlling the exponent style (E-notation or superscript), the number of decimal places, and whether a thousands separator (,) is included.') . '</li>';
  $help .= '</ul></dd>';
  $help .= '<dd>' . t("All general number notation styles support including the field's prefix and suffix before and after the value. The prefix is often used for currency symbols, while the suffix is often used for units of measure.") . '</dd>';
  $help .= '<dd>' . t("For multi-value fields, additional settings select whether to show values side-by-side on a single line, or consecutively in numbered, bulleted, or non-bulleted blocks. When shown on a single line, an optional separator may be used.") . '</dd>';

  // -------------------------------------------------------------------.
  $help .= '<a name="GeneralNumberWithBarIndicator"></a>';
  $help .= '<dt>' . t('General number with bar indicator') . '</dt>';
  $image = '<img src="' . $imagePath . 'GeneralNumberWithBarIndicator.png">';
  $help .= '<dd><em>' . $image . t('Supports: Any Decimal, Float, or Integer field.') . '</em></dd>';
  $help .= '<dd>' . t("Settings include all settings from the parent <strong>General number</strong> formatter, plus those to create a colored horizontal bar indicator beside the number. The bar's length varies based upon the field's value and its distance between the field's minimum and maximum values. Formatter settings select a maximum bar length, a bar width, and the bar and background color. The field's value can be shown before or after the bar, or omitted.") . '</dd>';
  $help .= '<dd>' . t("For multi-value fields, additional settings select whether to show values and bars side-by-side on a single line, or consecutively in numbered, bulleted, or non-bulleted blocks. When shown on a single line, an optional separator may be used.") . '</dd>';

  // -------------------------------------------------------------------.
  $help .= '<a name="GeneralNumberWithMinMax"></a>';
  $help .= '<dt>' . t('General number with min/max') . '</dt>';
  $image = '<img src="' . $imagePath . 'GeneralNumberWithMinMax.png">';
  $help .= '<dd><em>' . $image . t('Supports: Any Decimal, Float, or Integer field.') . '</em></dd>';
  $help .= '<dd>' . t('Settings include all settings from the parent <strong>General number</strong> formatter, plus those to show minimum and maximum values for the field. Settings select among common min/max formats, such as "0 &le; 5 &le; 10", "5 out of 10", "5/10", and others. Formatted output also can be customized with a token-like syntax to place the minimum and maximum values along with the field\'s prefix and suffix.') . '</dd>';
  $help .= '<dd>' . t("For multi-value fields, additional settings select whether to show values, minima, and maxima side-by-side on a single line, or consecutively in numbered, bulleted, or non-bulleted blocks. When shown on a single line, an optional separator may be used.") . '</dd>';

  $help .= '</dl>';

  // Text.
  $help .= '<h4>' . t('Formatters for text') . '</h4>';
  $help .= '<dl class="formatter_suite-formatter-list">';

  // -------------------------------------------------------------------.
  $help .= '<a name="TextWithExpandCollapseButtons"></a>';
  $help .= '<dt>' . t('Text with expand/collapse buttons') . '</dt>';
  $image = '<img src="' . $imagePath . 'TextWithExpandCollapseButtons.png">';
  $help .= '<dd><em>' . $image . t('Supports: Any String long, Text, Text long, or Text with Summary field.') . '</em></dd>';
  $help .= '<dd>' . t('Settings select how to shorten the presentation of long text blocks. Clicking an expand button lengthens the presentation to the full text,while clicking a collapse button shortens it again. Settings select the shortened text height and the labels for the expand and collapse buttons. Expand and collapse both use a brief animation to slide the area open or closed. The length of that animation, in milliseconds, can be set.') . '</dd>';

  $help .= '</dl>';

  // Timestamp.
  $help .= '<h4>' . t('Formatters for timestamps') . '</h4>';
  $help .= '<dl class="formatter_suite-formatter-list">';

  // -------------------------------------------------------------------.
  $help .= '<a name="TimestampList"></a>';
  $help .= '<dt>' . t('Timestamp list') . '</dt>';
  $image = '<img src="' . $imagePath . 'TimestampList.png">';
  $help .= '<dd><em>' . $image . t('Supports: Any Timestamp field, including Created and Changed dates.') . '</em></dd>';
  $help .= '<dd>' . t('Settings choose the timezone and the name of a date and time format configured for the site.') . '</dd>';
  $help .= '<dd>' . t("For multi-value fields, additional settings select whether to show values side-by-side on a single line, or consecutively in numbered, bulleted, or non-bulleted blocks. When shown on a single line, an optional separator may be used.") . '</dd>';

  // -------------------------------------------------------------------.
  $help .= '<a name="TimestampTimeAgoList"></a>';
  $help .= '<dt>' . t('Time ago list') . '</dt>';
  $image = '<img src="' . $imagePath . 'TimestampTimeAgoList.png">';
  $help .= '<dd><em>' . $image . t('Supports: Any Timestamp field, including Created and Changed dates.') . '</em></dd>';
  $help .= '<dd>' . t("Settings select the amount of detail to show for the amount of time in the past indicated by a field's value.") . '</dd>';
  $help .= '<dd>' . t("For multi-value fields, additional settings select whether to show values side-by-side on a single line, or consecutively in numbered, bulleted, or non-bulleted blocks. When shown on a single line, an optional separator may be used.") . '</dd>';

  $help .= '</dl>';

  //
  // Render element
  // --------------
  // Return a render element instead of the HTML string itself. This is
  // needed in order to include module libraries to style the help page.
  $page = [
    '#attached'  => [
      'library'  => [
        'formatter_suite/formatter_suite.help',
      ],
    ],
    'page'       => [
      '#type'    => 'container',
      '#tree'    => TRUE,
      '#attributes' => [
        'class'     => ['formatter_suite-admin-help'],
      ],
      'branding' => Branding::getBannerBranding(),
      'help'     => [
        '#type'  => 'html_tag',
        '#tag'   => 'div',
        '#value' => $help,
      ],
    ],
  ];

  return $page;
}
