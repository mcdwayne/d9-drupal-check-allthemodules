/**
 * @file
 * Users chart.
 */

(function (Drupal, d3, drupalSettings) {
  Drupal.behaviors.sitelogUsers = {
    attach: function (context, settings) {

      // add listener for change events
      d3.selectAll("input")
        .on("change", function() {
          period  = +d3.select('input[name=period]:checked').attr('value');
          accounts = d3.select('input[name=accounts]:checked').attr('value');
          render(period, accounts);
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

      // define color scheme
      const scheme = d3.scaleSequential(d3.interpolateGreens);
      const t = d3.scaleLinear()
        .domain([0, 53])
        .range([.25, 1]);

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
      const data = JSON.parse(drupalSettings.sitelog.users.data);

      // coerce data
      data.map(d => {
        d.logged = new Date(d.logged * 1000);
        d.active = +d.active;
        d.inactive = +d.inactive;
        d.registrations = +d.registrations;
        d.accessed = +d.accessed;
        return d;
      });

      // initialise chart
      render();

      function render(period = 90, accounts = 'active') {

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
            return d[accounts] - 1 < 0 ? 0 : d[accounts] - 1;
          }),
          d3.max(subset, d => {
            return d[accounts] + 1;
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
            return y(d[accounts]);
          })
          .attr("height", d => {
            return height - y(d[accounts]);
          })
          .attr("fill", d => {
            return scheme(t(formatWeek(d.logged)));
          });

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
        .attr("x", -35)
        .attr("y", 25)
        .text("Users");
    }
  };
})(Drupal, d3, drupalSettings);
