<?php

namespace isCtl;

class CshowMathML implements Icontroller {

    public function render():string {
        $html = '';
        $html .= '<p>CshowMathML</p>';
        return $html;
    }
}