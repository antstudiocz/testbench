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

	/** @var bool */
	private $silent = FALSE;

	protected function setUp()
	{
		parent::setUp();
		$this->getEM()->beginTransaction();
	}

	protected function tearDown()
	{
		parent::tearDown();
		if ($this->silent) {
			try {
				$this->getEM()->rollback();
			}

			catch (\Exception $e) {
				//silent only no data to rollback
				if ($e->getMessage() . '.' != ConnectionException::noActiveTransaction()->getMessage()) {
					throw $e;
				}
			}
		} else {
			$this->getEM()->rollback();
		}
	}

	public function setSilentTransaction()
	{
		$this->silent = TRUE;
	}

}
