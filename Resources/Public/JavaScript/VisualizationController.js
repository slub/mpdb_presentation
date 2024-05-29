let tx_publisherdb_visualizationController = {
    set data(data) {
        tx_publisherdb_visualizationStatus.data = data;
    },

    set publishers(publishers) {
        if (!tx_publisherdb_visualizationStatus.data) {
            throw new Error('Provide data before publishers.');
        }

        const firstTwoCapitals = /\b[A-Z][A-Z]/;
        const realisedPublishers = tx_publisherdb_visualizationStatus.data.published_items
            ?.map(d => firstTwoCapitals.exec(d.id)[0]) ??
            firstTwoCapitals.exec(tx_publisherdb_visualizationStatus.data.id);
        const uniqueRealisedPublishers = [ ... new Set(realisedPublishers) ];
        const publisherMap = uniqueRealisedPublishers
            .map(d => ({ 
                id: d,
                name: publishers.filter(p => p.shorthand == d)[0].name
            }));

        tx_publisherdb_visualizationStatus.publishers = publisherMap;
        tx_publisherdb_visualizationStatus.currentPublisher = publisherMap[0].id;
    },

    set config(config) {
        if (!tx_publisherdb_visualizationStatus.data) {
            throw new Error('Provide data before configuration.');
        }

        tx_publisherdb_visualizationStatus.movingAverages = config.movingAverages;
        tx_publisherdb_visualizationStatus.movingAverageSpan = config.movingAverages.
            reduce( (a, b) => a < b ? a : b );

        tx_publisherdb_tableController.target = config.tableTarget;
        if (!tx_publisherdb_visualizationStatus.singlePrint){
            tx_publisherdb_dashboardController.target = config.dashboardTarget;
            tx_publisherdb_graphController.target = config.graphTarget;
        }

        tx_publisherdb_visualizationStatus.registerView(tx_publisherdb_tableController);
        tx_publisherdb_visualizationStatus.registerView(tx_publisherdb_dashboardController);
        tx_publisherdb_visualizationStatus.registerView(tx_publisherdb_graphController);
    }
}
