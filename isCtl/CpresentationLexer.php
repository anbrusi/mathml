<?php

namespace isCtl;

class CpresentationLexer extends CcontrollerBase {

    public function render():string {
        $html = '';
        $html .= '<p>CpresentationLexer</p>';
        return $html;
    }
    
    public function setInitialView():void {

    }

    public function initialView(): string {
        return 'VavailableFormulas';
    }
}