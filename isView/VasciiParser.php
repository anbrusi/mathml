<?php

namespace isView;

class VasciiParser extends VviewBase {

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
    private function parseTree():string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>Parse tree</legend>';
        $html .= '<div>';
        $html .= '<pre>';
        $html .= $_POST['parseTree'];
        $html .= '</pre>';
        $html .= '</div>';
        $html .= '</fieldset>';
        return $html;
    }
    private function variables($variables):string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>Variables</legend>';
        $html .= '<div>';
        $html .= '<pre>';
        foreach ($variables as $variable) {
            $html .= $variable."\r\n";
        }
        $html .= '</pre>';
        $html .= '</div>';
        $html .= '</fieldset>';
        return $html;
    }

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= $this->asciiExpression();
        $html .= '<div class="spacerdiv"></div>';
        $html .= $this->tokens();
        $html .= '<div class="spacerdiv"></div>';
        $html .= $this->errors();
        $html .= '<div class="spacerdiv"></div>';
        $html .= $this->parseTree();
        if (isset($_POST['variables']) && !empty($_POST['variables'])) {
            $html .= '<div class="spacerdiv"></div>';
            $html .= $this->variables($_POST['variables']);
        }
        $html .= '</div>';
        return $html;
    }
}