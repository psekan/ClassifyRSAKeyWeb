<?php

namespace ClassifyRSA\Presenters;

use Nette;


class Error4xxPresenter extends Nette\Application\UI\Presenter
{
	public function startup()
	{
		parent::startup();
		if (!$this->getRequest()->isMethod(Nette\Application\Request::FORWARD)) {
			$this->error();
		}
	}


	public function renderDefault(Nette\Application\BadRequestException $exception)
	{
		$file = TEMPLATES_DIR . "/Error/{$exception->getCode()}.latte";
		$this->template->setFile(is_file($file) ? $file : TEMPLATES_DIR . '/Error/4xx.latte');
	}
}
