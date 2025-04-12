<?php

namespace isView;

class VgaussElim extends VviewBase {

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= \isLib\Lhtml::currentFile();
        $html .= \isLib\Lhtml::fieldset('Original expression', $_POST['input']);
        $html .= \isLib\Lhtml::fieldset('Start schema', \isLib\LmathDebug::drawGaussSchema($_POST['start_schema']));
        $html .= \isLib\Lhtml::fieldset('End schema', \isLib\LmathDebug::drawGaussSchema($_POST['end_schema']));
        $txt = '';
        if ($_POST['solution'][1] == 0) {
            // Numeric
            foreach ($_POST['solution'][0] as $name => $solution) {
                $txt .= $name."\t".'='."\t".$solution."\n";
            } 
        } else {
            foreach ($_POST['solution'][0] as $solution) {
                $txt .= $solution."\n";
            }
        }
        $html .= \isLib\Lhtml::fieldset('Solution', $txt);
        $html .= '</div>';
        return $html;
    }
}