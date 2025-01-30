<?php

namespace isView;

class VasciiLexer extends VviewBase {
    
    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= \isLib\Lhtml::currentFile();
        if (isset($_POST['mathml'])) {          
            $html .= \isLib\Lhtml::fieldset('MathML Expression', $_POST['mathml']);  
        }
        $html .= \isLib\Lhtml::fieldset('ASCII Expression', $_POST['expression']);
        $html .= \isLib\Lhtml::fieldset('Tokens', $_POST['tokens']);
        $html .= \isLib\Lhtml::fieldset('Symbol table', $_POST['symbolTable']);
        $html .= '</div>';
        return $html;
    }
}