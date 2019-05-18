/**
 * @file
 * Contains js for sys.
 */

(function ($, Drupal, drupalSettings) {
    'use strict';

    Drupal.behaviors.sys = {
        attach: function (context, settings) {
            var db_u = drupalSettings.sys.db_u;
            var labels = labelsTables(db_u);
            var data = dataTables(db_u);
            var ctx = document.getElementById("db-usage-chart").getContext('2d');
            var diskCtx = document.getElementById("disk-chart").getContext('2d');
            var memoryCtx = document.getElementById("memory-chart").getContext('2d');
            var tablesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: Drupal.t(' Table size in MB'),
                        data: data,
                        backgroundColor : '#364f6b',
                        hoverBackgroundColor: 'rgb(255, 99, 132)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero:true
                            }
                        }]
                    }
                }
            });


            var disk_u = drupalSettings.sys.disk_u;
            data = dataDisk(disk_u);
            var diskChart = new Chart(diskCtx, {
                type: 'pie',
                data: {
                    labels: ['Free space', 'Used space'],
                    datasets: [{
                        label: "GB",
                        backgroundColor: ["#fc5185", "#364f6b"],
                        data: data
                    }]
                }
            });


            var memory_u = drupalSettings.sys.memory_u;
            data = dataMemory(memory_u);
            var memoryChart = new Chart(memoryCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Free space', 'Used space', 'Cached space'],
                    datasets: [{
                        backgroundColor: ["#fc5185", "#364f6b"],
                        label: 'GB',
                        data: data
                    }]
                }
            });

        }
    }
})(jQuery, Drupal, drupalSettings);

var labelsTables = function ($data) {
    return $data.map(function (e) {
        return e.name;
    });
};

var dataTables = function ($data) {
    return $data.map(function (e) {
        return (e.size_byte / 1048576).toFixed(2);
    });
};
var dataDisk = function ($data) {
    var $array = $data[0];
    if($array.length > 4) {
        $array.pop();
    }
    var data = $array.filter(function (e) {
        return (e.indexOf('G') || e.indexOf('M')) !== -1;
    });
    return data.map(function (e) {
        if(e.indexOf('G') !== -1) {
            return e.replace("G", '');
        }
        else if(e.indexOf('M') !== -1) {
            return e.replace("M", '');
        }
    });
};

var dataMemory = function ($data) {
    var $ram = $data[0];
    var data = [];
    var str = null;

    data[0] = $ram.free;
    data[1] = $ram.used;
    data[2] = $ram.cached;

    data = data.map(function (e) {
        if(e.indexOf('G') !== -1) {
            return e.replace("G", '');
        }
        if(e.indexOf('M') !== -1) {
            str = e.replace("M", '');
            return (str / 1024).toFixed(2);
        }
    });
    return data;
};