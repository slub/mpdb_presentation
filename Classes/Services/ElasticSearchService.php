<?php

namespace Slub\MpdbPresentation\Services;

use Elasticsearch\Client;
use Illuminate\Support\Collection;
use Slub\MpdbCore\Domain\Model\Publisher;
use Slub\MpdbCore\Common\ElasticClientBuilder;
use Slub\MpdbPresentation\Controller\AbstractController;

class ElasticSearchService implements SearchServiceInterface
{
    protected string $index = '';
    protected string $publisher = '';
    protected string $searchTerm = '';
    protected int $uid = -1;
    protected int $from = 0;
    protected int $size = 0;
    protected array $params = [];
    protected ?Client $client = null;
    protected string $method = 'search';

    public function setIndex(string $index = ''): SearchServiceInterface
    {
        $this->index = $index;

        return $this;
    }

    public function setPublisher(string $publisher = ''): SearchServiceInterface
    {
        if ($publisher != '' && $this->uid != -1) {
            throw new InvalidParamsException('Attempted to restrict search for publisher while searching for uid');
        }

        $this->publisher = $publisher;

        return $this;
    }

    public function setSearchterm(string $searchTerm = ''): SearchServiceInterface
    {
        if ($searchTerm != '' && $this->uid != -1) {
            throw new InvalidParamsException('Attempted to search for term and uid simultaneously');
        }

        $this->searchTerm = $searchTerm;

        return $this;
    }

    public function setUid(int $uid = -1): SearchServiceInterface
    {
        if ($uid != -1 && $this->searchTerm != '') {
            throw new InvalidParamsException('Attempted to search for term and uid simultaneously');
        }
        if ($uid != -1 && $this->publisher != '') {
            throw new InvalidParamsException('Attempted to restrict search for publisher while searching for uid');
        }

        if ($uid == -1) {
            $this->method = 'search';
        } else {
            $this->method = 'get';
        }

        $this->uid = $uid;

        return $this;
    }

    public function setFrom(int $from = 0): SearchServiceInterface
    {
        $this->from = $from;

        return $this;
    }

    public function setSize(int $size = AbstractController::RESULT_COUNT): SearchServiceInterface
    {
        $this->size = $size;

        return $this;
    }

    public function search(): Collection
    {
        if ($this->uid != -1 && $this->index == '') {
            throw new InvalidParamsException('Uid specified but index unspecified');
        }

        $this->createParams();

        switch ($this->method) {
            case 'search':
                $result = $this->client->search($this->params);
                break;
            case 'get':
                $result = $this->client->get($this->params);
                break;
        }
        return Collection::wrap($result['hits']['hits']);
    }

    public function count(): int
    {
        if ($this->method == 'get') {
            throw new InvalidOperationException('Attempt to count a uid based search');
        }

        $this->createParams();
        unset($this->params['body']['size']);
        unset($this->params['body']['from']);

        return $this->client->count($this->params)['count'];
    }

    public function reset(): SearchServiceInterface
    {
        $this->setIndex();
        $this->setPublisher();
        $this->setSearchterm();
        $this->setUid();
        $this->setFrom();
        $this->setSize();
        $this->method = 'search';

        return $this;
    }

    public function init(): bool
    {
        $this->client = ElasticClientBuilder::create()->
            autoconfig()->
            build();
        return true;
    }

    private function createParams(): void
    {
        if ($this->index != '') {
            $this->params['index'] = $this->index;
        }

        if ($this->uid != -1) {
            $this->params['id'] = $this->uid;
        }

        if ($this->method == 'search') {
            $this->params['body'] = [ 'query' => [] ];
            $this->params['body']['size'] = $this->size;
            $this->params['body']['from'] = $this->from;
        }

        if ($this->searchTerm == '') {
            $this->params['body']['query'] = [
                'bool' => [
                    'must' => [ [
                        'match_all' => new \stdClass() 
                    ] ]
                ]
            ];
        } else {
            $this->params['body']['query'] = [
                'bool' => [
                    'must' => [ [ 
                        'query_string' => [
                            'query' => $this->searchTerm
                        ]
                    ] ]
                ]
            ];
        }

        if ($this->publisher != '') {
            $this->params['body']['query']['bool']['must'][] =
                [ 'query_string' => [
                    'query' => $this->publisher . '_*',
                    'fields' => [ 'mvdb_id', 'published_items.mvdb_id', 'works.published_items.mvdb_id' ]
                ]
            ];
        }
    }
}
