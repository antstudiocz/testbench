<?php

declare(strict_types=1);

namespace Testbench;

use Doctrine\DBAL\ConnectionException;
use Tester\TestCase;

class TransactionalTestCase extends TestCase
{
	use TDoctrine {
		getEntityManager as private getEM;
	}

	protected function setUp()
	{
		parent::setUp();
		$this->getEM()->beginTransaction();
	}

	protected function tearDown()
	{
		parent::tearDown();
		try {
			$this->getEM()->rollback();
		}
		catch (\Exception $e) {
			//silent only no data to rollback
			if ($e->getMessage() . '.' != ConnectionException::noActiveTransaction()->getMessage()) {
				throw $e;
			}
		}
	}

}
