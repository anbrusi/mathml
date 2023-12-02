<?php

namespace isCtl;

class CpresentationParser implements Icontroller {

    public function render():string {
        $html = '';
        $html .= '<p>CpresentationParser</p>';
        return $html;
    }
}