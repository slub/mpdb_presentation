class TimeseriesView {

    constructor(config) {
        this.config = config;
    }

    set config(config) {
        this.target = config.target;
        this.width = config.width;
        this.tAxis = config.tAxis;
        this.tScale = config.tScale;
        this.qScale = config.qScale;
        this.data = config.data;
        this.margin = config.margin;
        this.isMain = config.isMain;
        this.init();
    }

    get height() {
        return this._height + this.margin;
    }

    init() {
        const mainRatio = 1.2 - this.width / 1000;
        this.ratio = this.isMain ? mainRatio : mainRatio / 5;
        this._height = this.width * this.ratio;

        this.render();
    }

    render() {
        const qScale = this.qScale.range([this._height, 0]);
        const qAxis = d3.axisRight()
            .scale(this.qScale)
            .ticks(this._height / 25);
        this.target.append('g')
            .attr('transform', `translate(0,${this._height})`)
            .call(this.tAxis);

        const area = d3.area()
            .x(d => this.tScale(+d.year))
            .y0(this._height)
            .y1(d => this.qScale(d.quantity))
            .curve(d3.curveBumpX);

        this.target.append('path')
            .attr('d', area(this.data))
            .attr('fill', 'hsla(45,100%,80%,100%)');

        this.target.append('g')
            .attr('transform', `translate(${this.width},0)`)
            .call(qAxis);

        /*
        this.target.selectAll('circle')
            .data(this.data)
            .join('circle')
                .attr('cx', d => this.tScale(+d.year))
                .attr('cy', d => this.qScale(d.quantity))
                .attr('fill', 'black')
                .attr('r', d => d.quantity > 0 ? 2 : 0);
*/
    }
}
