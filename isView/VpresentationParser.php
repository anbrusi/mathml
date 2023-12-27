<?php

namespace isView;

class VpresentationParser extends VviewBase {
    
    private function mathmlSource():string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>Mathml source</legend>';
        $html .= '<div>';
        $html .= $_POST['source'];
        $html .= '</div>';
        $html .= '<div class="spacerdiv"></div>';
        $html .= '<div>';
        $html .= htmlentities($_POST['source']);
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
        $html .= $this->errors();
        $html .= '<div class="spacerdiv"></div>';
        $html .= '</div>';
        return $html;
    }
}