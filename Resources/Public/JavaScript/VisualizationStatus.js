const tx_publisherdb_granularity = {
    BY_DATE: 0,
    PER_YEAR: 1
}
const tx_publisherdb_cumulativity = {
    ABSOLUTE: 0,
    MOVING_AVERAGE: 1,
    CUMULATIVE: 2
}

let tx_publisherdb_visualizationStatus = {

    _config: {
        granularity: tx_publisherdb_granularity.PER_YEAR,
        cumulativity: tx_publisherdb_cumulativity.ABSOLUTE,
        movingAverage: -1
    },
    _movingAverages: [],
    _movingAverageSpan: -1,
    _excludedYears: [],
    _excludedDates: [],
    _excludedElements: [],
    _views: [],
    _data: [],
    _targetData: 'prints_per_year',
    _currentPublisher: '',
    _isPublishedItem: false,
    _publishers: [],
    singleItem: false,
    singleYear: false,
    singlePrint: false,
    _years: [],
    _subitemIds: [],

    sorting: {
        by: 'year',
        asc: true
    },

    set subitemIds (subitemIds) {
        this._subitemIds = subitemIds;
        if (this._subitemIds.length == 1) {
            this.singleItem = true;
        } else {
            this.singleItem = false;
        }
    },

    get subitemIds () {
        return this._subitemIds;
    },

    set years (years) {
        this._years = years;
        if (this._years.length == 1) {
            this.singleYear = true;
        } else {
            this.singleYear = false;
        }
    },

    get years () {
        return this._years;
    },

    get targetData () {
        return this._targetData;
    },

    set currentPublisher (currentPublisher) {
        this._currentPublisher = currentPublisher;
        this.updateView();
    },

    get currentPublisher () {
        return this._currentPublisher;
    },

    set config (config) {
        this._config = config;
        this.updateView();
    },

    get config () {
        return this._config;
    },

    set movingAverageSpan (movingAverageSpan) {
        this._movingAverageSpan = movingAverageSpan;
        this.updateView();
    },

    set publishers (publishers) {
        this._publishers = publishers;
    },

    get publishers () {
        return this._publishers;
    },

    set excludedYears (excludedYears) {
        this._excludedYears = excludedYears;
        this.updateView();
    },

    set excludedDates (excludedDates) {
        this._excludedDates = excludedDates;
        this.updateView();
    },

    set excludedElements (excludedElements) {
        this._excludedElements = excludedElements;
        this.updateView();
    },

    set data (data) {
        this._isPublishedItem = data.published_subitems ? true : false;
        this._data = data;

        if (
            this._data.published_subitems &&
            this._data.published_subitems.length == 1 && 
            this._data.published_subitems[0].prints_per_year.length == 1
        ) {
            this.singlePrint = true;
        }

        const suborder = this._isPublishedItem ? data.published_subitems :
            data.published_items
                .map(published_item => published_item.published_subitems)
                .flat();
        const dates = suborder.filter(published_subitem => published_subitem.prints_by_date)
            .map(published_subitem => published_subitem.prints_by_date)
            .flat()
            .map(print => print.date);
        const uniqueDates = [ ... new Set(dates) ];

        this.printsByDates = uniqueDates.map(date => ({ 
            date: date, items:
                suborder.map( subitem =>
                    subitem.prints_by_date?.filter(print => print.date== date).length > 0 ?
                    subitem.prints_by_date.filter(print => print.date == date)[0]['quantity'] : 0
                )
            })
        ).sort( (printA, printB) =>
            printA.date < printB.date ? -1 : 1
        );

        this.updateView();
    },

    get isPublishedItem () {
        return this._isPublishedItem;
    },

    get data () {
        return this._data;
    },

    get excludedElements () {
        return this._excludedElements;
    },

    get excludedYears () {
        return this._excludedYears;
    },

    excludeElement(id) {
        if (!this._excludedElements.includes(id)) {
            this._excludedElements.push(id);

            if (this._subitemIds.length - this._excludedElements.length == 1) {
                this.singleItem = true;
            } else {
                this.singleItem = false;
            }

            this.updateView();
        }
    },

    excludeYear(year) {
        if (!this._excludedYears.includes(year)) {
            this._excludedYears.push(year);

            if (this._years.length - this._excludedYears.length == 1) {
                this.singleYear = true;
            } else {
                this.singleYear = false;
            }

            this.updateView();
        }
    },

    includeElement(element) {
        this._excludedElements = this._excludedElements.filter(d => d != element);
        this.updateView();
    },

    includeYear(year) {
        this._excludedYears = this._excludedYears.filter(d => d != year);
        this.updateView();
    },

    updateView () {
        console.log(this._config);
        if (this._config.cumulativity == tx_publisherdb_cumulativity.ABSOLUTE) {
            if (this._config.granularity == tx_publisherdb_granularity.BY_DATE) {
                this._targetData = 'prints_by_date';
            } else {
                this._targetData = 'prints_per_year';
            }
        } else if (this._config.cumulativity == tx_publisherdb_cumulativity.CUMULATIVE) {
            if (this._config.granularity == tx_publisherdb_granularity.BY_DATE) {
                this._targetData = 'prints_by_date_cumulative';
            } else {
                this._targetData = 'prints_per_year_cumulative';
            }
        } else {
            this._targetData = `prints_per_year_ma_${this._config.movingAverage}`;
        }

        this.updateData();
        this._views.forEach( view => view.render() );
    },

    updateData() {
        if (this._targetData != 'prints_by_date') {
            // retrieve published subitems
            const publishedSubitems = this.isPublishedItem ? this.data.published_subitems :
                this.data.published_items.map(d => d.published_subitems).flat();
            const currentPublisherShorthand = this.currentPublisher ?? null;
            const currentPublisherRegex = currentPublisherShorthand ? new RegExp(`\\b${currentPublisherShorthand}_\w*`) : null;

            // retrieve ids for table header
            this.subitemIds = publishedSubitems.map(d => d.id)
                .filter(d => !this.excludedElements.includes(d))
                .filter(d => currentPublisherRegex ? currentPublisherRegex.test(d) : true);

            // retrieve per year data including totals for table body
            const years = publishedSubitems.map(subitem => {
                    const targetData = subitem[this.targetData] ?? [];
                    return targetData.map(print => print.date);
                })
                .flat();
            this.years = d3.range(+d3.min(years), +d3.max(years) + 1)
                .filter(year => !this.excludedYears.includes(year));

            const yearData = this.years.map(year => ({
                    year: year,
                    items: publishedSubitems
                        .filter(item => !this.excludedElements.includes(item.id))
                        .filter(item => currentPublisherRegex ? currentPublisherRegex.test(item.id) : true)
                        .map(prints => {
                            const targetData = prints[this.targetData] ?? [];
                            const targetPrint = targetData.filter(print => print.date == year);
                            return {
                                id: prints.id,
                                quantity: targetPrint.length > 0 ? targetPrint[0].quantity : 0
                            };
                        }),
                }))
                .sort(this.sort)
                .map(item => ({ year: item.year, items: item.items.map(i => i.quantity) }));
            this.summedYearData = yearData.map(({year, items}) => ({
                year, items,
                total: items.reduce((a, b) => +a + b)
            }));

            // retrieve per item sums for table footer
            this.sums = publishedSubitems.filter(item => !this.excludedElements.includes(item.id))
                .filter(item => currentPublisherRegex ? currentPublisherRegex.test(item.id) : true)
                .map(
                    subitem => {
                        const targetData = subitem[this.targetData] ?? [];
                        const filteredTargetData = targetData.filter(print => !this.excludedYears.includes(print.date));
                        const sum = filteredTargetData.length ? filteredTargetData.map(print => print.quantity)
                            ?.reduce( (a, b) => +a + b ) : null
                        return { id: subitem.id, sum: sum };
                    });
        }
    },

    registerView: function (view) {
        this._views.push(view);
    },

    sort(a, b) {
        if (tx_publisherdb_visualizationStatus.sorting.by == 'year') {
            return tx_publisherdb_visualizationStatus.sorting.asc ?
                a.year - b.year : b.year - a.year;
        } else if (tx_publisherdb_visualizationStatus.sorting.by == 'total') {
            const totalA = a.items.map(item => item.quantity).reduce((c, d) => c + d);
            const totalB = b.items.map(item => item.quantity).reduce((c, d) => c + d);
            const quantityDiff = tx_publisherdb_visualizationStatus.sorting.asc ?
                totalA - totalB : totalB - totalA;
            if (quantityDiff) {
                return quantityDiff;
            }
            return a.year - b.year;
        } else {
            const quantityA = a.items.filter(item => item.id == tx_publisherdb_visualizationStatus.sorting.by)[0]['quantity'];
            const quantityB = b.items.filter(item => item.id == tx_publisherdb_visualizationStatus.sorting.by)[0]['quantity'];
            const quantityDiff = tx_publisherdb_visualizationStatus.sorting.asc ?
                quantityA - quantityB : quantityB - quantityA;
            if (quantityDiff) {
                return quantityDiff;
            }
            return a.year - b.year;
        }
    }
}
