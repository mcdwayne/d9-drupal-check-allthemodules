/**
 * @file
 * Author: Synpase-studio.
 */

(function ($) {
  $(document).ready(function () {
    console.log('funnel-script.js')
    var $data = drupalSettings.funnel
    var $attach = '#' + $data.settings.attach
    var resources = {
      localData: $data.users,
      dataType: 'array',
      dataFields: [
        { name: 'id', type: 'number' },
        { name: 'name', type: 'string' },
        { name: 'image', type: 'string' },
        { name: 'common', type: 'boolean' }
      ]
    }
    var source = {
      localData: $data.nodes,
      dataType: 'array',
      dataFields: [
        { name: 'id', type: 'string' },
        { name: 'status', map: 'state', type: 'string' },
        { name: 'text', map: 'label', type: 'string' },
        { name: 'tags', type: 'string' },
        { name: 'color', map: 'hex', type: 'string' },
        { name: 'resourceId', type: 'number' }
      ]
    }

    $($attach).jqxKanban({
      resources: new $.jqx.dataAdapter(resources),
      source: new $.jqx.dataAdapter(source),
      width: '100%',
      height: '100%',
      columns: $data.colums
    })

    $($attach).on('itemMoved', function (event) {
      var $args = event.args
      var $order = []
      var $column = $("div[data-kanban-column-container='" + $args.newColumn.dataField + "'] .jqx-kanban-item")
      $.each($column, function (index, item) {
        $order[index] = $(this).attr('id')
      })
      var update = {
        id: $args.itemId,
        old: $args.oldColumn.dataField,
        new: $args.newColumn.dataField,
        order: $order,
        settings: $data.settings
      }

      $.post($data.settings.updUrl, update).done(function (data) {
        console.log(data)
      })
    })
  })
})(this.jQuery)
