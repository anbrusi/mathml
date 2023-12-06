<?php

namespace isCtl;

class CasciimathParser extends CcontrollerBase {

    public function render():string {
        $html = '';
        $html .= '<p>CasciimathParser</p>';
        return $html;
    }
    
    public static function setInitialView():void {

    }

    public function initialView(): string {
        return 'VavailableFormulas';
    }
}