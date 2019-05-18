(function ($, Drupal, drupalSettings) {

    'use strict';
    Drupal.behaviors.awesome = {
        attach: function(context) {
            $(context).find('#graph-container').once('awesome').each(function() {
                sigma.neo4j.cypher(
                    {url: 'http://localhost:7474', user: 'neo4j', password: 'r00t'},
                    $("#edit-query").val(),
                    {
                        renderers: [{
                            container: document.getElementById('graph-container'),
                            type: 'canvas'
                        }],
                        settings: {
                            enableHovering: true,
                            enableEdgeHovering: true,
                            drawEdgeLabels: false,
                            edgeHoverExtremities: true,
                            edgeLabelSizePowRatio: 1,
                            labelThreshold: 12
                        }
                    },
                    function (s) {
                        s.bind("doubleClickNode", function (event) {
                            window.location = "http://pkm.dev" + event.data.node.neo4j_data.field_url;
                        });
                        s.bind("clickStage", function (event) {
                            s.stopForceAtlas2();
                        });

                        var dragListener = sigma.plugins.dragNodes(s, s.renderers[0]);
                        dragListener.bind('startdrag', function (event) {
                        });
                        dragListener.bind('drag', function (event) {
                        });
                        dragListener.bind('drop', function (event) {
                        });
                        dragListener.bind('dragend', function (event) {
                        });

                        s.refresh();
                        s.startForceAtlas2({
                            barnesHutOptimize: true,
                            gravity: 2,
                            easing: 'cubicInOut'
                        });
                    }
                );
            });
        }
    };
    $('#edit-execute').click(function (e) {
        e.preventDefault();
        $('#graph-container').html('<div id="graph"></div>');
        sigma.neo4j.cypher(
            {url: 'http://localhost:7474', user: 'neo4j', password: 'r00t'},
            $("#edit-query").val(),
            {
                renderers: [{
                    container: document.getElementById('graph-container'),
                    type: 'canvas'
                }],
                settings: {
                    enableHovering: true,
                    enableEdgeHovering: true,
                    drawEdgeLabels: false,
                    edgeHoverExtremities: true,
                    edgeLabelSizePowRatio: 1,
                    labelThreshold: 12
                }
            },
            function (s) {
                s.bind("doubleClickNode", function (event) {
                    window.location = "http://pkm.dev" + event.data.node.neo4j_data.field_url;
                });
                s.bind("clickStage", function (event) {
                    s.stopForceAtlas2();
                });

                var dragListener = sigma.plugins.dragNodes(s, s.renderers[0]);
                dragListener.bind('startdrag', function (event) {
                });
                dragListener.bind('drag', function (event) {
                });
                dragListener.bind('drop', function (event) {
                });
                dragListener.bind('dragend', function (event) {
                });

                s.refresh();
                s.startForceAtlas2({
                    barnesHutOptimize: true,
                    gravity: 2,
                    easing: 'cubicInOut'
                });
            }
        );
    });
})(jQuery, Drupal, drupalSettings);
