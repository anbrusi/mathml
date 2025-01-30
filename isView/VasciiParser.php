<?php

namespace isView;

class VasciiParser extends VviewBase {

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= \isLib\Lhtml::currentFile();
        if (isset($_POST['originalExpression'])) {
            $html .= \isLib\Lhtml::fieldset('Original exprssion', $_POST['originalExpression']);
        }
        $html .= \isLib\Lhtml::fieldset('ASCII math exprssion', $_POST['asciiExpression']);
        $html .= \isLib\Lhtml::fieldset('Parse tree', $_POST['parseTree']);
        if (!empty($_POST['variables'])) {
            $html .= \isLib\Lhtml::fieldset('Variable names', implode(', ', $_POST['variableNames']));
        }
        $html .= \isLib\Lhtml::fieldset('Traversation', $_POST['traversation']);
        $html .= '</div>';
        return $html;
    }
}