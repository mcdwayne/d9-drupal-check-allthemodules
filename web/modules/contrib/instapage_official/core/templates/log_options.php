<?php

/*
 * log_options.php is a template file. It's meant to be included in other php files. To work properly variables need to be set before file inclusion:
 * @param array $rows - options as an associative array. Key is the option name and value if option's value.
 */
?>
<table>
  <thead>
    <tr>
      <th><?php echo InstapageCmsPluginConnector::lang('#'); ?></th>
      <th><?php echo InstapageCmsPluginConnector::lang('Key'); ?></th>
      <th><?php echo InstapageCmsPluginConnector::lang('Value'); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php $index = 1; ?>
    <?php foreach ($rows as $key => $value): ?>
      <tr>
        <td><?php echo $index ?></td>
        <td><?php echo $key ?></td>
        <td><?php echo $value ?></td>
      </tr>
      <?php $index++; ?>
    <?php endforeach; ?>
  </tbody>
</table>
