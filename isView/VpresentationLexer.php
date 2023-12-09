<?php

namespace isView;

class VpresentationLexer extends VviewBase {
    
    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= 'VpresentationLexer';
        $html .= '</div>';
        return $html;
    }
}