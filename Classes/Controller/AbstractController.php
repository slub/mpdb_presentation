<?php
namespace Slub\MpdbPresentation\Controller;

use Illuminate\Support\Collection;
use Slub\MpdbCore\Controller\AbstractController as CoreAbstractController;
use Slub\MpdbCore\Services\SearchServiceInterface;
use Slub\MpdbCore\Services\SearchServiceNotFoundException;
use Slub\MpdbPresentation\Command\IndexPublishersCommand;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

abstract class AbstractController extends CoreAbstractController
{
    const TABLE_TARGET = 'tx_mpdbpresentation_table';
    const GRAPH_TARGET = 'tx_mpdbpresentation_graph';
    const DASHBOARD_TARGET = 'tx_mpdbpresentation_dashboard';
    const RESULT_COUNT = 25;
    const INDICES = [ 
        'person' => [
            'controller' => 'Person',
            'translation' => 'person' ],
        'work' => [
            'controller' => 'Work',
            'translation' => 'work' ],
        'published_item' => [
            'controller' => 'PublishedItem',
            'translation' => 'publishedItem' ]
    ];

    protected Collection $publishers;

    public function initializeShowAction()
    {
        parent::initializeShowAction();
        $this->publishers = $this->searchService->
            reset()->
            setIndex(IndexPublishersCommand::INDEX_NAME)->
            search()->
            pluck('_source');
    }

    protected function getJsCall(Collection $data, Collection $publishers = null, string $title): string
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
        $call .= 'tx_publisherdb_visualizationController.title = "' . $title . '";';
        $call .= 'tx_publisherdb_visualizationController.config = ' . json_encode($config) . ';';
        
        return self::callWrap($call);
    }

    protected static function callWrap(string $call): string
    {
        return '<script>document.addEventListener("DOMContentLoaded", _ => {' . $call . '});</script>';
    }

}
