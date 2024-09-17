<?php

namespace isView;

class VshowLatex extends VviewBase {

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= \isLib\Lhtml::currentFile();
        $html .= \isLib\Lhtml::fieldset('Parse tree', $_POST['parseTree']);
        $html .= \isLib\Lhtml::fieldset('Errors', $_POST['errors']);
        $html .= \isLib\Lhtml::fieldset('Trace', $_POST['trace']);
        $html .= \isLib\Lhtml::fieldset('LateX code', $_POST['latex']);
        $html .= \isLib\Lhtml::fieldset('Latex representation', '\\[ '.$_POST['latex'].' \\]', false);
        $html .= '</div>';
        return $html;
    }
}