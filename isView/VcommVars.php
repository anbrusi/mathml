<?php

namespace isView;

class VcommVars extends VviewBase {

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= \isLib\Lhtml::currentFile();
        $html .= \isLib\Lhtml::fieldset('Original expression', $_POST['input']);
        $html .= \isLib\Lhtml::fieldset('Original parse tree', $_POST['originalTree']);
        $html .= \isLib\Lhtml::fieldset('Transformed parse tree', $_POST['parseTree']);
        $html .= \isLib\Lhtml::fieldset('LateX', '\\['.$_POST['latex'].'\\]', false);
        $html .= '</div>';
        return $html;
    }
}