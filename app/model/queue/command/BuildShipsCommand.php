<?php

namespace App\Model\Queue\Command;
 
use App\Enum\Building;
use App\Enum\Defense;
use App\Enum\Ships;
use Nette;
 
class BuildShipsCommand extends Nette\Object implements IBuildCommand
{

	/** @var Ships */
	private $ships;

	/** @var int */
	private $amount;

	public function __construct(Ships $ships, $amount)
	{
		$this->ships = $ships;
		$this->amount = $amount;
	}

	public static function getAction() : string
	{
		return static::ACTION_BUILD_SHIPS;
	}

	public function getShips() : Ships
	{
		return $this->ships;
	}

	public function getAmount() : int
	{
		return $this->amount;
	}

	public static function fromArray(array $data) : BuildShipsCommand
	{
		return new BuildShipsCommand(Ships::_($data['defense']), $data['amount']);
	}

	public function toArray() : array
	{
		return [
			'action' => $this->getAction(),
			'data' => [
				'ships' => $this->ships->getValue(),
				'amount' => $this->amount
			]
		];
	}

}