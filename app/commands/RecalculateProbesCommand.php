<?php

namespace App\Commands;


use App\Enum\FleetMission;
use App\Enum\ProbingStatus;
use App\Enum\Ships;

use App\Model\DatabaseManager;
use App\Model\Game\FleetManager;

use App\Model\Game\ReportReader;
use App\Model\Game\SignManager;
use App\Model\PageObject\FleetInfo;
use App\Model\Queue\Command\SendFleetCommand;


use App\Utils\OgameMath;
use Carbon\Carbon;
use Nette\DI\Container;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RecalculateProbesCommand
 * @package App\Commands
 * @author: Matěj Račinský 
 */
class RecalculateProbesCommand extends Command {

	/** @var Container */
	private $container;

	public function __construct(Container $container)
	{
		parent::__construct();
		$this->container = $container;
	}

	protected function configure()
	{
		$this->setName('bot:probes')
			->setDescription('It recalculates probes after espionage technology leveled up.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		/** @var DatabaseManager $databaseManager */
		$databaseManager = $this->container->getByType(ReportReader::class);
		$me = $databaseManager->getMe();
		$myLevel = $me->getEspionageTechnologyLevel();
		$playersWithAllInfo = $databaseManager->getPlayersWithAllInfo();
		$allInfoResult = ProbingStatus::_(ProbingStatus::GOT_ALL_INFORMATION)->getMinimalResult();
		foreach ($playersWithAllInfo as $player) {
			$enemyLevel = $player->getEspionageTechnologyLevel();
			$probesToAllInfo = OgameMath::calculateProbesToSend($myLevel, $enemyLevel, $allInfoResult);
			$player->setProbesToLastEspionage($probesToAllInfo);
		}
//		$databaseManager->flush();
		return 0; // zero return code means everything is ok
	}


} 