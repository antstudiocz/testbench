<?php

class Component extends \Nette\Application\UI\Control
{

	public function render()
	{
		$this->template->render(__DIR__ . '/Component.latte');
	}

	public function renderB()
	{
		$this->template->render(__DIR__ . '/ComponentB.latte');
	}

}
