<?php

namespace isView;

class VasciiParser extends VviewBase {


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

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= $this->tokens();
        $html .= '<div class="spacerdiv"></div>';
        $html .= $this->errors();
        $html .= '<div class="spacerdiv"></div>';
        $html .= $this->parseTree();
        $html .= '</div>';
        return $html;
    }
}