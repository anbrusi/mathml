<?php

namespace isCtl;

class CpresentationParser extends CcontrollerBase {

    public function render():string {
        $html = '';
        $html .= '<p>CpresentationParser</p>';
        return $html;
    }
    
    public static function setInitialView():void {

    }

    public function initialView(): string {
        return 'VavailableFormulas';
    }
}