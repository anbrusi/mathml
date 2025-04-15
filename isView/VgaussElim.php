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
            foreach ($_POST['solution'][0] as $name => $solution) {
                $sum = '';
                $nrSummands = count($solution);
                if ($nrSummands > 0) {
                    $summand = $solution[0];
                    if ($summand[1] == '1') {
                        $sum .= strval($summand[0]);
                    } else {
                        $sum .= strval($summand[0]).$summand[1];
                    }
                    for ($i = 1; $i <$nrSummands; $i++) {
                        $summand = $solution[$i];
                        if ($summand[0] >= 0) {
                            $sum .= '+';
                        }
                        if ($summand[1] == '1') {
                            $sum .= strval($summand[0]);
                        } else {
                            $sum .= strval($summand[0]).$summand[1];
                        }
                    }
                }
                $txt .= $name."\t".'='."\t".$sum."\n";
            }
        }
        $html .= \isLib\Lhtml::fieldset('Solution', $txt);
        $html .= '</div>';
        return $html;
    }
}