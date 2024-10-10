const tx_publisherdb_dashboardExcludedItem = 'excluded-item';
const tx_publisherdb_dashboardExcludedYear = 'excluded-year';
const tx_publisherdb_dashboardPublisher = 'publisher';

const tx_publisherdb_dashboardController = {
    
    set target(target) {
        this._target = target;
        this.render();
    },

    render() {
        const createIncludeButton = type => id => `<a id="${id}" class="include-${type} m-1"> + </a>`;
        const createYearIncludeButton = createIncludeButton('year');
        const createElementIncludeButton = createIncludeButton('element');

        const target = d3.select(`#${this._target}`);
        target.html('');

        const excludedItems = target.append('div')
            .attr('id', 'dashboard-excluded-items');
        excludedItems.append('h3')
            .text('Ausgeschlossene Verlagsartikel');
        if (tx_publisherdb_visualizationStatus.excludedElements.length) {
            const excludedItemList = excludedItems.append('div')
                .attr('class', 'tiny button-group');
            d3.select('tx_mpdbpresentation_excludeinfo');
            excludedItemList.selectAll(`a.${tx_publisherdb_dashboardExcludedItem}`)
                .data(tx_publisherdb_visualizationStatus.excludedElements.sort())
                .join('a')
                .attr('class', `${tx_publisherdb_dashboardExcludedItem} primary button hollow include-element`)
                .attr('id', d => d)
                .html(d => d);
        } else {
            excludedItems.append('p')
                .style('font-style', 'italic')
                .style('font-size', '80%')
                .attr('id', 'tx_mpdbpresentation_excludeinfo')
                .text('Sie können in der tabellarischen Ansicht auf das "X" klicken, um einzelne Verlagsteilartikel aus den Ansichten auszuschließen.')
        }

        /*
        const excludedYears = target.append('div')
            .attr('id', 'dashboard-excluded-years');
        excludedYears.append('h3')
            .text('Ausgeschlossene Jahre');
        if (tx_publisherdb_visualizationStatus.excludedYears.length) {
            const excludedYearList = excludedYears.append('div')
                .attr('class', 'tiny button-group');
            excludedYearList.selectAll(`a.${tx_publisherdb_dashboardExcludedYear}`)
                .data(tx_publisherdb_visualizationStatus.excludedYears.sort())
                .join('a')
                .attr('class', `${tx_publisherdb_dashboardExcludedYear} primary button hollow include-year`)
                .attr('id', d => d)
                .html(d => d);
        }
        */

        const movingAverages = target.append('div')
            .attr('id', 'dashboard-moving-averages')
        movingAverages.append('h3')
            .html('Anzeige');
        const movingAveragesList = movingAverages.append('div')
            .attr('class', 'tiny button-group');
        tx_publisherdb_visualizationStatus.movingAverages.forEach( ma => {
            movingAveragesList
                .append('a')
                .attr('id', `dashboard-set-moving-average-${ma}`)
                .attr('class', 'dashboard-set-moving-average primary button hollow')
                .html(`gleitender MW ${ma}`);
        });
        movingAveragesList.append('a')
            .attr('id', 'dashboard-set-cumulative')
            .attr('class', 'primary button hollow dashboard-set-cumulativity')
            .html('kumulativ');
        /*
        movingAveragesList.append('a')
            .attr('id', 'dashboard-set-absolute')
            .attr('class', 'primary button hollow dashboard-set-cumulativity')
            .html('absolut');
            */
        movingAveragesList.append('a')
            .attr('id', 'dashboard-set-per-year')
            .attr('class', 'primary button hollow dashboard-set-granularity')
            .html('pro Jahr');
        movingAveragesList.append('a')
            .attr('id', 'dashboard-set-by-date')
            .attr('class', 'primary button hollow dashboard-set-granularity')
            .html('nach Datum');

        $('a.include-year').click( e => {
            const year = e.currentTarget.id;
            tx_publisherdb_visualizationStatus.includeYear(year);
        });

        $('a.include-element').click( e => {
            const element = e.currentTarget.id;
            tx_publisherdb_visualizationStatus.includeElement(element);
        });

        $('a.dashboard-set-moving-average').click ( e => {
            const ma = e.currentTarget.id.replace('dashboard-set-moving-average-', '');
            tx_publisherdb_visualizationStatus.config = {
                granularity: tx_publisherdb_granularity.PER_YEAR,
                cumulativity: tx_publisherdb_cumulativity.MOVING_AVERAGE,
                movingAverage: ma
            };
        });

        $('a.dashboard-set-cumulativity').click ( e => {
            if (e.currentTarget.id == 'dashboard-set-cumulative') {
                tx_publisherdb_visualizationStatus.config = {
                    granularity: tx_publisherdb_granularity.PER_YEAR,
                    cumulativity: tx_publisherdb_cumulativity.CUMULATIVE,
                    movingAverage: -1
                }
            } else {
                tx_publisherdb_visualizationStatus.config = {
                    granularity: tx_publisherdb_visualizationStatus.config.granularity,
                    cumulativity: tx_publisherdb_cumulativity.ABSOLUTE,
                    movingAverage: -1
                }
            }
        });

        $('a.dashboard-set-granularity').click ( e => {
            if (e.currentTarget.id == 'dashboard-set-per-year') {
                tx_publisherdb_visualizationStatus.config = {
                    cumulativity: tx_publisherdb_cumulativity.ABSOLUTE,
                    granularity: tx_publisherdb_granularity.PER_YEAR,
                    movingAverage: -1
                }
            } else {
                tx_publisherdb_visualizationStatus.config = {
                    cumulativity: tx_publisherdb_cumulativity.ABSOLUTE,
                    granularity: tx_publisherdb_granularity.BY_DATE,
                    movingAverage: -1
                }
            }
        });

        if (!tx_publisherdb_visualizationStatus.isPublishedItem) {
            const publishers = target.append('div')
                .attr('id', 'dashboard-publishers');
            publishers.append('h3')
                .text('Verlage');
            const publisherList = publishers.append('div')
                .attr('class', 'tiny button-group');
            publisherList.selectAll(`a.${tx_publisherdb_dashboardPublisher}`)
                .data(tx_publisherdb_visualizationStatus.publishers)
                .join('a')
                .attr('class', `${tx_publisherdb_dashboardPublisher} primary button hollow include-year`)
                .attr('id', d => d.id)
                .html(d => d.id)
                .attr('title', d => d.name);
        }

        $(`a.${tx_publisherdb_dashboardPublisher}`).click ( e => {
            tx_publisherdb_visualizationStatus.currentPublisher = e.currentTarget.id;
        });

    }
}
