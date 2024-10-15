const tx_publisherdb_dashboardExcludedItem = 'excluded-item';
const tx_publisherdb_dashboardExcludedYear = 'excluded-year';
const tx_publisherdb_dashboardPublisher = 'publisher';

const prefix = 'dashboard';
const movingAverages = `${prefix}-moving-averages`;
const setMovingAverage = `${prefix}-set-moving-average`;
const setCumulative = `${prefix}-set-cumulative`;
const setPerYear = `${prefix}-set-per-year`;
const setByDate = `${prefix}-set-by-date`;
const btnClassList = 'primary button hollow';

const tx_publisherdb_dashboardController = {

    set target(target) {
        this._target = target;
        this.render();
    },

    draw(targetElement) {
        const target = d3.select(targetElement);
        target.html('');

        const movingAverages = target.append('div')
            .attr('class', 'display')
        movingAverages.append('h3')
            .html('Anzeige');
        const movingAveragesList = movingAverages.append('div')
            .attr('class', 'tiny button-group');
        tx_publisherdb_visualizationStatus.movingAverages.forEach( ma => {
            movingAveragesList
                .append('a')
                .attr('data', ma)
                .attr('type', setMovingAverage)
                .attr('class', btnClassList)
                .html(`gleitender MW ${ma}`);
        });
        movingAveragesList.append('a')
            .attr('type', setCumulative)
            .attr('class', btnClassList)
            .html('kumulativ');
        movingAveragesList.append('a')
            .attr('type', setPerYear)
            .attr('class', btnClassList)
            .html('pro Jahr');
        movingAveragesList.append('a')
            .attr('type', setByDate)
            .attr('class', btnClassList)
            .html('nach Datum');

        if (!tx_publisherdb_visualizationStatus.isPublishedItem) {
            const publishers = target.append('div')
                .attr('class', 'dashboard-publishers');
            publishers.append('h3')
                .text('Verlage');
            const publisherList = publishers.append('div')
                .attr('class', 'tiny button-group');
            publisherList.selectAll(`a.${tx_publisherdb_dashboardPublisher}`)
                .data(tx_publisherdb_visualizationStatus.publishers)
                .join('a')
                .attr('class', `${tx_publisherdb_dashboardPublisher} primary button hollow include-year`)
                .attr('class', d => d.id)
                .html(d => d.id)
                .attr('title', d => d.name);
        }

        const excludedItems = target.append('div')
            .attr('class', 'dashboard-excluded-items');
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

    },

    render() {
        const createIncludeButton = type => id => `<a class="${id}" class="include-${type} m-1"> + </a>`;
        const createYearIncludeButton = createIncludeButton('year');
        const createElementIncludeButton = createIncludeButton('element');
        const displayBtnList = `a[type=${setMovingAverage}], a[type=${setCumulative}], a[type=${setPerYear}], a[type=${setByDate}]`;

        const target = d3.selectAll(`.${this._target}`);
        target.nodes().forEach(target => this.draw(target));

        $('a.include-year').click( e => {
            const year = e.currentTarget.id;
            tx_publisherdb_visualizationStatus.includeYear(year);
        });

        $('a.include-element').click( e => {
            const element = e.currentTarget.id;
            tx_publisherdb_visualizationStatus.includeElement(element);
        });

        $(`a[type=${setMovingAverage}]`).click ( e => {
            const ma = e.currentTarget.attributes.data.nodeValue;
            tx_publisherdb_visualizationStatus.config = {
                granularity: tx_publisherdb_granularity.PER_YEAR,
                cumulativity: tx_publisherdb_cumulativity.MOVING_AVERAGE,
                movingAverage: ma
            };

            $(displayBtnList).addClass('hollow');
            $(`a[type=${setMovingAverage}][data=${ma}]`).removeClass('hollow');
        });

        $(`a[type=${setCumulative}]`).click ( e => {
            tx_publisherdb_visualizationStatus.config = {
                granularity: tx_publisherdb_granularity.PER_YEAR,
                cumulativity: tx_publisherdb_cumulativity.CUMULATIVE,
                movingAverage: -1
            }

            $(displayBtnList).addClass('hollow');
            $(`a[type=${setCumulative}]`).removeClass('hollow');
        });

        $(`a[type=${setPerYear}]`).click ( e => {
            tx_publisherdb_visualizationStatus.config = {
                cumulativity: tx_publisherdb_cumulativity.ABSOLUTE,
                granularity: tx_publisherdb_granularity.PER_YEAR,
                movingAverage: -1
            }

            $(displayBtnList).addClass('hollow');
            $(`a[type=${setPerYear}]`).removeClass('hollow');
        });

        $(`a[type=${setByDate}]`).click ( e => {
            tx_publisherdb_visualizationStatus.config = {
                cumulativity: tx_publisherdb_cumulativity.ABSOLUTE,
                granularity: tx_publisherdb_granularity.BY_DATE,
                movingAverage: -1
            }

            $(displayBtnList).addClass('hollow');
            $(`a[type=${setByDate}]`).removeClass('hollow');
        });

        $(`a.${tx_publisherdb_dashboardPublisher}`).click ( e => {
            tx_publisherdb_visualizationStatus.currentPublisher = e.currentTarget.id;
        });

    }
}
