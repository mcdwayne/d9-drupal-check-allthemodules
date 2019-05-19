/**
 * @file
 * System events chart.
 */

(function (Drupal, d3, drupalSettings) {
  Drupal.behaviors.sitelogLine = {
    attach: function (context, settings) {

      // add listener for change events
      d3.selectAll("input")
        .on("change", function() {
          period  = +d3.select('input[name=period]:checked').attr('value');
          render(period);
        });

      // define chart dimensions
      const margin = {
        top: 10,
        right: 10,
        bottom: 20,
        left: 50
      };
      const width = 800 - margin.left - margin.right;
      const height = 400 - margin.top - margin.bottom;

      // define scale display ranges
      const x = d3.scaleTime().range([0, width]);
      const y = d3.scaleLinear().range([height, 0]);
      const z = d3.scaleOrdinal(d3.schemeCategory10);

      // define axes orientations
      const xAxis = d3.axisBottom(x);
      const yAxis = d3.axisLeft(y);

      // define line generator
      const line = d3.line()
        .curve(d3.curveBasis)
        .x(function(d) {
          return x(d.logged);
        })
        .y(function(d) {
          return y(d.events);
        });

      const labels = [
        "emergency",
        "alert",
        "critical",
        "error",
        "warning",
        "notice",
        "info",
        "debug"
      ];

      // append group container
      const svg = d3.select("svg")
        .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

      // append axis groups
      const bottomAxis = svg.append("g")
        .attr("class", "sitelog-axis")
        .attr("transform", "translate(0," + height + ")");
      const leftAxis = svg.append("g")
        .attr("class", "sitelog-axis");

      // load data
      const data = JSON.parse(drupalSettings.sitelog.system_events.data);

      // coerce timestamps into date objects
      data.map(d => {
        return d.logged = new Date(d.logged * 1000);
      });

      // append legend
      const legend = d3.select(".sitelog-legend-container")
        .append("div")
        .attr("class", "sitelog-legend");
      const legendItem = legend.selectAll(".sitelog-legend-key")
        .data(labels)
        .enter()
        .append("div");
      legendItem.append("div")
        .attr("class", "sitelog-legend-key")
        .style("background-color", function(d, i) {
          return z(i);
        });
      legendItem.append("div")
        .attr("class", "sitelog-legend-label")
        .html(d => {
          return d.charAt(0).toUpperCase() + d.slice(1);
        });

      // initialise chart
      render();

      function render(period = 90) {

        // filter data
        const subset = data.filter(function(d, i) {
          return i >= (data.length - period);
        });

        // restructure subset
        subset.levels = labels;
        const levels = subset.levels.map(function(level) {
          return {
            level: level,
            values: subset.map(function(d) {
              return {
                logged: d.logged,
                events: d[level]
              };
            })
          };
        });

        // generate scale domains
        x.domain(d3.extent(subset, function(d) {
          return d.logged;
        }));
        y.domain([
          d3.min(levels, function(l) {
            return d3.min(l.values, function(d) {
              return +d.events;
            });
          }),
          d3.max(levels, function(l) {
            return d3.max(l.values, function(d) {
              return +d.events;
            });
          })
        ]);
        z.domain(levels.map(function(l) {
          return l.level;
        }));

        // generate axes
        bottomAxis.call(xAxis)
          .select(".domain")
          .remove();
        leftAxis.call(yAxis);

        // join data to selection
        const level = svg.selectAll(".sitelog-line")
          .data(levels);

        // remove exit selection
        level.exit()
          .remove();

        // append enter selection
        level.enter()
          .append("path")
          .merge(level)
          .attr("class", "sitelog-line")
          .attr("d", function(d) {
            return line(d.values);
          })
          .style("stroke", function(d) {
            return z(d.level);
          });
      }

      // append y axis label
      svg.append("text")
        .attr("transform", "rotate(-90)")
        .attr("x", -41)
        .attr("y", 25)
        .text("Events");
    }
  };
})(Drupal, d3, drupalSettings);
