<?php

namespace isCtl;

class Cformula implements Icontroller {
    
    public function render():string {
        $html = '';
        $html .= '<p>Cformula</p>';
        return $html;
    } 
}