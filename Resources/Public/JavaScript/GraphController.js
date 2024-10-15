const tx_publisherdb_graphController = {

    set target(target) {
        this._target = target;
        this.render();
    },

    calculateAxis() {
        this._yearScale = d3.scaleLinear()
            .domain(d3.extent(tx_publisherdb_visualizationStatus.years))
            .range([0, this.timeseriesWidth]);
        this._yearBandScale = d3.scaleBand()
            .range([0, this.timeseriesWidth])
            .domain(tx_publisherdb_visualizationStatus.years);

        this._yearAxis = d3.axisBottom()
            .tickFormat(d3.format(' '))
            .scale(this._yearScale);
        this._yearBandAxis = d3.axisBottom()
            .tickFormat(d3.format(' '))
            .scale(this._yearBandScale)
            .tickValues(this._yearScale.ticks());

    },

    render() {
        const graphType = 
            tx_publisherdb_visualizationStatus.targetData == 'prints_per_year' ||
            tx_publisherdb_visualizationStatus.targetData == 'prints_by_date' ?
                'columns' : 'area';
        const target = d3.select(`#${this._target}`);
        target.html('');

        const width = $(`#${this._target}`).width();
        const margin = Math.log2(width) * 3 + 10;
        this.barplotWidth = 
            tx_publisherdb_visualizationStatus.singleItem || 
            tx_publisherdb_visualizationStatus.targetData == 'prints_per_year_cumulative' ?
                0 : (width - 3 * margin) / 4;
        this.timeseriesWidth = width - 3 * margin - this.barplotWidth;

        this.calculateAxis();

        const svg = target.append('svg')
            .attr('id', 'graph')
            .attr('width', width);

        const mainTimeseriesTarget = svg.append('g')
            .attr('id', 'graph-main-timeseries')
            .attr('transform', `translate(${margin},${margin})`);

        const qScale = d3.scaleLinear()
            .domain([0, d3.max(tx_publisherdb_visualizationStatus.summedYearData, d => d.total)]);

        const qAxis = d3.axisRight()
            .scale(this.qScale);

        const timeseriesConfig = {
            target: mainTimeseriesTarget,
            width: this.timeseriesWidth,
            data: tx_publisherdb_visualizationStatus.summedYearData.map(d => ({year: d.year, quantity: d.total})),
            qAxis: qAxis,
            qScale: qScale,
            tAxis: graphType == 'area' ? this._yearAxis : this._yearBandAxis,
            tScale: this._yearScale,
            margin: margin,
            title: '',
            isMain: true,
            type: graphType
        };
        const mainTimeseries = new TimeseriesView(timeseriesConfig);

        const timeseriesHeight = mainTimeseries.height;
        svg.attr('height', mainTimeseries.height + margin * 4);

        if (!tx_publisherdb_visualizationStatus.singleItem) {

            const subitemsTarget = svg.append('g')
                .attr('id', 'graph-subitems')
                .attr('transform', `translate(${margin},${2 * margin + timeseriesHeight})`);

            const subitemConfig = {
                target: subitemsTarget,
                startY: timeseriesHeight + margin * 2,
                width: this.timeseriesWidth,
                tAxis: graphType == 'area' ? this._yearAxis : this._yearBandAxis,
                data: tx_publisherdb_visualizationStatus.summedYearData,
                tScale: this._yearScale,
                titles: tx_publisherdb_visualizationStatus.subitemIds,
                margin: margin,
                type: graphType
            };
            tx_publisherdb_subitemController.config = subitemConfig;

            svg.attr('height', mainTimeseries.height + tx_publisherdb_subitemController.height + margin * 4);

            if (tx_publisherdb_visualizationStatus.targetData != 'prints_per_year_cumulative') {
                const barplotTarget = svg.append('g')
                    .attr('id', 'graph-barplot')
                    .attr('transform', `translate(${2 * margin + this.timeseriesWidth},${2 * margin + mainTimeseries.height})`);
                const barplotConfig = {
                    target: barplotTarget,
                    width: this.barplotWidth,
                    height: tx_publisherdb_subitemController.height
                };
                tx_publisherdb_barplotController.config = barplotConfig;
            }
        }
    }
}
