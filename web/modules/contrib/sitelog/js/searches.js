/**
 * @file
 * Searches chart.
 */

(function (Drupal, d3, drupalSettings) {
  Drupal.behaviors.sitelogSearches = {
    attach: function (context, settings) {

      // define chart dimensions
      const margin = {
        top: 10,
        right: 10,
        bottom: 20,
        left: 150
      };
      const width = d3.select("svg").attr("width") - margin.left - margin.right;
      const height = d3.select("svg").attr("height") - margin.top - margin.bottom;

      // define scale display ranges
      const x = d3.scaleLinear()
        .range([0, width]);
      const y = d3.scaleBand()
        .range([height, 0], .1);

      // define axes orientations
      const xAxis = d3.axisBottom(x);
      const yAxis = d3.axisLeft(y);

      // define grid
      function grid() {
        return d3.axisBottom(x);
      }

      // define colors
      const color = d3.scaleOrdinal(d3.schemeCategory10)
        .domain(d3.range(0, 9));

      // append svg container
      const svg = d3.select("svg")
        .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

      // append axis groups
      const bottomAxis = svg.append("g")
        .attr("transform", "translate(0," + height + ")")
        .attr("class", "sitelog-axis");
      const leftAxis = svg.append("g")
        .attr("class", "sitelog-axis sitelog-no-axis-line");

      // load data
      const data = d3.entries(JSON.parse(drupalSettings.sitelog.searches.data));

      // filter data
      const subset = data.filter(function(d, i) {
        return i < 15;
      });

      // coerce data
      subset.map(d => {
        return d.value = +d.value;
      });

      // reverse data
      subset.reverse();

      // generate scale domains
      x.domain([
        d3.min(subset, d => {
          return d.value - 1;
        }),
        d3.max(subset, d => {
          return d.value + 1;
        }),
      ]);
      y.domain(subset.map(d => {
        return d.key;
      }))
        .paddingInner(.1)
        .paddingOuter(.3);

      // generate axes
      bottomAxis.call(xAxis);
      leftAxis.call(yAxis)
        .select(".domain")
        .remove();

      // join data to selection
      const rect = svg.selectAll("rect")
        .data(subset);

      // append enter selection
      rect.enter()
        .append("rect")
        .attr("y", d => {
          return y(d.key);
        })
        .attr("height", y.bandwidth())
        .attr("x", 1)
        .attr("width", d => {
          return x(d.value);
        })
        .attr("fill", color(9));

      // append grid
      svg.append("g")
        .attr("class", "sitelog-grid")
        .call(grid()
          .tickSize(height)
          .tickFormat("")
        );

      // append x axis label
      svg.append("text")
        .attr("x", width -305)
        .attr("y", height - 12)
        .text("Popular searches, over past 12 months");

    }
  };
})(Drupal, d3, drupalSettings);