<?php

namespace App\Commands;
use App\Enum\Building;
use App\Fixtures\RootFixture;
use App\Model\BuildingsManager;
use App\Model\PlanetManager;
use App\Model\ResourcesCalculator;
use App\Model\SignManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TestCommand
 * @package App\Commands
 * @author: Matěj Račinský 
 */
class TestCommand extends Command {

	/** @var SignManager */
	private $signManager;

	/** @var PlanetManager */
	private $planetManager;

	/** @var ResourcesCalculator */
	private $resourcesCalculator;

	/**
	 * TestCommand constructor.
	 * @param SignManager $signManager
	 * @param PlanetManager $planetManager
	 * @param ResourcesCalculator $resourcesCalculator
	 */
	public function __construct(SignManager $signManager, PlanetManager $planetManager, ResourcesCalculator $resourcesCalculator)
	{
		parent::__construct();
		$this->signManager = $signManager;
		$this->planetManager = $planetManager;
		$this->resourcesCalculator = $resourcesCalculator;
	}

	protected function configure()
	{
		$this->setName('bot:search-inactive')
			->setDescription('Searches for inactive planets and adds them to database.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->signManager->signIn();
		$this->planetManager->refreshResourceData();
		$planet = $this->planetManager->getMyHomePlanet();
		$this->resourcesCalculator->getTimeToEnoughResourcesForBuilding($planet, Building::_(Building::SOLAR_POWER_PLANT), 8);
		$output->writeln('Hello world');
		return 0; // zero return code means everything is ok
	}


} 