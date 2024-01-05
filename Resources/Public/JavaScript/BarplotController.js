const tx_publisherdb_barplotController = {

    set config (config) {
        this.target = config.target;
        this.width = config.width;
        this.height = config.height;
        this.render();
    },

    render () {
        const qScale = d3.scaleLinear()
            .range([0, this.width])
            .domain([0, d3.max(tx_publisherdb_visualizationStatus.sums, d => d.sum)]);
        const cScale = d3.scaleBand()
            .range([0, this.height])
            .domain(tx_publisherdb_visualizationStatus.subitemIds)
            .align(0)
            .paddingOuter(.25)
            .paddingInner(.5);

        const textMargin = 5;
        const positioner = x => qScale(x) + textMargin < this.width / 2 ?
            qScale(x) + textMargin :
            qScale(x) - textMargin;
        const anchorer = x => qScale(x) + textMargin < this.width / 2 ?
            'start' : 'end';

        const bars = this.target.selectAll('rect')
            .data(tx_publisherdb_visualizationStatus.sums)
            .join('rect')
                .attr('x', 0)
                .attr('y', d => cScale(d.id))
                .attr('height', cScale.bandwidth())
                .attr('width', d => qScale(d.sum))
                .attr('fill', 'hsl(45,100%,80%)');

        this.target.selectAll('text')
            .data(tx_publisherdb_visualizationStatus.sums)
            .join('text')
                .attr('x', d => positioner(d.sum))
                .attr('y', d => cScale(d.id) + cScale.bandwidth() / 2 + 5)
                .attr('text-anchor', d => anchorer(d.sum))
                .attr('font-size', '75%')
                .text(d => d.sum);

    }

}
