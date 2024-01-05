<?php
namespace Slub\MpdbPresentation\Controller;

use Illuminate\Support\Collection;
use Slub\MpdbCore\Lib\DbArray;
use Slub\MpdbPresentation\Controller\AbstractController;
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

    const TABLE_INDEX_NAME = 'person_tables';

    /**
     * action show
     * 
     * @param GndPerson $person
     * @return void
     */
    public function showAction(GndPerson $person)
    {
        $personData = $this->get($person);
        $document = $this->searchService->
            reset()->
            setIndex(self::TABLE_INDEX_NAME)->
            setId($person->getGndId())->
            search();

        $visualizationCall = $this->getJsCall($document, $this->publishers);
        $this->view->assign('publishers', $this->publishers->all());
        $this->view->assign('visualizationCall', $visualizationCall);
        $this->view->assign('tableTarget', self::TABLE_TARGET);
        $this->view->assign('dashboardTarget', self::DASHBOARD_TARGET);
        $this->view->assign('person', $personData);
        $this->view->assign('graphTarget', self::GRAPH_TARGET);
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
}
