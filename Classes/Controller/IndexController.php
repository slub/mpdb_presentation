<?php
namespace Slub\MpdbPresentation\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Slub\MpdbPresentation\Domain\Model\Publisher;

class IndexController extends AbstractController
{

    public function searchAction(array $config = []): ResponseInterface
    {
        $entities = $this->searchService->
            setPublisher($config['publisher'] ?? '')->
            setIndex($config['index'] ?? '')->
            setSearchterm($config['searchTerm'] ?? '')->
            setFrom($config['from'] ?? 0)->
            search();
        $totalItems = $this->searchService->count();
        $publishers = $this->searchService->
            reset()->
            setIndex(Publisher::INDEX_NAME)->
            search()->
            pluck('_source');

        $this->view->assign('entities', $entities->all());
        $this->view->assign('config', $config);
        $this->view->assign('indices', $this->localizedIndices->all());
        $this->view->assign('totalItems', $totalItems);
        $this->view->assign('publishers', $publishers->all());
        $this->view->assign('resultCount', self::RESULT_COUNT);

        return $this->htmlResponse();
    }
}
