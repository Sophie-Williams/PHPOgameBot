<?php

namespace App\Model\Game;

use App\Enum\MenuItem;
use App\Enum\Ships;
use App\Model\DatabaseManager;
use App\Model\Entity\Planet;
use App\Model\PageObject\FleetInfo;
use App\Model\Queue\Command\IBuildCommand;
use App\Model\Queue\Command\ICommand;
use App\Model\Queue\Command\IEnhanceCommand;
use App\Model\Queue\Command\ProbePlayersCommand;
use App\Model\Queue\Command\SendFleetCommand;
use app\model\queue\ICommandProcessor;
use App\Model\ResourcesCalculator;
use App\Utils\OgameParser;
use App\Utils\Random;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Kdyby\Monolog\Logger;
use Nette\Object;

class FleetManager extends Object implements ICommandProcessor
{

	/** @var \AcceptanceTester */
	private $I;

	/** @var PlanetManager */
	private $planetManager;

	/** @var Menu */
	private $menu;

	/** @var Logger */
	private $logger;

	/** @var DatabaseManager */
	private $databaseManager;

	/** @var ResourcesCalculator */
	private $resourcesCalculator;

	/** @var FleetInfo */
	private $fleetInfo;

	public function __construct(\AcceptanceTester $I, PlanetManager $planetManager, Menu $menu, Logger $logger, DatabaseManager $databaseManager, ResourcesCalculator $resourcesCalculator, FleetInfo $fleetInfo)
	{
		$this->I = $I;
		$this->planetManager = $planetManager;
		$this->menu = $menu;
		$this->logger = $logger;
		$this->databaseManager = $databaseManager;
		$this->resourcesCalculator = $resourcesCalculator;
		$this->fleetInfo = $fleetInfo;
	}

	public function canProcessCommand(ICommand $command) : bool
	{
		return $command instanceof SendFleetCommand;
	}

	public function getTimeToProcessingAvailable(ICommand $command) : Carbon
	{
		/** @var SendFleetCommand $command */
		//todo: later add checking for amount of ships in planet from command
		$minimalTime = OgameParser::getNearestTime($this->fleetInfo->getMyFleetsReturnTimes());

		if ($command->waitForResources()) {
			$planet = $this->planetManager->getPlanet($command->getCoordinates());
			$timeToResources = $this->resourcesCalculator->getTimeToEnoughResources($planet, $command->getResources());
			$minimalTime = $minimalTime->max($timeToResources);
		}

		return $minimalTime;
	}

	public function isProcessingAvailable(SendFleetCommand $command) : bool
	{
		//todo: later add checking for amount of ships in planet from command
		$this->menu->goToPage(MenuItem::_(MenuItem::FLEET));
		$fleets = $this->I->grabTextFrom('#inhalt > div:nth-of-type(2) > #slots > div:nth-of-type(1) > span.tooltip');
		list($occupied, $total) = OgameParser::parseSlash($fleets);
		$freeSlots = $occupied < $total;

		$enoughResources = true;
		if ($command->waitForResources()) {
			$planet = $this->planetManager->getPlanet($command->getCoordinates());
			$enoughResources = $this->resourcesCalculator->isEnoughResources($planet, $command->getResources());
		}
		return $freeSlots && $enoughResources;
	}

	public function processCommand(ICommand $command) : bool
	{
		/** @var SendFleetCommand $command */
		return $this->sendFleet($command);
	}

	private function sendFleet(SendFleetCommand $command) : bool
	{
		$I = $this->I;

		$to = $command->getTo();

		$planet = $this->planetManager->getPlanet($command->getCoordinates());
		$this->menu->goToPlanet($planet);

		if (!$this->isProcessingAvailable($command)) {
			$this->logger->addDebug('Processing not available.');
			return false;
		}
		$this->logger->addDebug('Processing available, starting to process the command.');
		$this->menu->goToPage(MenuItem::_(MenuItem::FLEET));
		foreach ($command->getFleet()->getNonZeroShips() as $ship => $count) {
			$I->fillField(Ships::_($ship)->getFleetInputSelector(), $count);
		}
		$I->click('#continue.on');
		usleep(Random::microseconds(1.5, 2.5));

		$I->fillField('input#galaxy', $to->getGalaxy());
		$I->fillField('input#system', $to->getSystem());
		$I->fillField('input#position', $to->getPlanet());
		usleep(Random::microseconds(0.5, 1));
		$I->click('#continue.on');

		if ($I->seeElementExists('#fadeBox span.failed')) {
			throw new NonExistingPlanetException();
		}
		
		usleep(Random::microseconds(1.5, 2.5));

		$I->click($command->getMission()->getMissionSelector());
		usleep(Random::microseconds(1, 2));

		$I->fillField('input#metal', $command->getResources()->getMetal());
		$I->fillField('input#crystal', $command->getResources()->getCrystal());
		$I->fillField('input#deuterium', $command->getResources()->getDeuterium());

		$I->click('#start.on');
		usleep(Random::microseconds(1.5, 2.5));

		return true;
	}
}

class NonExistingPlanetException extends \Exception {}