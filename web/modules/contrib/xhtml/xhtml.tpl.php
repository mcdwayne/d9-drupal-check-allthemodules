<?php if (!empty($xml_shebang)) echo $xml_shebang;
?><!DOCTYPE html>
<html<?php print $html_attributes; ?>>
  <head>
    <?php print $head; ?>
    <title><?php print $head_title; ?></title>
    <?php print $styles; ?>
    <?php print $scripts; ?>
  </head>
  <body class="<?php print $classes; ?>" <?php print $body_attributes;?>>
    <div id="skip-link">
      <a href="#main-content" class="element-invisible element-focusable"><?php print t('Skip to main content'); ?></a>
    </div>
    <?php print $page_top; ?>
    <?php print $page; ?>
    <?php print $page_bottom; ?>
  </body>
</html>
