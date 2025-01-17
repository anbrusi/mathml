<?php

namespace isView;

class Vlatex extends VviewBase {

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= \isLib\Lhtml::currentFile();
        $html .= \isLib\Lhtml::fieldset('Original expression', $_POST['input']);
        $html .= \isLib\Lhtml::fieldset('Latex Code', $_POST['latex']);
        $html .= \isLib\Lhtml::fieldset('Latex representation', '\\[ '.$_POST['latex'].' \\]', false);
        $html .= '</div>';
        return $html;
    }
}