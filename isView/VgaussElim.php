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
        foreach ($_POST['solution'] as $name => $solution) {
            $txt .= $name."\t".'='."\t".$solution."\n";
        } 
        $html .= \isLib\Lhtml::fieldset('Solution', $txt);
        $html .= '</div>';
        return $html;
    }
}