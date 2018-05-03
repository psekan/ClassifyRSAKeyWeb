<?php

namespace ClassifyRSA\Presenters;

use Nette;
use Nette\Application\Helpers;

class Presenter extends Nette\Application\UI\Presenter
{
    /**
     * Formats layout template file names.
     * @return array
     */
    public function formatLayoutTemplateFiles()
    {
        if (preg_match('#/|\\\\#', $this->layout)) {
            return [$this->layout];
        }
        list($module, $presenter) = Helpers::splitName($this->getName());
        $layout = $this->layout ? $this->layout : 'layout';
        $dir = TEMPLATES_DIR;
        $list = [
            "$dir/$presenter/@$layout.latte",
            "$dir/$presenter.@$layout.latte",
        ];
        do {
            $list[] = "$dir/@$layout.latte";
            $dir = dirname($dir);
        } while ($dir && $module && (list($module) = Helpers::splitName($module)));
        return $list;
    }


    /**
     * Formats view template file names.
     * @return array
     */
    public function formatTemplateFiles()
    {
        list(, $presenter) = Helpers::splitName($this->getName());
        $dir = TEMPLATES_DIR;
        return [
            "$dir/$presenter/$this->view.latte",
            "$dir/$presenter.$this->view.latte",
        ];
    }
}