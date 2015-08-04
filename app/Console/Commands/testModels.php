<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Propel\Runtime\Propel;
use \Config;

use App\Models\Empire;
use App\Models\EmpireQuery;
use App\Models\Unit;
use App\Models\TerritoryTemplate;
use App\Models\TerritoryTemplateQuery;
use App\Models\Map\TerritoryTemplateTableMap;
use App\Models\Match;
use App\Models\State;
use App\Models\StateQuery;
use App\Models\Game;
use App\Models\GameQuery;
use App\Models\Map\GameTableMap;
use App\Models\Order;
use App\Models\OrderQuery;
use App\Models\UnitSupplyResolver;
use App\Models\RetreatResolver;

use App\Models\Move;
use App\Models\Support;
use App\Models\Retreat;

use Propel\Runtime\ActiveQuery\Criteria;

class testModels extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'test:run';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Runs the tests from the old main.php.  Used for testing for now.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		// Clear the DB first

		$con = Propel::getWriteConnection(GameTableMap::DATABASE_NAME);
		//$sql = "DELETE FROM game WHERE name<>'test'";
		$queries = array('DELETE FROM game');
		$tables = array('game_match', 'match_state', 'turn', 'empire_order', 'empire', 'territory_template');
		foreach ($tables as $t) $queries[] = "ALTER TABLE $t AUTO_INCREMENT = 1";
		foreach ($queries as $q) {
			$stmt = $con->prepare($q);
			$stmt->execute();
		}

		//$config->system->db->useDebug(true);

		// Create or use
		$game = null;
		$games = GameQuery::create()->filterByName('test%', Criteria::LIKE);
		$game_base_name = 'test';
		$game_name = $game_base_name . '_' . $games->count();
		$p_objs = json_decode(file_get_contents(Config::get('paths.games') . "/$game_base_name/empires.json"), false);
		$t_objs = json_decode(file_get_contents(Config::get('paths.games') . "/$game_base_name/territories.json"), false);


		$game = Game::create($game_name, 1861, 'spring');
		$game->loadEmpires($p_objs);
		$game->loadTerritories($t_objs);

		$game->save();

		// $texas   = Territory::findTerritoryByName($territories, 'Texas');
		// $sequoia = Territory::findTerritoryByName($territories, 'Sequoia');
		// $ohio    = Territory::findTerritoryByName($territories, 'Ohio');

		// print "Texas-Sequoia? " . ($texas->isNeighbour($sequoia) ? 'PASS':'FAIL') . "\n";
		// print "Sequoia-Texas? " . ($sequoia->isNeighbour($texas) ? 'PASS':'FAIL') . "\n";
		// print "Texas-Ohio? "    . ($texas->isNeighbour($ohio) ? 'FAIL':'PASS') . "\n";

		// Empires
		$red   = EmpireQuery::create()->filterByGame($game)->filterByAbbr('RED')->findOne();
		$blue  = EmpireQuery::create()->filterByGame($game)->filterByAbbr('BLU')->findOne();
		$green = EmpireQuery::create()->filterByGame($game)->filterByAbbr('GRN')->findOne();


		$match = Match::create($game, "Matt Test");
		$turn = $match->getCurrentTurn();
		print "\n" . $match ."\n";

		// Territories
		// Crate the $t_<territory> magic variables.
		$t_names=array('A', 'B', 'C', 'D', 'E');
		foreach ($t_names as $n) {
			$c = new Criteria; $c->add(TerritoryTemplateTableMap::COL_NAME, $n);
			$tt = $game->getGameTerritories($c);
			$varname  = "t_".strtolower($n);
			$$varname = StateQuery::create()->filterByTerritory($tt)->findOne();
		}

		print "$t_a neighbours:\n";
		$neighbours = $t_a->getTerritory()->getNeighbours();
		foreach ($neighbours as $n) {
			print "$n\n";
		}


		print "\n" . Unit::printUnitTable($match->getCurrentTurn());

		$case = 3;
		switch ($case) {
			case 1;
				// Test move conflict
				$turn->addOrder(Move::createNS($red,    $t_a,  $t_b));
				$turn->addOrder(Move::createNS($red,    $t_a,  $t_c));
				$turn->addOrder(Move::createNS($blue,   $t_a,  $t_b));
				$turn->addOrder(Move::createNS($green,  $t_e,  $t_d));
				$turn->save();
				break;
			case 2;
				// Test support
				$turn->addOrder(Move::createNS($red,       $t_a,  $t_b));
				$turn->addOrder(Move::createNS($blue,      $t_b,  $t_c));
				$turn->addOrder(Support::createNS($green,  $t_a,  $t_e, $t_b));
				$turn->save();
				break;
			case 3:
		//$config->system->db->useDebug(true);
				try {
					$turn->addOrder(Order::interpretText('MOVE "A" "B"', $match, $red));
				} catch (\DiplomacyOrm\InvalidOrderException $e) {
					print "[". Config::get('ansi.red') ."Error". Config::get('ansi.clear') ."]: Red cannot MOVE A-B: ". $e->getMessage() . "\n";
					exit;
				} catch (DiplomacyOrm\TurnClosedToOrdersException $e) {
					print "[". Config::get('ansi.red') ."Error". Config::get('ansi.clear') ."]: Some how the turn state is empty again: ". $e->getMessage() . "\n";
					exit;
				}
				$turn->addOrder(Order::interpretText('SUPPORT "E" "A" "B"', $match, $green));
				$turn->save();
				break;
			case 4:
				// Test the case with multiple contendors stalemat. standoff.svg
				$turn->addOrder(Order::interpretText('MOVE "A" "B"', $match, $red));
				$turn->addOrder(Order::interpretText('SUPPORT "A" "F" "B"', $match, $red));
				$turn->addOrder(Order::interpretText('MOVE "I" "B"', $match, $green));
				$turn->addOrder(Order::interpretText('SUPPORT "E" "H" "B"', $match, $green));
				$turn->save();

				// RED and GREEN should loose in statemates, B should belong to BLUE
				break;
			case 5:
				$turn->addOrder(Order::interpretText('MOVE "E" "C"', $match, $green));
				break;
			case 6:
				// No orders
				$result = $match->getCurrentTurn()->processOrders(); print $match ."\n";
				$result = $match->getCurrentTurn()->processOrders();
				$result = $match->getCurrentTurn()->processOrders();
				$result = $match->getCurrentTurn()->processOrders();
				break;

		}

		$match->getCurrentTurn()->printOrders();
		$result = $match->getCurrentTurn()->processOrders();
		//print "\n" . Unit::printUnitTable($match->getCurrentTurn());
		//$match->getCurrentTurn()->printOrders();
		//print_r($result->__toArray());
		//print json_encode($result->__toArray());

		if ($result->retreatsRequired()) {
			//print $result;

			$retreat = Retreat::createNS($blue, $t_b, $t_c);
			print "Adding retreat order: $retreat\n";
			$match->getCurrentTurn()->addOrder($retreat);

			print "----------------------------------------\n";
			print "After adding retreat order..\n";
			$match->getCurrentTurn()->printOrders();
			$result = $match->getCurrentTurn()->processOrders();
			print $result;
		}


		// Show which orders have failed, etc

		print "\n" . $match ."\n";

	}
}

// vim: ts=3 sw=3 noet :
