<?php

namespace Testbench;

use Nette\ComponentModel\IComponent;

trait TComponent
{

	private $__testbench_presenterMock;

	protected function attachToPresenter(IComponent $component, $name = NULL)
	{
		if ($name === NULL) {
			if (!$name = $component->getName()) {
				$name = $component->getReflection()->getShortName();
				if (preg_match('~class@anonymous.*~', $name)) {
					$name = md5($name);
				}
				if (preg_match('~Control@anonymous.*~', $name)) {
					$name = md5($name);
				}
			}
		}
		if (!$this->__testbench_presenterMock) {
			$container = \Testbench\ContainerFactory::create(FALSE);
			$this->__testbench_presenterMock = $container->getByType('Testbench\Mocks\PresenterMock');
			$container->callInjects($this->__testbench_presenterMock);
		}
		$this->__testbench_presenterMock->onStartup[] = function (Mocks\PresenterMock $presenter) use ($component, $name) {
			try {
				$componentToReplace = $presenter->getComponent($name, FALSE);
				if ($componentToReplace) {
					$presenter->removeComponent($componentToReplace);
				}
				$presenter->removeComponent($component);
			} catch (\Nette\InvalidArgumentException $exc) {
			}
			$presenter->addComponent($component, $name);
		};
		$this->__testbench_presenterMock->run(new \Nette\Application\Request('Foo'));
	}

	protected function checkRenderOutput(IComponent $control, $expected, $renderParameters = [], $renderMethod = 'render')
	{
		if (!$control->getParent()) {
			$this->attachToPresenter($control);
		}
		ob_start();
		$control->$renderMethod(...$renderParameters);
		if (is_file($expected)) {
			\Tester\Assert::matchFile($expected, ob_get_clean());
		} else {
			\Tester\Assert::match($expected, ob_get_clean());
		}
	}

}
