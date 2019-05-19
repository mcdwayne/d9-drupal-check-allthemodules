/**
 * @file
 * Files chart.
 */

(function (Drupal, d3, drupalSettings) {
  Drupal.behaviors.sitelogFiles = {
    attach: function (context, settings) {

      // add listener for change events
      d3.selectAll("input")
        .on("change", function() {
          period  = +d3.select('input[name=period]:checked').attr('value');
          files = d3.select('input[name=files]:checked').attr('value');
          render(period, files);
        });

      // define chart dimensions
      const margin = {
        top: 10,
        right: 10,
        bottom: 20,
        left: 70
      };
      const width = 800 - margin.left - margin.right;
      const height = 400 - margin.top - margin.bottom;

      // define scale display ranges
      const x = d3.scaleBand()
        .range([0, width], .1);
      const y = d3.scaleLinear()
        .range([height, 0]);

      // define axes orientations
      const xAxis = d3.axisBottom(x);
      const yAxis = d3.axisLeft(y)
        .ticks(5)
        .tickFormat(d3.format(","));

      // define grid
      function grid() {
        return d3.axisLeft(y);
      }

      // define formatter
      const formatWeek = d3.timeFormat("%W");

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
        .attr("class", "sitelog-axis");

      // load data
      const data = JSON.parse(drupalSettings.sitelog.files.data);

      // coerce data
      data.map(d => {
        d.logged = new Date(d.logged * 1000);
        d.uploaded = +d.uploaded;
        d.storage = +d.storage;
        return d;
      });

      // initialise chart
      render();

      function render(period = 90, files = 'uploaded') {

        // filter data
        const subset = data.filter(function(d, i) {
          return i >= (data.length - period);
        });

        // generate scale domains
        x.domain(subset.map(d => {
          return d.logged;
        }))
          .paddingInner(.1)
          .paddingOuter(.3);
        y.domain([
          d3.min(subset, d => {
            return d[files] - 1 < 0 ? 0 : d[files] - 1;
          }),
          d3.max(subset, d => {
            return d[files] + 1;
          }),
        ]);

        // generate ticks
        switch(period) {
          case 7:
            xAxis.tickValues(ticks(1));
            xAxis.tickFormat(d3.timeFormat("%a %d"));
            break;
          case 30:
            xAxis.tickValues(ticks(4));
            xAxis.tickFormat(d3.timeFormat("%a %d %b"));
            break;
          case 90:
            xAxis.tickValues(ticks(12));
            xAxis.tickFormat(d3.timeFormat("%d %b"));
            break;
          case 365:
            xAxis.tickValues(ticks(48));
            xAxis.tickFormat(d3.timeFormat("%d %b %y"));
            break;
          case 1095:
            xAxis.tickValues(ticks(144));
            xAxis.tickFormat(d3.timeFormat("%b %Y"));
            break;
        }
        function ticks(interval) {
          return x.domain().filter((d, i) => {
            return !(i % interval);
          });
        }

        // generate axes
        bottomAxis.call(xAxis)
          .select(".domain")
          .remove();
        leftAxis.call(yAxis);

        // join data to selection
        const rect = svg.selectAll("rect")
          .data(subset);

        // remove exit selection
        rect.exit()
          .remove();

        // append enter selection
        rect.enter()
          .append("rect")
          .merge(rect)
          .attr("x", d => {
            return x(d.logged);
          })
          .attr("width", x.bandwidth())
          .attr("y", d => {
            return y(d[files]);
          })
          .attr("height", d => {
            return height - y(d[files]);
          })
          .attr("fill", files == 'uploaded' ? color(6) : color(8));

        // remove/append grid
        d3.select(".sitelog-grid")
          .remove();
        svg.append("g")
          .attr("class", "sitelog-grid")
          .call(grid()
            .ticks(5)
            .tickSize(-width)
            .tickFormat("")
          );
      }

      // append y axis label
      svg.append("text")
        .attr("transform", "rotate(-90)")
        .attr("x", -30)
        .attr("y", 25)
        .text("Files");
    }
  };
})(Drupal, d3, drupalSettings);
