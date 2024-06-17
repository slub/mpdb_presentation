<?php

namespace Slub\MpdbPresentation\Command;

use Elastic\Elasticsearch\Client;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Slub\MpdbCore\Lib\DbArray;
use Slub\MpdbCore\Common\ElasticClientBuilder;
use Slub\MpdbPresentation\Domain\Model\PublishedItem;
use Slub\MpdbPresentation\Controller\PublishedItemController;
use Slub\MpdbPresentation\Controller\WorkController;
use Slub\MpdbPresentation\Controller\PersonController;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;

/**
 * Calculate Tables Command class
 *
 * @author Matthias Richter <matthias.richter@slub-dresden.de>
 * @package TYPO3
 * @subpackage publisher_db
 * @access public
 */

class CalculateTablesCommand extends Command
{

    protected Client $elasticClient;
    protected string $prefix;
    protected Collection $composers;
    protected Collection $composerTables;
    protected Collection $publishedItemTables;
    protected Collection $publishedItems;
    protected Collection $works;
    protected Collection $workTables;

    const BULKSIZE = 100;

    /**
     * Pre-Execution configuration
     *
     * @return array
     */
    protected function configure(): void
    {
        $this->setHelp('Calculate tabular data for frontend visualisations.');
        $this->setDescription('Calculate tabular data for frontend visualisations.');
    }

    /**
     * Initialization steps
     *
     * @return void
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->client = ElasticClientBuilder::create()->
            autoconfig()->
            build();
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('mpdb_core');
        $this->prefix = $extConf['prefix'];
        $this->publishedItems = new Collection();
        $this->publishedItemTables = new Collection();
    }

    /**
     * Executes the command to build indices from Database
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title($this->getDescription());

        $this->io->section('Fetching published items');
        $this->fetchPublishedItems();

        $this->io->section('Grouping published items');
        $this->groupPublishedItems();

        $this->io->section('Calculating tables');
        $this->calculateTables();

        $this->io->section('Committing tables');
        $this->commitPublishedItemTables();

        $this->io->success('All tables built and committed.');
        return 0;
    }

    protected function groupPublishedItems(): void
    {
        $this->io->progressStart(count($this->publishedItems));
        $workGroups = [];
        $composerGroups = [];
        foreach ($this->publishedItems as $publishedItem) {
            $source = $publishedItem['_source'];
            $works = Collection::wrap($source['works']);
            $workIds = $works->pluck('gnd_id');
            $composers = $works->pluck('composers')->collapse();
            $composerIds = $composers->pluck('gnd_id');

            foreach($workIds as $workId) {
                if (!isset($workGroups[$workId])) {
                    $workGroups[$workId] = [];
                }
                $workGroups[$workId][] = $source['mvdb_id'];
            }

            foreach($composerIds as $composerId) {
                if (!isset($composerGroups[$composerId])) {
                    $composerGroups[$composerId] = [];
                }
                $composerGroups[$composerId][] = $source['mvdb_id'];
            }
            $this->io->progressAdvance();
        }
        $this->io->progressFinish();

        $this->works = Collection::wrap($workGroups);
        $this->composers = Collection::wrap($composerGroups);
    }

    /**
     * Reads published items from API
     *
     * @return void
     */
    protected function fetchPublishedItems(): void
    {
        $coreExtConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('mpdb_core');
        $prefix = $coreExtConf['prefix'];

        $params = [
            // TODO name index
            'index'  => $prefix . 'published_item',
            'body'   => [
                'query' => [
                    'match_all' => new \stdClass()
                ]
            ]
        ];
        $count = $this->client->count($params)['count'];
        $current = 0;
        $this->io->progressStart($count);

        $params['scroll'] = '10s';
        $params['size'] = self::BULKSIZE;

        // Execute the search
        // The response will contain the first batch of documents
        // and a scroll_id
        $response = $this->client->search($params);

        // Now we loop until the scroll "cursors" are exhausted
        while (isset($response['hits']['hits']) && count($response['hits']['hits']) > 0) {

            $current += self::BULKSIZE;
            if ($current < $count) {
                $this->io->progressAdvance(self::BULKSIZE);
            }
            $this->publishedItems = $this->publishedItems->concat($response['hits']['hits']);

            // When done, get the new scroll_id
            // You must always refresh your _scroll_id!  It can change sometimes
            $scroll_id = $response['_scroll_id'];

            // Execute a Scroll request and repeat
            $response = $this->client->scroll([
                'body' => [
                    'scroll_id' => $scroll_id,  //...using our previously obtained _scroll_id
                    'scroll'    => '10s'        // and the same timeout window
                ]
            ]);
        }
        $this->io->progressFinish();
    }

    /**
     * Calculates by year tables for published items
     *
     * @return array
     */
    protected function calculateTables(): void
    {
        $this->count = count($this->publishedItems) + count($this->works) + count($this->composers);
        $this->io->progressStart($this->count);
        $this->publishedItemTables = $this->publishedItems->
            mapWithKeys(function ($item) { 
                $this->io->progressAdvance();
                return self::samplePublishedItemData($item); 
            });
        $this->workTables = $this->works->
            mapWithKeys(function ($work, $key) {
                $this->io->progressAdvance();
                return self::connectPublishedItems($work, $key, $this->publishedItemTables);
            });
        $this->composerTables = $this->composers->
            mapWithKeys(function ($work, $key) {
                $this->io->progressAdvance();
                return self::connectPublishedItems($work, $key, $this->publishedItemTables);
            });
        $this->io->progressFinish();
    }

    protected static function connectPublishedItems(array $entity, string $key, Collection $publishedItemTables)
    {
        $connectedItems = Collection::wrap($entity)->map(function ($item) use ($publishedItemTables) { return $publishedItemTables[$item]; });

        return [ $key => [ 'id' => $key, 'published_items' => $connectedItems ] ];
    }

    protected function getParams(array $entity, string $index): array
    {
        $params = [];
        $params[] = [ 'index' =>
            [
                '_index' => $this->prefix . $index,
                '_id' => $entity['id']
            ]
        ];
        $params[] = json_encode($entity);

        return $params;
    }

    protected function commitChunk(Collection $chunk, string $index): void
    {
        $params = $chunk->map(function($entity) use ($index) { return self::getParams($entity, $index); })->values()->collapse();
        $this->client->bulk([ 'body' => $params->all() ]);
    }
    /**
     * Commits indices to Elasticsearch
     *
     * @return void
     */
    protected function commitPublishedItemTables(): void
    {
        if ($this->client->indices()->exists(['index' => $this->prefix . PublishedItemController::TABLE_INDEX_NAME])->asBool()) {
            $this->client->indices()->delete(['index' => $this->prefix . PublishedItemController::TABLE_INDEX_NAME]);
        }
        if ($this->client->indices()->exists(['index' => $this->prefix . WorkController::TABLE_INDEX_NAME])->asBool()) {
            $this->client->indices()->delete(['index' => $this->prefix . WorkController::TABLE_INDEX_NAME]);
        }
        if ($this->client->indices()->exists(['index' => $this->prefix . PersonController::TABLE_INDEX_NAME])->asBool()) {
            $this->client->indices()->delete(['index' => $this->prefix . PersonController::TABLE_INDEX_NAME]);
        }

        $this->io->progressStart($this->count);

        $this->publishedItemTables->chunk(self::BULKSIZE)->each(
            function($chunk) { 
                $this->io->progressAdvance(self::BULKSIZE);
                $this->commitChunk($chunk, PublishedItemController::TABLE_INDEX_NAME);
            });
        $this->workTables->chunk(self::BULKSIZE)->each(
            function($chunk) { 
                $this->io->progressAdvance(self::BULKSIZE);
                $this->commitChunk($chunk, WorkController::TABLE_INDEX_NAME);
            });
        $this->composerTables->chunk(self::BULKSIZE)->each(
            function($chunk) { 
                $this->io->progressAdvance(self::BULKSIZE);
                $this->commitChunk($chunk, PersonController::TABLE_INDEX_NAME);
            });

        $this->io->progressFinish();
    }

    protected static function mapQuantities(array $item) {
        return [ $item['date'] => $item['quantity'] ];
    }

    protected static function calculateMovingAverages(Collection $collection, int $years): array
    {
        $span = intdiv($years, 2);
        $yearKeys = $collection->pluck('date');
        $minYear = $yearKeys->first() - $span;
        $maxYear = $yearKeys->last() + $span;
        $mappedQuantities = $collection->mapWithKeys(function($item, $_) {
            return self::mapQuantities($item);
        });
        $timespan = Collection::wrap(range($minYear, $maxYear));

        $result = [];
        foreach ($timespan as $year) {
            $sum = 0;
            foreach (range(-$span, $span) as $offset) {
                if (isset($mappedQuantities[$year+$offset])) {
                    $sum += $mappedQuantities[$year+$offset];
                }
            }
            $result[] = [
                'date' => $year,
                'quantity' => round($sum / $years)
            ];
        }

        return $result;
    }

    protected static function samplePublishedItemData(array $publishedItem): array
    {
        $publishedSubitems = Collection::wrap($publishedItem['_source']['published_subitems'])->
            map(function ($item) { return self::samplePublishedSubitemData($item); })->
            all();

        return [ 
            $publishedItem['_id'] =>
            [
                'id' => $publishedItem['_id'],
                'published_subitems' => $publishedSubitems
            ]
        ];
    }

    protected static function samplePublishedSubitemData(array $publishedSubitem): array
    {
		$extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('mpdb_presentation');

        if ($publishedSubitem['prints'] == null) {
            return [
                'id' => $publishedSubitem['mvdb_id'],
                'prints_by_date' => $publishedSubitem['prints'],
                'prints_per_year' => null
            ];
        }

        $printsByDate = Collection::wrap($publishedSubitem['prints'])->
            map(function ($prints) { return self::groupByDate($prints); })->
            sortBy('date');

        $printsPerYear = Collection::wrap($publishedSubitem['prints'])->
            map(function ($print) { return self::samplePrintData($print); })->
            groupBy('date')->
            map(function ($prints) { return self::sumPrints($prints); })->
            sortBy('date')->
            values();

        $printsByDateCumulative = Collection::wrap($printsByDate)->
            map(function ($_, $key) use ($printsByDate) {
                return self::cumulativeSum($key, $printsByDate);
            });

        $printsPerYearCumulative = Collection::wrap($printsPerYear)->
            map(function ($_, $key) use ($printsPerYear) {
                return self::cumulativeSum($key, $printsPerYear);
            });

        $result = [ 
            'id' => $publishedSubitem['mvdb_id'],
            'prints_by_date' => $printsByDate->values(),
            'prints_per_year' => $printsPerYear->values(),
            'prints_by_date_cumulative' => $printsByDateCumulative->values(),
            'prints_per_year_cumulative' => $printsPerYearCumulative->values()
        ];

        foreach(explode(',', $extConf['movingAverages']) as $years) {
            $result['prints_per_year_ma_' . $years] = self::calculateMovingAverages($printsPerYear, $years);
        }

        
        return $result;
    }

    protected static function sumPrints(Collection $prints): array
    {
        return [ 
            'date' => $prints[0]['date'],
            'quantity' =>
                Collection::wrap($prints)->
                    sum('quantity')
        ];
    }

    protected static function groupByDate(array $prints): array
    {
        return [
            'date' => $prints['date_of_action'],
            'quantity' => $prints['quantity']
        ];
    }

    protected static function samplePrintData(array $print): array
    {
        $year = \DateTime::createFromFormat('Y-m-d', $print['date_of_action'])->
            format('Y');
        return [
            'date' => $year,
            'quantity' => $print['quantity']
        ];
    }

    protected static function cumulativeSum(int $key, Collection $collection): array
    {
        $predecessor = $key - 1;
        if (isset($collection[$predecessor])) {
            $cumulativeSum = $collection[$key]['quantity'] + 
                self::cumulativeSum($predecessor, $collection)['quantity'];
        } else {
            $cumulativeSum = $collection[$key]['quantity'];
        }

        return [
            'date' => $collection[$key]['date'],
            'quantity' => $cumulativeSum
        ];
    }

}
