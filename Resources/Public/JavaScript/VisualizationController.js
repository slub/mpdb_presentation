let tx_publisherdb_visualizationController = {
    set data(data) {
        tx_publisherdb_visualizationStatus.data = data;
    },

    set config(config) {
        if (!tx_publisherdb_visualizationStatus.data) {
            throw new Error('Provide data first and configuration second.');
        }

        tx_publisherdb_visualizationStatus.movingAverages = config.movingAverages;
        tx_publisherdb_visualizationStatus.movingAverageSpan = config.movingAverages.
            reduce( (a, b) => a < b ? a : b );

        tx_publisherdb_tableController.target = config.tableTarget;
        tx_publisherdb_dashboardController.target = config.dashboardTarget;
        //tx_publisherdb_graphController.target = config.graphTarget;

        tx_publisherdb_visualizationStatus.registerView(tx_publisherdb_tableController);
        tx_publisherdb_visualizationStatus.registerView(tx_publisherdb_dashboardController);
        //tx_publisherdb_visualizationStatus.registerView(tx_publisherdb_graphController);
    }
}

/*
tx_publisherdb_visualizationController.setData = (data) => {
    this.data = data;
}

tx_publisherdb_visualizationController.config = (config) => {
    this.config = config;
}

class VisualizationController extends Singleton {

    constructor(config, data) {
        this.id = config.id;
        this.target = config.target;
        this.movingAverages = config.movingAverages;
        this.data = data;

        this.#movingAverageSpan = config.movingAverages.reduce( (a, b) => a < b ? a : b );
        new TableController(config, data, visualizationStatus);

        this.init();
    }

    init() {
        console.log(this.data);
        console.log(`#${this.target}`);
        console.log($(`#${this.target}`));

    };

}
*/
