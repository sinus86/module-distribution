<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the license that is available in LICENSE file.
 *
 * DISCLAIMER
 *
 * Do not edit this file if you wish to upgrade this extension to newer version in the future.
 */

namespace FjodorMaller\Distribution\Console\Command;

use FjodorMaller\Distribution\Api\Data\DistributionInterface;
use FjodorMaller\Distribution\Api\DistributionRepositoryInterface as RepositoryInterface;
use FjodorMaller\Distribution\Helper\Data as Helper;
use FjodorMaller\Distribution\Model\ResourceModel\Distribution\Collection;
use FjodorMaller\Distribution\Model\ResourceModel\Distribution\CollectionFactory;
use FjodorMaller\Distribution\Model\System\Config\Source\Distribution\Health;
use FjodorMaller\Distribution\Service\Distribution as Service;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CheckCommand
 */
class CheckCommand extends Command
{
    const OPTION_IDS    = 'id';

    const OPTION_ACTIVE = 'active';

    /**
     * @var State
     */
    protected $_state;

    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var Service
     */
    protected $_service;

    /**
     * @var DateTime
     */
    protected $_dateTime;

    /**
     * @var TimezoneInterface
     */
    protected $_timezone;

    /**
     * @var RepositoryInterface
     */
    protected $_repository;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @param State               $state
     * @param Helper              $helper
     * @param LoggerInterface     $logger
     * @param Service             $service
     * @param DateTime            $dateTime
     * @param TimezoneInterface   $timezone
     * @param RepositoryInterface $repository
     * @param CollectionFactory   $collectionFactory
     */
    public function __construct(
        State $state,
        Helper $helper,
        Service $service,
        DateTime $dateTime,
        LoggerInterface $logger,
        TimezoneInterface $timezone,
        RepositoryInterface $repository,
        CollectionFactory $collectionFactory
    ) {
        $this->_state             = $state;
        $this->_helper            = $helper;
        $this->_logger            = $logger;
        $this->_service           = $service;
        $this->_dateTime          = $dateTime;
        $this->_timezone          = $timezone;
        $this->_repository        = $repository;
        $this->_collectionFactory = $collectionFactory;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('fm:distribution:check')
             ->setDescription('Check distribution connections.')
             ->setDefinition([
                 new InputOption(
                     static::OPTION_IDS,
                     null,
                     InputArgument::OPTIONAL,
                     'Distribution ids to check. Example: 2,6,4',
                     ''
                 ),
                 new InputOption(
                     static::OPTION_ACTIVE,
                     null,
                     InputArgument::OPTIONAL,
                     'Check active distributions only.',
                     true
                 ),
             ]);
        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $extra = [];
            $this->_state->setAreaCode(Area::AREA_ADMINHTML);
            $output->write('Starting distribution check');
            /* @var $collection Collection */
            $collection = $this->_collectionFactory->create();
            if ($input->getOption(static::OPTION_ACTIVE)) {
                $extra[] = 'with active filter';
                $collection->addActiveFilter(true);
            }
            $ids = array_map('trim', array_filter(explode(',', $input->getOption(static::OPTION_IDS))));
            if ($ids && count($ids)) {
                $extra[] = 'with specific ids';
                $collection->addFieldToFilter(DistributionInterface::DISTRIBUTION_ID, ['in' => $ids]);
            }
            if (count($extra)) {
                $output->write(' ' . implode(' and ', $extra));
            }
            $output->writeln('.');
            $stats    = [
                'alive'    => 0,
                'bleeding' => 0,
                'dead'     => 0,
            ];
            $count    = $collection->count();
            $progress = new ProgressBar($output, $count);
            $progress->setFormat(
                " %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s%/%estimated:-6s% | %memory:6s% | <info>%message%</info>"
            );
            if ($output->getVerbosity() !== OutputInterface::VERBOSITY_NORMAL) {
                $progress->setOverwrite(false);
            }
            /* @var $distribution DistributionInterface */
            foreach ($collection as $distribution) {
                $dateNow = $this->_dateTime->formatDate(true);
                $isValid = $this->_service->isValidAdapter(
                    $distribution->getAdapter(),
                    $distribution->getOptions()
                );
                $distribution->setHealth(Health::BLEEDING);
                if ($isValid) {
                    $stats[ 'alive' ]++;
                    $distribution->setHealth(Health::ALIVE);
                    $distribution->setLastAlive($dateNow);
                } elseif ($distribution->getLastAlive() && $distribution->getLastCheck()) {
                    $lastAlive = $this->_timezone->date($distribution->getLastAlive());
                    $lastCheck = $this->_timezone->date($distribution->getLastCheck());
                    if (($lastAlive->getTimestamp() - $this->_helper->getDeadInterval()) < $lastCheck->getTimestamp()) {
                        $stats[ 'dead' ]++;
                        $distribution->setHealth(Health::DEAD);
                    }
                } else {
                    $stats[ 'bleeding' ]++;
                }
                $distribution->setLastCheck($dateNow);
                $this->_repository->save($distribution);
                $progress->setMessage(sprintf('%s: %s', $distribution->getName(), $distribution->getAdapter()));
                $progress->advance();
            }
            $output->writeln('');
            $output->writeln('');
            $output->writeln(sprintf(
                '<fg=green>Alive: %d</> / <fg=magenta>Bleeding: %d</> / <fg=red>Dead: %d</>',
                $stats[ 'alive' ],
                $stats[ 'bleeding' ],
                $stats[ 'dead' ]
            ));
            $output->writeln('Distribution check completed.');

            return Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $this->_logger->addError($e);
        }

        return Cli::RETURN_FAILURE;
    }
}
