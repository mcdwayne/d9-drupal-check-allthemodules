/**
 * @file
 * Views chart.
 */

(function (Drupal, d3, drupalSettings) {
  Drupal.behaviors.sitelogViews = {
    attach: function (context, settings) {

      // define chart dimensions
      const width = d3.select("svg").attr("width");
      const height = d3.select("svg").attr("height");
      const radius = Math.min(width, height) / 2;
      const ring = 75;
      const arc = d3.arc()
        .innerRadius(radius - ring)
        .outerRadius(radius)
        .padAngle(.015);
      const pie = d3.pie()
        .value(function(d) {
          return d.daycount;
        })
        .sort(null);

      // define color scheme
      const color = d3.scaleOrdinal(d3.schemeCategory20c)
        .domain(d3.range(0, 4));

      // json parse data
      const data = JSON.parse(drupalSettings.sitelog.views.data);

      // define legend dimensions
      const size = 20;
      const spacing = 5;

      // append container
      const svg = d3.select("svg")
        .append("g")
        .attr("transform", "translate(" + (width / 2) + "," + (height / 2) + ")")
        .attr("class", "sitelog-donut");

      // append slices
      const slice = svg.selectAll(".sitelog-slice")
        .data(pie(data))
        .enter()
        .append("g")
        .attr("class", "sitelog-slice");
      slice.append("path")
        .attr("d", arc)
        .attr("fill", (d, i) => {
          return i == data.length - 1 ? "#eee" : color(i);
        });
      slice.append("text")
        .attr("transform", d => {
          return "translate(" + arc.centroid(d) + ")";
        })
        .attr("dy", ".35em")
        .style("text-anchor", "middle")
        .text((d, i) => {
          return i == data.length - 1 ? "" : d.data.daycount;
        });

      // append legend
      data.pop();
      const legend = svg.selectAll(".sitelog-key")
        .data(data)
        .enter()
        .append("g")
        .attr("class", ".sitelog-key")
        .attr("transform", (d, i) => {
          const height = size + spacing;
          const offset = height * data.length / 2;
          const horizontal = -5 * size;
          const vertical = i * height - offset;
          return "translate(" + horizontal + "," + vertical + ")";
        });
      legend.append("rect")
        .attr("width", size)
        .attr("height", size)
        .attr("fill", (d, i) => {
          return color(i);
        });
      legend.append("text")
        .attr("x", size + spacing)
        .attr("y", size - spacing)
        .text(d => {
          return d.title.length < 20 ? d.title : d.title.substr(0, 19) + "...";
        });

      // no data label
      if (data.length == 0) {
        svg.append("text")
          .attr("class", "sitelog-none")
          .text("None");
      }
    }
  };
})(Drupal, d3, drupalSettings);
