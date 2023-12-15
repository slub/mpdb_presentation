<?php

namespace SLUB\MpdbPresentation\Command;

// TODO name sequence steps, buffer results and reuse them

use Elastic\Elasticsearch\Client;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Slub\MpdbCore\Lib\DbArray;
use Slub\MpdbCore\Common\ElasticClientBuilder;
use Slub\MpdbPresentation\Domain\Model\PublishedItem;
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

    protected Collection $publishedItems;
    protected Collection $publishedItemTables;
    protected Client $elasticClient;

    const BULKSIZE = 500;

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

        $this->io->section('Calculating tables for published items');
        $this->calculatePublishedItems();

        $this->io->section('Committing tables for published items');
        $this->commitPublishedItemTables();

        $this->io->success('All tables built and committed.');
        return 0;
    }

    /**
     * Reads published items from API
     *
     * @return void
     */
    protected function fetchPublishedItems(): void
    {
        $params = [
            // TODO name index
            'index'  => 'published_item',
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
    protected function calculatePublishedItems(): void
    {
        $this->io->progressStart(count($this->publishedItems));
        $this->publishedItemTables = $this->publishedItems->
            map(function ($item) { 
                $this->io->progressAdvance();
                return self::samplePublishedItemData($item); 
            });
        $this->io->progressFinish();
    }

    /**
     * Commits indices to Elasticsearch
     *
     * @return void
     */
    protected function commitPublishedItemTables(): void
    {
        $this->io->text('Committing the published item tables index');
        $this->io->progressStart(count($this->publishedItemTables));
        if ($this->client->indices()->exists(['index' => PublishedItem::TABLE_INDEX_NAME])) {
            $this->client->indices()->delete(['index' => PublishedItem::TABLE_INDEX_NAME]);
        }

        $params = [];
        $params = [ 'body' => [] ];
        $bulkCount = 0;
        foreach ($this->publishedItemTables as $document) {
            $params['body'][] = [ 'index' => 
                [ 
                    '_index' => PublishedItem::TABLE_INDEX_NAME,
                    '_id' => $document['id']
                ] 
            ];
            $params['body'][] = json_encode($document);

            $this->io->progressAdvance();
            $bulkCount++;
            if ($bulkCount == self::BULKSIZE) {
                $this->client->bulk($params);
                $params = [ 'body' => [] ];
                $bulkCount = 0;
            }
        }
        $this->io->progressFinish();
        $this->client->bulk($params);
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
            toArray();

        return [
            'id' => $publishedItem['_id'],
            'published_subitems' => $publishedSubitems
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
            'prints_by_date' => $printsByDate->toArray(),
            'prints_per_year' => $printsPerYear->toArray(),
            'prints_by_date_cumulative' => $printsByDateCumulative->toArray(),
            'prints_per_year_cumulative' => $printsPerYearCumulative->toArray()
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
