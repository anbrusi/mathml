<?php

namespace isView;

class VasciiLexer extends VviewBase {
    
    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= \isLib\Lhtml::currentFile();
        $html .= \isLib\Lhtml::fieldset('ASCII math exprssion', $_POST['expression']);
        $html .= \isLib\Lhtml::fieldset('Tokens', $_POST['tokens']);
        $html .= \isLib\Lhtml::fieldset('Symbol table', $_POST['symbolTable']);
        $html .= \isLib\Lhtml::fieldset('Evaluation', $_POST['evaluation']);
        $html .= \isLib\Lhtml::fieldset('Errors', $_POST['errors']);
        $html .= \isLib\Lhtml::fieldset('Trace', $_POST['trace']);
        $html .= '</div>';
        return $html;
    }
}