class ViewgraphControl {
    constructor (data, svg, controls, margin) {
        this.data = data;
        this.svg = svg;
        this.controls = controls;
        this.margin = margin;
        this.init();
    }

    init () {
        this.calcStructuredData();
        this.maingroup = d3.select(svg).append('g')
            .attr('class', 'main')
            .attr('transform', `translate(${margin},${margin})`);

        const timeseriesMain = d3.select(this.maingroup).append('g')
            .attr('class', 'timeseries-main');
        this.timeseriesMain = new TimeseriesControl(timeseriesMain);
        this.mainHeight = this.timeseriesMain.getHeight();

        const timeseriesSmall = d3.select(this.maingroup).append('g')
            .attr('class', 'timeseries-small')
            .attr('transform', `translate(0,${mainHeight})`);
        this.timeseriesSmall = new TimeseriesSmallControl(timeseriesSmall);
        this.height = this.timeseriesSmall.getHeight();

        const zoomline = d3.select(this.maingroup).append('g')
            .attr('class', 'zoomline');
        this.zoomline = new ZoomlineControl(zoomline);

        const brush = d3.select(this.maingroup).append('g')
            .attr('class', 'brush');
        this.brush = new brushControl(brush);
        $(window).resize(draw);
        $(controls).find('.graph-control').click(
            e => draw($(e.target).attr('data-year'))
        );
        draw(0);
    }

    draw(amt) {
        this.calcMeans(amt);
        const flat = amt ? this.structuredData.means[amt].flat : this.structuredData.cumulative.flat;
        const deep = amt ? this.structuredData.means[amt].deep : this.structuredData.cumulative.deep;
        this.timeseriesMain.update(flat);
        this.timeseriesSmall.update(deep);
    }

    static sortByDate (a, b) {
        return a.date < b.date ? 1 : -1;
    }

    static calcCumulative (amt) {
        return ({label, actions}, i, array) => ({ label, actions: d3.cumsum(array, action => action.quantity)[i]/amt });
    }

    static calcNegSteps (amt) {
        return ({label, actions}) => ({ label, actions: actions.map(ViewgraphControl.calcNegStep(amt)) });
    }

    static calcNegStep ({uid, date, quantity}, amt) {
        return ({uid, date, quantity}) => ({uid, date: d3.timeYear.offset(date, amt), quantity: -quantity});
    }

    static sortGroups (groups) {
        return groups.map(group => group.sort(ViewgraphControl.sortByDate));
    }

    calcStructuredData() {
        this.data = ViewgraphControl.sortGroups(this.data);
        const cumulativeDeep = data.map(ViewgraphControl.calcCumulative(1));
        const cumulativeFlat = [ ... new Set(cumulativeDeep.map(group => group.actions).flat() ];
        this.structuredData = { cumulative: { deep: cumulativeDeep, flat: cumulativeFlat }, means: undefined };
    }

    calcMeans(amt) {
        if (!this.structuredData.means[amt]) {
            const negSteps = data.map(ViewgraphControl.calcNegSteps(amt));
            const all = ViewgraphControl.sortGroups(this.structuredData.cumulative.deep.concat(negSteps));
            const deep = all.map(ViewgraphControl.calcCumulative(amt));
            const flat = [ ... new Set(cumulativeDeep.map(group => group.actions).flat() ];
            this.structuredData.means[amt] = { deep: deep, flat: flat };
        }
    }

}
