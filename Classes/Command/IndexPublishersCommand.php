<?php

namespace Slub\MpdbPresentation\Command;

use Elastic\Elasticsearch\Client;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Slub\MpdbCore\Common\ElasticClientBuilder;
use Slub\MpdbPresentation\Domain\Model\Publisher;
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

class IndexPublishersCommand extends Command
{

    const NAME_COLNAME = 'name';
    const SHORTHAND_COLNAME = 'shorthand';
    const PUBLIC_COLNAME = 'public';
    const TABLE_NAME = 'tx_mpdbcore_domain_model_publisher';
    const INDEX_NAME = 'publishers';

    protected string $prefix;

    /**
     * Pre-Execution configuration
     *
     * @return array
     */
    protected function configure(): void
    {
        $this->setHelp('Index publishers for frontend use.');
        $this->setDescription('Index publishers for frontend use.');
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
    }

    /**
     * Executes the command to build indices from Database
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$coreExtConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('mpdb_core');
        $this->prefix = $coreExtConf['prefix'];

        $qb = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_NAME);
        $qb->select(
                'uid',
                self::NAME_COLNAME . ' AS name',
                self::SHORTHAND_COLNAME . ' AS shorthand',
                self::PUBLIC_COLNAME . ' AS public'
            )->
            from(self::TABLE_NAME);

        if ($this->client->indices()->exists(['index' => $prefix . self::INDEX_NAME])->asBool()) {
            $this->client->indices()->delete(['index' => $prefix . self::INDEX_NAME]);
        }

        Collection::wrap($qb->execute()->fetchAll())->
            filter(function ($publisher) { return $this->isPublic($publisher); })->
            each(function ($publisher) { $this->indexPublisher($publisher); });

        return 0;
    }

    private static function isPublic(array $publisher): bool {
        return (bool) $publisher[self::PUBLIC_COLNAME];
    }

    private function indexPublisher(array $publisher): void {
        unset($publisher[self::PUBLIC_COLNAME]);

        $params = [
            'index' => $this->prefix . self::INDEX_NAME,
            'id' => $publisher['uid'],
            'body' => $publisher ];

        $this->client->index($params);
    }

}
