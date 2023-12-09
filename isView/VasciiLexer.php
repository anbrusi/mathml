<?php

namespace isView;

class VasciiLexer extends VviewBase {
    
    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= 'VasciiLexer';
        $html .= '</div>';
        return $html;
    }
}