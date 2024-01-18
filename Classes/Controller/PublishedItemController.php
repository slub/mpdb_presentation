<?php

namespace Slub\MpdbPresentation\Controller;

use \TYPO3\CMS\Core\Http\ApplicationType;
use \TYPO3\CMS\Core\Messaging\AbstractMessage;
use \TYPO3\CMS\Core\Pagination\SimplePagination;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use \TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use \TYPO3\CMS\Extbase\Persistence\QueryInterface;
use \Slub\DmNorm\Domain\Model\Person;
use \Slub\MpdbCore\Domain\Model\Publisher;
use \Slub\MpdbCore\Domain\Model\PublisherAction;
use \Slub\MpdbCore\Domain\Model\PublishedItem;
use \Slub\MpdbCore\Domain\Model\PublishedSubitem;
use \Slub\MpdbCore\Domain\Model\Work;
use \Slub\MpdbCore\Lib\DbArray;
use \Slub\MpdbCore\Lib\Tools;

/***
 *
 * This file is part of the "Publisher Database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2021 Matthias Richter <matthias.richter@slub-dresden.de>, SLUB Dresden
 *
 ***/
/**
 * PublishedItemController
 */
class PublishedItemController extends AbstractController
{

    const TABLE_INDEX_NAME = 'published_item_tables';

    /**
     * action show
     * 
     * @param Slub\MpdbCore\Domain\Model\PublishedItem $publisherMakroItem
     * @return void
     */
    public function showAction(PublishedItem $publishedItem)
    {
        $sortByDate = function (PublisherAction $a, PublisherAction $b) {
            return $a->getDateOfAction() < $b->getDateOfAction() ? -1 :
                ( $a->getDateOfAction() == $b->getDateOfAction() ? 0 : 1 );
        };
        $publisherMikroItems = $publishedItem->getPublishedSubitems()->toArray();
        $publisherActions = [];
        // use collection
        foreach ($publisherMikroItems as $publisherMikroItem) {
            $publisherActions = array_merge(
            $publisherActions, 
            $this->publisherActionRepository->findByPublisherMikroItem($publisherMikroItem)->toArray()
            );
        }
        usort($publisherActions, $sortByDate);

        $document = $this->searchService->
            reset()->
            setIndex(self::TABLE_INDEX_NAME)->
            setId($publishedItem->getMvdbId())->
            search();

        $visualizationCall = $this->getJsCall($document, $this->publishers, $publishedItem->getMvdbId());
        $publishers = $this->publisherRepository->findAll();
        $this->view->assign('publishedItem', $publishedItem);
        $this->view->assign('publisherMikroItems', $publisherMikroItems);
        $this->view->assign('publisherActions', $publisherActions);
        $this->view->assign('visualizationCall', $visualizationCall);
        $this->view->assign('tableTarget', self::TABLE_TARGET);
        $this->view->assign('graphTarget', self::GRAPH_TARGET);
        $this->view->assign('dashboardTarget', self::DASHBOARD_TARGET);
    }

}
