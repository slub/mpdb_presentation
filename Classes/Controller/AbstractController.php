<?php
namespace Slub\MpdbPresentation\Controller;

use Illuminate\Support\Collection;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Slub\MpdbCore\Controller\AbstractController as CoreAbstractController;
use Slub\MpdbPresentation\Command\IndexPublishersCommand;
use Slub\MpdbPresentation\Services\SearchServiceInterface;
use Slub\MpdbPresentation\Services\SearchServiceNotFoundException;

abstract class AbstractController extends CoreAbstractController
{
    const TABLE_TARGET = 'tx_mpdbpresentation_table';
    const GRAPH_TARGET = 'tx_mpdbpresentation_graph';
    const DASHBOARD_TARGET = 'tx_mpdbpresentation_dashboard';
    const RESULT_COUNT = 25;
    const INDICES = [ 
        'person' => [ 
            'symbol' => '🧍',
            'controller' => 'Person' 
        ], 
        'work' => [ 
            'symbol' => '📄',
            'controller' => 'Work' 
        ],
        'published_item' => [ 
            'symbol' => '📕',
            'controller' => 'PublishedItem' 
        ],
        'instrument' => [ 
            'symbol' => '🎺',
            'controller' => 'Instrument' 
        ],
        'genre' => [ 
            'symbol' => '🎶',
            'controller' => 'Genre' 
        ]
    ];
    const EXT_NAME = 'MpdbPresentation';

    protected Collection $localizedIndices;
    protected Collection $publishers;
    protected SearchServiceInterface $searchService;

    public function initializeShowAction()
    {
        parent::initializeShowAction();
        $this->publishers = $this->searchService->
            reset()->
            setIndex(IndexPublishersCommand::INDEX_NAME)->
            search()->
            pluck('_source');
    }

    /**
     * @throws SearchServiceNotFoundException
     */
    protected function initializeAction(): void
    {
        $this->localizedIndices = Collection::wrap(self::INDICES)->
            mapWithKeys(function ($array, $key) { return self::localizeIndex($array, $key); });

        $searchService = GeneralUtility::makeInstanceService('search');
        if (is_object($searchService)) {
            $this->searchService = $searchService;
        } else {
            throw new SearchServiceNotFoundException();
        }

        $this->searchService->
            setSize(self::RESULT_COUNT);
    }

    private static function localizeIndex(array $array, string $key): array
    {
        $body = $array;
        $translation = LocalizationUtility::translate($key, self::EXT_NAME);
        $body['translation'] = ucwords($translation);
        return [ $key => $body ];
    }

    protected function getJsCall(Collection $data, Collection $publishers = null): string
    {
        $movingAverages = explode(',', GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('mpdb_presentation')['movingAverages']);
        $config = [
            'movingAverages' => $movingAverages,
            'tableTarget' => self::TABLE_TARGET,
            'graphTarget' => self::GRAPH_TARGET,
            'dashboardTarget' => self::DASHBOARD_TARGET
        ];
        
        $call = 'tx_publisherdb_visualizationController.data = ' . json_encode($data->all()) . ';';
        if ($publishers) {
            $call .= 'tx_publisherdb_visualizationController.publishers = ' . json_encode($publishers->all()) . ';';
        }
        $call .= 'tx_publisherdb_visualizationController.config = ' . json_encode($config) . ';';
        
        return self::callWrap($call);
    }

    protected static function callWrap(string $call): string
    {
        return '<script>document.addEventListener("DOMContentLoaded", _ => {' . $call . '});</script>';
    }

}
