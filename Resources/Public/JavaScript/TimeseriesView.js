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
        this.title = config.title;
        this.type = config.type;
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
        this.target.append('text')
            .text(this.title);
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

        const bandScale = d3.scaleBand()
            .range([0, this.width])
            .domain(d3.extent(this.data, d => +d.year))
            .align(0)
            .paddingOuter(.25)
            .paddingInner(.5);

        this.target.append('g')
            .attr('transform', `translate(${this.width},0)`)
            .call(qAxis);

        if (this.type == 'area') {
            this.target.append('path')
                .attr('d', area(this.data))
                .attr('fill', 'hsla(45,100%,80%,100%)');

        } else {
            this.target.selectAll('rect')
                .data(this.data)
                .join('rect')
                    .attr('y', d => this.qScale(d.quantity))
                    .attr('x', d => bandScale(+d.year))
                    .attr('width', d => bandScale.bandwidth())
                    .attr('height', d => this._height - this.qScale(d.quantity))
                    .attr('fill', 'hsl(45,100%,80%)');
        }

    }
}
