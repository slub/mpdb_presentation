<?php
namespace Slub\MpdbPresentation\Controller;

use Illuminate\Support\Collection;
use Slub\MpdbCore\Lib\DbArray;
use Slub\MpdbCore\Controller\AbstractController;
use Slub\DmNorm\Domain\Model\GndPerson;
use Slub\MpdbCore\Domain\Model\Publisher;
use Slub\MpdbCore\Domain\Model\Work;
use TYPO3\CMS\Extbase\Persistence\Repository;

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
 * PersonController
 */
class PersonController extends AbstractController
{

    const GRAPH_TARGET = 'person-graph';
    const PAGINATION_ITEMS_COUNT = 25;

    /**
     * action list
     * 
     * @param Publisher $publisher
     * @param string $searchTerm
     * @param int $from
     * @return void
     */
    public function listAction(Publisher $publisher = null, string $searchTerm = '', int $from = 0)
    {
        [ $persons, $total ] = $this->list($searchTerm, $from, $publisher);
        $paginationRange = floor($total / self::PAGINATION_ITEMS_COUNT);

        // this action should be rewritten after elasticsearch implementation
        $getPersonsWorks = function (GndPerson $person) { 
            return $this
                ->workRepository
                ->listForPerson($person, $this->level)
                ->toArray(); 
        };
        $filterTestPublisher = function (Publisher $publisher) { 
            return $publisher->getShortHand() != 'AA';
        };

        $personRange = false;
        if ($searchTerm) {
            $persons = $this->search($searchTerm, $from, $this->gndPersonRepository);
            $works = $this->search($searchTerm, $from, $this->workRepository, $publisher);
            $result = $this->makeResult($persons, $works);
        } else {
            $persons = $this
                ->gndPersonRepository
                ->list($from, $this->level)
                ->toArray();
            $works = (new DbArray())
                ->set( $persons )
                ->map( $getPersonsWorks )
                ->merge()
                ->filter( self::filterPublisher($publisher) )
                ->toArray();
            $result = $this->makeResult($persons, $works);
            $pages = $this->gndPersonRepository->count($this->level) / 25;
            $personRange = range(0, floor($pages));
        }

        $publishers = (new DbArray())
            ->set( $this
                ->publisherRepository
                ->findAll()
                ->toArray()
            )
            ->filter( $filterTestPublisher )
            ->toArray();

        $this->view->assign('persons', $result);
        $this->view->assign('currentPage', $from);
        $this->view->assign('personRange', $personRange);
        $this->view->assign('searchTerm', $searchTerm);
        $this->view->assign('publishers', $publishers);
        $this->view->assign('currentPublisher', $publisher);
    }

    /**
     * action show
     * 
     * @param GndPerson $person
     * @return void
     */
    public function showAction(GndPerson $person)
    {
        $personData = $this->get($person);
        $this->view->assign('person', $personData);
        $this->view->assign('graphTarget', self::GRAPH_TARGET);
        $this->view->assign('personGraph', $this->renderPersonGraph($personData, self::GRAPH_TARGET));
    }

    protected function renderPersonGraph($personData, $target)
    {
        return "<script>document.addEventListener('DOMContentLoaded', _ => new PersonGraph({target:'" .
            $target . "',data:" . json_encode($personData) . "}));</script>";
    }

    protected function get(GndPerson $person): array
    {
        $params = [
            'index' => 'person',
            'body' => [
                'query' => [
                    'match' => [
                        'uid' => $person->getUid()
                    ]
                ]
            ]
        ];

        $personData = $this->elasticClient->search($params);
        return $personData['hits']['hits'][0]['_source'];
    }

    protected function list(): array
    {
        $params = [
            'index' => 'person',
            'body' => [
                'query' => []
            ]
        ];

        $personData = $this->elasticClient->search($params);
        $total = $personData['hits']['total']['value'];
        return [ $total,
            Collection::wrap($personData['hits']['hits'])->
            pluck('_source')
        ];
    }

    /**
     * action new
     * 
     * @return void
     */
    public function newAction()
    {
    }

    /**
     * action loadData
     * 
     * @param GndPerson $person
     * @return void
     */
    public function loadDataAction(GndPerson $person)
    {
        // throw away after refactoring show
        $works = $this->workRepository->findByFirstcomposer($person)->toArray();
        $outWorks = [];

        foreach ($works as $work) {
            if ($this->publisherMakroItemRepository->lookupByWork($work)) {
                foreach ($this->publisherMakroItemRepository->lookupByWork($work) as $makro) {
                    if ($this->level == 0 || $makro->getFinal() == 2) {
                        $outMikros = [];
                        $mikros = $this->publisherMikroItemRepository->lookupByWork($work);
                        foreach ($mikros as $mikro) {
                            if ($this->level == 0 || ($mikro->getPublisherMakroItem() && $mikro->getPublisherMakroItem()->getFinal() == 2)) {
                                $outMikros[] = [$mikro, $this->publisherActionRepository->findByPublisherMikroItem($mikro)];
                            }
                        }
                        $outWorks[] = [$work, $outMikros];
                        break;
                    }
                }
            }
        }
        $this->view->assign('works', $outWorks);
    }

    /**
     * action searchPerson
     * 
     * @param GndPerson $person
     * @param Publisher $publisher
     * @return void
     */
    public function searchPersonAction(GndPerson $person, Publisher $publisher = null)
    {
        $works = (new DbArray())
            ->set( $this->workRepository->findByFirstcomposer($person)->toArray() )
            ->filter( $this->filterPublicEntities )
            ->filter( self::filterPublisher($publisher) )
            ->toArray();
        $this->view->assign('works', $works);
    }

    private static function filterPublisher(Publisher $publisher = null) 
    {
        return $publisher ? 
            function (Work $work) use ($publisher) {
                return str_contains($work->getPublishers(), $publisher->getShorthand());
            } :
            function () { return true; };
    }

    private function filterPublicEntities()
    {
        return function ($item) {
            return $item->getFinal() >= $this->level;
        };
    }

    private function makeResult(array $persons, array $works)
    {
        // should this be moved to repository?
        $getFirstComposer = function ($work) { 
            return $work->getFirstcomposer(); 
        };
        $getGnd = function ($person) { 
            return $person ? $person->getGndId() : '';
        };
        $personGroupedArrayKey = function ($groupedWorks) { 
            return ['person' => $groupedWorks['groupObject'], 'works' => $groupedWorks['group']];
        };
        $personGroupedArrayValues = function ($groupedWorks) { 
            return $groupedWorks['groupObject'] ? $groupedWorks['groupObject']->getGndId() : '';
        };
        $key = function ($person) { 
            return ['person' => $person, 'works' => []];
        };

        $groupedWorks = (new DbArray())
            ->set($works)
            ->group( $getFirstComposer, $getGnd )
            ->map( $personGroupedArrayKey, $personGroupedArrayValues );

        return (new DbArray())
            ->set($persons)
            ->map( $key, $getGnd )
            ->concat($groupedWorks->toArray())
            ->toArray();
    }

    private function search(
        string $searchTerm, 
        int $from, 
        Repository $repository, 
        Publisher $publisher = null
    )
    {
        // should this be moved to repository?
        $getRelevantStrings = function ($string) { return strlen($string) != 1; };

        $getSubSearches = function ($string) use ($repository, $from) { 
            return $repository->search($string, $this->level, $from)->toArray();
        };

        $calcRelevance = function ($groupedItem) use ($searchTerm) {
            $baseRelevance = 0;
            if (getType($groupedItem['groupObject']) == 'Person' && $groupedItem['item']->getTitle() == $searchTerm) {
                $baseRelevance = 1;
            }
            if (getType($groupedItem['groupObject']) == 'Work' && $groupedItem['item']->getName() == $searchTerm) {
                $baseRelevance = 1;
            }
            return ['item' => $groupedItem['groupObject'], 'relevance' => count($groupedItem['group'])];
        };

        $diffRelevance = function ($a, $b) { return $a['relevance'] - $b['relevance']; };

        $umlauts = ['ae', 'oe', 'ue', 'ss', 'ä', 'ö', 'ü', 'é'];
        $purgedTerm = str_replace($umlauts, ' ', $searchTerm);

        return (new DbArray())
            ->set(explode(' ', $purgedTerm))
            ->filter( $getRelevantStrings )
            ->map( $getSubSearches )
            ->merge()
            ->group(
                function ($item) { return $item; }, 
                function ($item) { return $item->getGndId(); }
            )
            ->map( $calcRelevance )
            ->sort( $diffRelevance )
            ->map(function ($groupedItem) { return $groupedItem['item']; })
            ->filter( self::filterPublisher($publisher) )
            ->toArray();
    }

    // move to field instead
    private function filterPublic(array $personArray)
    {
        // can filterFinal be replaced by filterPublicEntities?
        $makeResult = function ($person) {
            $filterFinal = function ($item) {
                return $item->getFinal() == 2;
            };
            $hasPublicItems = function ($work) use ($filterFinal) {
                return (new DbArray())
                    ->set($work->getPublisherMakroItems()->toArray())
                    ->filter( $filterFinal )
                    ->reduceOr();
            };
            $getResult = function ($work) use ($filterFinal) {
                $publicItems = (new DbArray())
                    ->set($work->getPublisherMakroItems()->toArray())
                    ->filter( $filterFinal )
                    ->toArray();
                return 
                    [
                        'title' => $work->getTitle(), 
                        'publisherMakroItems' => $publicItems, 
                        'publishers' => $work->getPublishers(), 
                        'fullTitle' => $work->getFullTitle(), 
                        'opusNo' => $work->getOpusNo()
                    ];
            };

            $filteredWorks = (new DbArray())
                ->set($person->getWorks()->toArray())
                ->filter( $hasPublicItems )
                ->map( $getResult )
                ->toArray();

            return 
                [
                    'name' => $person->getName(), 
                    'works' => $filteredWorks
                ];
        };
        $hasWorks = function ($person) {
            return $person['works'] != []; 
        };

        return (new DbArray())
            ->set($personArray )
            ->map( $makeResult )
            ->filter( $hasWorks )
            ->toArray();
    }

}
