tx_publisherdb_granularity = {
    BY_DATE: 0,
    PER_YEAR: 1
}
tx_publisherdb_cumulativity = {
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

    get targetData () {
        return this._targetData;
    },

    //get movingAverage () {
        //return this._movingAverage;
    //},

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

    /*
    set granularity (granularity) {
        this._granularity = granularity;
        this.updateView();
    },

    set cumulativity (cumulativity) {
        this._cumulativity = cumulativity;
        this.updateView();
    },
    */

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
        this._data = data;
        this.updateView();
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
            this.updateView();
        }
    },

    excludeYear(year) {
        if (!this._excludedYears.includes(year)) {
            this._excludedYears.push(year);
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

    updateView: function (_) {
        console.log(this._config);
        console.log(this._config.cumulativity);
        console.log(tx_publisherdb_cumulativity.CUMULATIVE);
        if (this._config.cumulativity == tx_publisherdb_cumulativity.ABSOLUTE) {
            console.log('cumulativity absolute');
            if (this._config.granularity == tx_publisherdb_granularity.BY_DATE) {
                this._targetData = 'prints_by_date';
            } else {
                this._targetData = 'prints_per_year';
            }
        } else if (this._config.cumulativity == tx_publisherdb_cumulativity.CUMULATIVE) {
            console.log('cumulativity cumulative');
            if (this._config.granularity == tx_publisherdb_granularity.BY_DATE) {
                this._targetData = 'prints_by_date_cumulative';
            } else {
                this._targetData = 'prints_per_year_cumulative';
            }
        } else {
            console.log('cumulativity movavg');
            this._targetData = `prints_per_year_ma_${this._config.movingAverage}`;
        }
        this._views.forEach( view => view.render() );
    },

    registerView: function (view) {
        this._views.push(view);
    }
}
