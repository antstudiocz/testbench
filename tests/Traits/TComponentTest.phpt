<?php

declare(strict_types=1);

namespace Tests\Traits;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

//require getenv('BOOTSTRAP');

/**
 * @testCase
 */
class TComponentTest extends \Tester\TestCase
{

	use \Testbench\TComponent;

	public function testAttachToPresenter()
	{
		$control = new \Component;
		Assert::exception(function () use ($control) {
			$control->lookup(\Nette\Application\IPresenter::class);
		}, \Nette\InvalidStateException::class, "Component of type 'Component' is not attached to '" . \Nette\Application\IPresenter::class . "'.");
		$this->attachToPresenter($control);
		Assert::type(\Testbench\CustomPresenterMock::class, $control->lookup(\Nette\Application\IPresenter::class));
	}

	public function testRender()
	{
		$control = new \Component;
		$this->checkRenderOutput($control, '<strong>OK</strong>');
		$this->checkRenderOutput($control, __DIR__ . '/Component.expected');

		$this->checkRenderOutput($control, '<strong>OKB</strong>', [], 'renderB');
		$this->checkRenderOutput($control, __DIR__ . '/ComponentB.expected', [], 'renderB');

		$control = new \Component;
		$this->checkRenderOutput($control, '<strong>OK</strong>');
		$this->checkRenderOutput($control, __DIR__ . '/Component.expected');

		$this->checkRenderOutput($control, '<strong>OKB</strong>', [], 'renderB');
		$this->checkRenderOutput($control, __DIR__ . '/ComponentB.expected', [], 'renderB');
		$control = new \Component;
		$this->checkRenderOutput($control, '<strong>OK</strong>');
		$this->checkRenderOutput($control, __DIR__ . '/Component.expected');

		$this->checkRenderOutput($control, '<strong>OKB</strong>', [], 'renderB');
		$this->checkRenderOutput($control, __DIR__ . '/ComponentB.expected', [], 'renderB');
		$control = new \Component;
		$this->checkRenderOutput($control, '<strong>OK</strong>');
		$this->checkRenderOutput($control, __DIR__ . '/Component.expected');

		$this->checkRenderOutput($control, '<strong>OKB</strong>', [], 'renderB');
		$this->checkRenderOutput($control, __DIR__ . '/ComponentB.expected', [], 'renderB');

		$control = new \ComponentWithParameters();
		$this->checkRenderOutput($control, '1', [1]);
		$this->checkRenderOutput($control, '12', [1, 2]);

		$this->checkRenderOutput($control, '1B', [1], 'renderB');
		$this->checkRenderOutput($control, '12B', [1, 2], 'renderB');
	}

	public function testRenderWithExplicitAttach()
	{
		$this->attachToPresenter($control = new \Component);
		$this->checkRenderOutput($control, '<strong>OK</strong>');
		$this->checkRenderOutput($control, __DIR__ . '/Component.expected');

		$this->checkRenderOutput($control, '<strong>OKB</strong>', [], 'renderB');
		$this->checkRenderOutput($control, __DIR__ . '/ComponentB.expected', [], 'renderB');
	}

	public function testMultipleAttaches()
	{
		$control = new \Component;
		$this->attachToPresenter($control);
		Assert::type(\Testbench\CustomPresenterMock::class, $control->lookup(\Nette\Application\IPresenter::class));
		$this->attachToPresenter($control);
		Assert::type(\Testbench\CustomPresenterMock::class, $control->lookup(\Nette\Application\IPresenter::class));
		\Tester\Environment::$checkAssertions = FALSE;
	}

	public function testMultipleAttachesDifferentComponents()
	{
		$this->attachToPresenter($control = new \Component, 'name_1');
		Assert::type(\Testbench\CustomPresenterMock::class, $control->lookup(\Nette\Application\IPresenter::class));
		$this->attachToPresenter($control = new \Component, 'name_2');
		Assert::type(\Testbench\CustomPresenterMock::class, $control->lookup(\Nette\Application\IPresenter::class));
		\Tester\Environment::$checkAssertions = FALSE;
	}

}

(new TComponentTest)->run();
