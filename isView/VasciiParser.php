<?php

namespace isView;

class VasciiParser extends VviewBase {


    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= 'VasciiParser';
        $html .= '</div>';
        return $html;
    }
}