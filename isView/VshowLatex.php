<?php

namespace isView;

class VshowLatex extends VviewBase {

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
        $html .= $this->parseTree();
        $html .= '</div>';
        return $html;
    }
}