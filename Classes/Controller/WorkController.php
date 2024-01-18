<?php
namespace Slub\MpdbPresentation\Controller;

use \TYPO3\CMS\Core\Messaging\AbstractMessage;
use \TYPO3\CMS\Core\Pagination\SimplePagination;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser;
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
        if (isset($GLOBALS['BE_USER'])) {
            $level = -1;
        } else {
            $level = 2;
        }
        if ($work->getSuperWork()) {
            $work = $work->getSuperWork();
        }
        //$publisherMakroItems = $this->publisherMakroItemRepository->lookupByWork($work);//->toArray();
        //$publisherMikroItems = $this->publisherMikroItemRepository->lookupByWork($work)->toArray();

        $outMakros = [];
        $outMikros = [];
        //foreach ($publisherMakroItems as $makro) {
            //if ($makro->getFinal() >= $level) {
                //$outMakros[] = $makro;
                //$outMikros = array_merge($outMikros, $makro->getPublisherMikroItems()->toArray());
            //}
        //}

        $publisherActions = [];
        foreach ($outMikros as $publisherMikroItem) {
            $publisherActions = array_merge(
            $publisherActions, 
            $this->publisherActionRepository->findByPublisherMikroItem($publisherMikroItem)->toArray()
            );
        }

        $getPublishedItems = function ($subWork) {
            $makros = $this->publisherMakroItemRepository->lookupByWork($subWork, $this->level);
            foreach ($makros as $makro) {
                foreach ($makro->getPublisherMikroItems() as $mikro) {
                    $mikros[] = $mikro;
                }
            }
            return [
                'subWork' => $subWork,
                'makros' => $makros,
                'mikros' => $mikros ?? []
            ];
        };

        /*
        $subWorks = (new DbArray())
            ->set($this->workRepository->findBySuperWork($work)->toArray())
            ->map( $getPublishedItems )
            ->filter( function ($subwork) { return $subwork['makros'] != []; })
            ->toArray();

		$sw = $this->workRepository->findBySuperWork($work);
        foreach ($subWorks as $subWork) {
			$subPublisherMakroItems = $this->publisherMakroItemRepository->lookupByWork($work);

            $outMikros = [];
            $publisherActions = [];
			foreach ($subPublisherMakroItems as $makro) {
				if ($makro->getFinal() >= $level) {
					$outMakros[] = $makro;
					$outMikros = array_merge($outMikros, $makro->getPublisherMikroItems()->toArray());
				}
			}
            foreach ($outMikros as $mikro) {
                $publisherActions = array_merge($publisherActions, $mikro->getPublisherActions()->toArray());
            }
            foreach ($subWork['mikros'] as $mikro) {
                $publisherActions = array_merge($publisherActions, $mikro->getPublisherActions()->toArray());
            }
        }
         */

        $document = $this->searchService->
            reset()->
            setIndex(self::TABLE_INDEX_NAME)->
            setId($work->getGndId())->
            search();

        $visualizationCall = $this->getJsCall($document, $this->publishers, $work->getFullTitle());
        $this->view->assign('publishers', $this->publishers);
        $this->view->assign('visualizationCall', $visualizationCall);
        $this->view->assign('tableTarget', self::TABLE_TARGET);
        $this->view->assign('dashboardTarget', self::DASHBOARD_TARGET);
        $this->view->assign('graphTarget', self::GRAPH_TARGET);
        $this->view->assign('work', $work);
        //$this->view->assign('subWorks', $subWorks);
        //$this->view->assign('publisherMikroItems', $outMikros);
        //$this->view->assign('publisherActions', $publisherActions);
        //$this->view->assign('publisherMakroItems', $outMakros);
    }
}
