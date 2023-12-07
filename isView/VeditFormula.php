<?php

namespace isView;

class VeditFormula extends VviewBase {

    function __construct(string $name) {
        parent::__construct($name);
    } 

    public function render():string {
        $html = '';
        $html .= '<p>VeditFormula</p>';
        return $html;
    }
}