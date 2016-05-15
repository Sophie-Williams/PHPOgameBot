<?php

namespace App\Model\Queue;
 
use App\Model\CronManager;
use App\Model\Game\BuildManager;
use App\Model\Game\UpgradeManager;
use App\Model\Game\PlanetManager;
use App\Model\ResourcesCalculator;
use Carbon\Carbon;
use Nette\Object;

class QueueConsumer extends Object
{

	/** @var PlanetManager */
	private $planetManager;

	/** @var ResourcesCalculator */
	private $resourcesCalculator;

	/** @var CronManager */
	private $cronManager;

	/** @var ICommandProcessor[] */
	private $processors;

	/** @var QueueRepository */
	private $queueRepository;

	public function __construct(QueueRepository $queueRepository, UpgradeManager $upgradeManager, PlanetManager $planetManager, ResourcesCalculator $resourcesCalculator, CronManager $cronManager, BuildManager $buildManager)
	{
		$this->queueRepository = $queueRepository;
		$this->planetManager = $planetManager;
		$this->resourcesCalculator = $resourcesCalculator;
		$this->cronManager = $cronManager;
		$this->processors = [
			$upgradeManager,
			$buildManager
		];
	}

	public function processQueue()
	{
		$this->planetManager->refreshAllData();
		$queue = $this->queueRepository->loadQueue();
		$success = true;    //aby se zastavilo procházení fronty, když se nepodaří postavit budovu a zpracování tak skončilo
		$lastCommand = null;
		foreach ($queue as $key => $command) {
			foreach ($this->processors as $processor) {
				$this->planetManager->refreshAllResourcesData();
				if ($processor->canProcessCommand($command)) {
					echo 'going to process the command' .  $command->__toString() . PHP_EOL;
					$success = $processor->processCommand($command);
					break;
				}
			}
			$lastCommand = $command;
			if ($success) {
				echo 'command processed successfully' . PHP_EOL;
				unset($queue[$key]);
			} else {
				echo 'command failed to process' . PHP_EOL;
				break;
			}
		}
		$this->queueRepository->saveQueue($queue);
		if (!$success) {
			/** @var Carbon $datetime */
			$datetime = Carbon::now();
			$planet = $this->planetManager->getMyHomePlanet();
			foreach ($this->processors as $processor) {
				if ($processor->canProcessCommand($lastCommand)) {
					echo 'found processor to determine when to process last command' . PHP_EOL;
					$datetime = $processor->getTimeToProcessingAvailable($planet, $lastCommand);
					echo 'new run set to ' . $datetime->__toString() . PHP_EOL;
					break;
				}
			}
			$this->cronManager->setNextStart($datetime);
		}
	}

}