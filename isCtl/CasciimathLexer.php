<?php

namespace isCtl;

class CasciimathLexer extends CcontrollerBase {

    public function render():string {
        $html = '';
        $html .= '<p>CasciimathLexer</p>';
        return $html;
    }
    
    public static function setInitialView():void {

    }

    public function initialView(): string {
        return 'VavailableFormulas';
    }
}