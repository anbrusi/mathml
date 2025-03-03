<?php

namespace isView;

class Vparse extends VviewBase {

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= \isLib\Lhtml::currentFile();
        $html .= \isLib\Lhtml::fieldset('Original expression', $_POST['input']);
        $html .= \isLib\Lhtml::fieldset('Parse tree', $_POST['parseTree']);
        if (!empty($_POST['variableNames'])) {
            $html .= \isLib\Lhtml::fieldset('Variable names', implode(', ', $_POST['variableNames']));
        }
        $html .= '</div>';
        return $html;
    }
}