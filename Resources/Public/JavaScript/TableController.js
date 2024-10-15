const tx_publisherdb_tableClass = 'data-table';
const tx_publisherdb_tableSort = 'datatable-sort';
const tx_publisherdb_tableYear = 'datatable-year';
const tx_publisherdb_tableTranslation = 'datatable-translation';
const tx_publisherdb_tableItem = 'datatable-item';
const tx_publisherdb_tableRow = 'datatable-row';
const tx_publisherdb_tableData = 'datatable-data';
const tx_publisherdb_tableFooterTotal = 'footer-total';
const tx_publisherdb_tableSortItem = 'sort-items';
const tx_publisherdb_tableExcludeItem = 'exclude-item';
const tx_publisherdb_tableExcludeYear = 'exclude-year';

const tx_publisherdb_tableController = {

    set target(target) {
        this._target = target;
        this.render();
        this.colorSortButtons();
    },

    colorSortButtons() {
        const ascSelector = tx_publisherdb_visualizationStatus.sorting.asc ? 'asc' : 'desc';
        const sortSelector = `${tx_publisherdb_tableSortItem}-${tx_publisherdb_visualizationStatus.sorting.by}`;
        d3.selectAll('a.sort-btn span').style('color', '');
        d3.selectAll(`a.sort-btn#${sortSelector} span.${ascSelector}`).style('color', 'black');
    },

    render() {
        // set formatters
        const formatNumber = x => Intl.NumberFormat('de-DE').format(x);
        const formatDate = x => d3.timeFormat('%d.%m.%Y')(d3.isoParse(x));
        const createButton = (cl, label) => id => `<a id="${id}" class="${cl} m-1"> ${label} </a>`;
        const createSortButton = tx_publisherdb_visualizationStatus._config.cumulativity == tx_publisherdb_cumulativity.CUMULATIVE ?
            _ => '' :
            createButton('sort-btn', '<span class="desc">↑</span><span class="asc">↓</span>');
        const createExcludeYearButton = tx_publisherdb_visualizationStatus.singleYear ? _ => '' : createButton('exclude-btn', 'x');
        const createExcludeItemButton = tx_publisherdb_visualizationStatus.singleItem ? _ => '' : createButton('exclude-btn', 'x');

        // init
        const target = d3.select(`#${this._target}`);
        target.html('');

        // create table and header
        const table = target.append('table')
            .attr('class', 'table')
            .attr('class', tx_publisherdb_tableClass);

        const tableHead = table.append('thead');

        const translationHeadRow = tableHead.append('tr');
        translationHeadRow.append('th');
        translationHeadRow.selectAll(`th.${tx_publisherdb_tableTranslation}`)
            .data(tx_publisherdb_visualizationStatus.subitemIds)
            .join('th')
            .attr('scope', 'col')
            .attr('class', tx_publisherdb_tableTranslation)
            .attr('id', d => d)
            .attr('class', 'text-right')
            .html(d => tx_mpdbpresentation_translate(d));
        if (
            !tx_publisherdb_visualizationStatus.singleItem &&
            tx_publisherdb_visualizationStatus.targetData != 'prints_by_date'
        ) {
            translationHeadRow.append('th');
        }

        const headRow = tableHead.append('tr');
        const yearHead = headRow.append('th');
        yearHead
            .attr('scope', 'col')
            .attr('class', tx_publisherdb_tableSort)
            .attr('id', tx_publisherdb_tableYear)
            .attr('class', 'text-right');

        if (tx_publisherdb_visualizationStatus.singlePrint) {
            yearHead.html('Datum');
        } else {
            yearHead.html(createSortButton(`${tx_publisherdb_tableSortItem}-year`) + ' Jahr');
        }

        headRow.selectAll(`th.${tx_publisherdb_tableYear}`)
            .data(tx_publisherdb_visualizationStatus.subitemIds)
            .join('th')
            .attr('scope', 'col')
            .attr('class', tx_publisherdb_tableYear)
            .attr('id', d => d)
            .attr('class', 'text-right')
            .html(d => tx_publisherdb_visualizationStatus.singleItem ? d : createSortButton(`${tx_publisherdb_tableSortItem}-${d}`) +
                createExcludeItemButton(`${tx_publisherdb_tableExcludeItem}-${d}`) + d);


        if (tx_publisherdb_visualizationStatus.singlePrint) {
            headRow.selectAll(`th.${tx_publisherdb_tableYear}`).html(d => d);
        } else {
            headRow.selectAll(`th.${tx_publisherdb_tableYear}`).html(d => createSortButton(`${tx_publisherdb_tableSortItem}-${d}`) +
                createExcludeItemButton(`${tx_publisherdb_tableExcludeItem}-${d}`) + d);
        }

        if (
            !tx_publisherdb_visualizationStatus.singleItem &&
            tx_publisherdb_visualizationStatus.targetData != 'prints_by_date'
        ) {
            headRow.append('th')
                .attr('scope', 'col')
                .attr('class', tx_publisherdb_tableSort)
                .attr('id', `${tx_publisherdb_tableYear}-total`)
                .attr('class', 'text-right')
                .html(createSortButton(`${tx_publisherdb_tableSortItem}-total`) + 'Total');
        }

        // create body
        const tableBody = table.append('tbody');

        const bodyRows = tableBody.selectAll('tr')
            .data(
                tx_publisherdb_visualizationStatus.targetData == 'prints_by_date' ?
                tx_publisherdb_visualizationStatus.printsByDates :
                tx_publisherdb_visualizationStatus.summedYearData
            )
            .join('tr');
        bodyRows.append('th')
            .attr('class', tx_publisherdb_tableRow)
            .attr('scope', 'row')
            .attr('class', 'text-right')
            .html(d => tx_publisherdb_visualizationStatus.targetData == 'prints_by_date' ?
                formatDate(d.date) : d.year);

        bodyRows.selectAll(`td.${tx_publisherdb_tableData}`)
            .data(d => d.items)
            .join('td')
            .attr('class', tx_publisherdb_tableData)
            .attr('class', 'text-right')
            .html(d => formatNumber(d));

        if (tx_publisherdb_visualizationStatus.targetData != 'prints_by_date') {
            if (!tx_publisherdb_visualizationStatus.singleItem) {
                bodyRows.append('th')
                    .attr('class', `${tx_publisherdb_tableYear}-total`)
                    .attr('class', 'text-right')
                    .attr('scope', 'row')
                    .html(d => formatNumber(d.total));
            }
        }

        if (
            tx_publisherdb_visualizationStatus._config.cumulativity != 
            tx_publisherdb_cumulativity.CUMULATIVE && 
            !tx_publisherdb_visualizationStatus.singleYear &&
            !tx_publisherdb_visualizationStatus.singlePrint
        ) {
            const tableFoot = table.append('tfoot');
            const footRow = tableFoot.append('tr');
            footRow.append('th')
                .attr('scope', 'col')
                .attr('class', 'text-right')
                .html('Total');

            footRow.selectAll(`th.${tx_publisherdb_tableFooterTotal}`)
                .data(tx_publisherdb_visualizationStatus.sums)
                .join('th')
                .attr('class', tx_publisherdb_tableFooterTotal)
                .attr('scope', 'col')
                .attr('class', 'text-right')
                .html(d => formatNumber(d.sum));
            if (
                !tx_publisherdb_visualizationStatus.singleItem &&
                tx_publisherdb_visualizationStatus.targetData != 'prints_by_date'
            ) {
                const total = tx_publisherdb_visualizationStatus.sums
                    .map(d => +d.sum)
                    .reduce((a, b) => a + b);
                footRow.append('th')
                    .attr('scope', 'col')
                    .attr('class', 'text-right')
                    .html(formatNumber(total));
            }
        }

        this.registerEvents();
    },

    registerEvents() {
        $('a.sort-btn').click( e => {
            const id = e.currentTarget.id.replace(`${tx_publisherdb_tableSortItem}-`, '');
            if (tx_publisherdb_visualizationStatus.sorting.by == id) {
                tx_publisherdb_visualizationStatus.sorting.asc = !tx_publisherdb_visualizationStatus.sorting.asc;
            } else {
                tx_publisherdb_visualizationStatus.sorting.by = id;
                if (id == 'year') {
                    tx_publisherdb_visualizationStatus.sorting.asc = true;
                } else {
                    tx_publisherdb_visualizationStatus.sorting.asc = false;
                }
            }
            tx_publisherdb_visualizationStatus.updateData();
            this.render();
            this.colorSortButtons();
        });

        $('thead a.exclude-btn').click( e => {
            const id = e.currentTarget.id.replace(`${tx_publisherdb_tableExcludeItem}-`, '');
            tx_publisherdb_visualizationStatus.excludeElement(id);
        });

        $('tbody a.exclude-btn').click( e => {
            const year = e.currentTarget.id.replace(`${tx_publisherdb_tableExcludeYear}-`, '');
            tx_publisherdb_visualizationStatus.excludeYear(year);
        });
    }
}
