<?php

/*
 * log_plugins.php is a template file. It's meant to be included in other php files. To work properly variables need to be set before file inclusion:
 * @param array $rows List of plugins or modules as an associative array.
 * @param string $row['Name'] Plugin's / Module's name
 * @param string $row['Version'] - Plugin's / Module's version
 */
?>
<table>
  <thead>
    <tr>
      <th><?php echo InstapageCmsPluginConnector::lang('#'); ?></th>
      <th><?php echo InstapageCmsPluginConnector::lang('Name'); ?></th>
      <th><?php echo InstapageCmsPluginConnector::lang('Version'); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php $index = 1; ?>
    <?php foreach ($rows as $key => $row): ?>
      <tr>
        <td><?php echo $index ?></td>
        <td><?php echo $row['Name'] ?></td>
        <td><?php echo $row['Version'] ?></td>
      </tr>
      <?php $index++; ?>
    <?php endforeach; ?>
  </tbody>
</table>
