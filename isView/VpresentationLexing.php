<?php

namespace isView;

class VpresentationLexing extends VviewBase {

    private function mathmlSource():string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>Mathml source</legend>';
        $html .= '<div>';
        $html .= '<pre>';
        $html .= $_POST['source'];
        $html .= '</pre>';
        $html .= '</div>';
        $html .= '</fieldset>';
        return $html;
    }

    private function xmlCode():string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>XML code</legend>';
        $html .= '<div>';
        $html .= '<pre>';
        $html .= $_POST['xmlCode'];
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

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= $this->mathmlSource();
        $html .= '<div class="spacerdiv"></div>';
        $html .= $this->xmlCode();
        $html .= '<div class="spacerdiv"></div>';
        $html .= $this->tokens();
        $html .= '<div class="spacerdiv"></div>';
        $html .= $this->errors();
        $html .= '</div>';
        return $html;
    }
}