<?php

namespace App\Model\Queue\Command;
 
use App\Enum\Building;
use App\Enum\Upgradable;
use App\Model\ValueObject\Coordinates;
use Nette\Utils\Arrays;

class UpgradeBuildingCommand extends BaseCommand implements IUpgradeCommand
{

	/** @var Building */
	private $building;

	public function __construct(Coordinates $coordinates, Building $building)
	{
		parent::__construct($coordinates);
		$this->building = $building;
	}

	public static function getAction() : string
	{
		return static::ACTION_UPGRADE_BUILDING;
	}

	public function getUpgradable() : Upgradable
	{
		return $this->building;
	}

	public static function fromArray(array $data) : UpgradeBuildingCommand
	{
		return new UpgradeBuildingCommand(Coordinates::fromArray($data['coordinates']), Building::_($data['building']));
	}

	public function toArray() : array
	{
		$data = [
			'action' => $this->getAction(),
			'data' => [
				'building' => $this->building->getValue()
			]
		];
		return Arrays::mergeTree($data, parent::toArray());
	}

}