<?php

namespace isView;

class VasciiParser extends VviewBase {

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= \isLib\Lhtml::currentFile();
        $html .= \isLib\Lhtml::fieldset('ASCII math exprssion', $_POST['expression']);
        $html .= \isLib\Lhtml::fieldset('Parse tree', $_POST['parseTree']);
        $html .= \isLib\Lhtml::fieldset('Variables', $_POST['variables']);
        $html .= \isLib\Lhtml::fieldset('Traversation', $_POST['traversation']);
        $html .= \isLib\Lhtml::fieldset('Errors', $_POST['errors']);
        $html .= \isLib\Lhtml::fieldset('Trace', $_POST['trace']);
        $html .= '</div>';
        return $html;
    }
}