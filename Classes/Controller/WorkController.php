<?php
namespace Slub\MpdbPresentation\Controller;

use \TYPO3\CMS\Core\Messaging\AbstractMessage;
use \TYPO3\CMS\Core\Pagination\SimplePagination;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser;
use \Slub\MpdbCore\Command\IndexCommand;
use \Slub\MpdbCore\Domain\Model\Publisher;
use \Slub\MpdbCore\Domain\Model\PublisherMakroItem;
use \Slub\MpdbCore\Lib\DbArray;
use \Slub\MpdbCore\Lib\Tools;
use \Slub\MpdbCore\Lib\GndLib;
use \Slub\MpdbPresentation\Command\IndexPublishersCommand;
use \Slub\DmNorm\Domain\Model\GndWork;

/***
 *
 * This file is part of the "Publisher Database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Matthias Richter <matthias.richter@slub-dresden.de>, SLUB Dresden
 *
 ***/
/**
 * WorkController
 */
class WorkController extends AbstractController
{

    const TABLE_INDEX_NAME = 'work_tables';

    /**
     * action show
     * 
     * @param Slub\DmNorm\Domain\Model\GndWork $work
     * @return void
     */
    public function showAction(GndWork $work)
    {
        if ($work->getSuperWork()) {
            $work = $work->getSuperWork();
        }

        $indexedWork = $this->searchService->
            reset()->
            setIndex(IndexCommand::WORK_INDEX)->
            setId($work->getGndId())->
            search();
        $document = $this->searchService->
            reset()->
            setIndex(self::TABLE_INDEX_NAME)->
            setId($work->getGndId())->
            search();
        $hasPrints = $document->
            get('published_items')->
            pluck('published_subitems')->
            flatten(1)->
            pluck('prints_by_date')->
            flatten(1)->
            filter()->
            count();
        $publishedItems = $this->publishedItemRepository->findByContainedWorks($work->getUid());
        $altTitles = explode(' $ ', $work->getAltTitles());

        $visualizationCall = $this->getJsCall($document, $this->publishers, $work->getFullTitle());
        $this->view->assign('publishers', $this->publishers);
        $this->view->assign('visualizationCall', $visualizationCall);
        $this->view->assign('tableTarget', self::TABLE_TARGET);
        $this->view->assign('dashboardTarget', self::DASHBOARD_TARGET);
        $this->view->assign('graphTarget', self::GRAPH_TARGET);
        $this->view->assign('work', $indexedWork);
        $this->view->assign('publishedItems', $publishedItems);
        $this->view->assign('altTitles', $altTitles);
        $this->view->assign('hasPrints', $hasPrints);
    }
}
