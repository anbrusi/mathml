<?php

namespace isView;

class VpresentationParser extends VviewBase {
    
    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= 'VpresentationParser';
        $html .= '</div>';
        return $html;
    }
}