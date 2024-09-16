<?php

namespace isView;

class VasciiLexer extends VviewBase {
    
    private function currentFile():string {
        $html = '';
        $html .= '<div>';
        $html .= 'current file: <strong>'.$_POST['currentFile'].'</strong>';
        $html .= '</div>';
        return $html;
    }

    private function asciiExpression():string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>ASCII math exprssion</legend>';
        $html .= '<div>';
        $html .= '<pre>';
        $html .= $_POST['expression'];
        $html .= '</pre>';
        $html .= '</div>';
        $html .= '</fieldset>';
        return $html;
    }

    private function tokens():string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>Tokens</legend>';
        $html .= '<div>';
        $html .= '<pre>';
        $html .= $_POST['tokens'];
        $html .= '</pre>';
        $html .= '</div>';
        $html .= '</fieldset>';
        return $html;
    }

    private function errors():string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>Errors</legend>';
        $html .= '<div>';
        $html .= '<pre>';
        $html .= $_POST['errors'];
        $html .= '</pre>';
        $html .= '</div>';
        $html .= '</fieldset>';
        return $html;
    }

    private function symbolTable():string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>Symbol table</legend>';
        $html .= '<div>';
        $html .= '<pre>';
        $html .= $_POST['symbolTable'];
        $html .= '</pre>';
        $html .= '</div>';
        $html .= '</fieldset>';
        return $html;
    }

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        if (isset($_POST['currentFile']) && !empty($_POST['currentFile'])) {
            // Display the current file
            $html .= $this->currentFile();
            $html .= '<div class="spacerdiv"></div>';
        }
        $html .= $this->asciiExpression();
        $html .= '<div class="spacerdiv"></div>';
        $html .= $this->tokens();
        $html .= '<div class="spacerdiv"></div>';
        $html .= $this->errors();
        $html .= '<div class="spacerdiv"></div>';
        $html .= $this->symbolTable();
        $html .= '</div>';
        return $html;
    }
}