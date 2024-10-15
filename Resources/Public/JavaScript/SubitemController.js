const tx_publisherdb_subitemController = {

    set config (config) {
        this.target = config.target;
        this.startY = config.startY;
        this.width = config.width;
        this.tAxis = config.tAxis;
        this.tScale = config.tScale;
        this.data = config.data;
        this.titles = config.titles;
        this.margin = config.margin;
        this.type = config.type;
        this.init();
    },

    init() {
        const qScale = d3.scaleLinear()
            .domain(d3.extent(this.data.map(d => d.items).flat()))
            .range([this._height, 0]);

        tx_publisherdb_visualizationStatus.subitemIds.forEach( (d, i) => {
            const data = tx_publisherdb_visualizationStatus.summedYearData.map(d => ({year: d.year, quantity: d.items[i]}));

            const target = this.target.append('g')
                .attr('id', `timeseries-${d}`);

            const config = {
                target: target,
                width: this.width,
                tAxis: this.tAxis,
                tScale: this.tScale,
                qScale: qScale,
                data: data,
                title: this.titles[i],
                subtitle: tx_mpdbpresentation_translate(this.titles[i]),
                margin: this.margin,
                isMain: false,
                type: this.type
            };

            const view = new TimeseriesView(config);
            target.attr('transform', `translate(0,${i * view.height})`);
            this.height = view.height * (i + 1);
        });
    }

}
