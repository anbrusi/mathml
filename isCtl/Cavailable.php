<?php

namespace isCtl;

class Cavailable implements Icontroller {
    
    public function render():string {
        $html = '';
        $html .= '<p>Cavailable</p>';
        return $html;
    } 
}