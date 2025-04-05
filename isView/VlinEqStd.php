<?php

namespace isView;

class VlinEqStd extends VviewBase {

    private function decode(array $linEqStd):string {
        $txt = '';
        foreach($linEqStd as $key => $value) {
            $txt .= $key."\t".'-->'."\t".$value."\n";
        }
        return $txt;
    }

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= \isLib\Lhtml::currentFile();
        $html .= \isLib\Lhtml::fieldset('Original expression', $_POST['input']);
        $html .= \isLib\Lhtml::fieldset('Original parse tree', $_POST['originalTree']);
        $html .= \isLib\Lhtml::fieldset('Linear equation standard', $this->decode($_POST['linEqStd']));
        $trfSequence = '';
        foreach ($_POST['trfSequence'] as $subtree) {
            $trfSequence .= '\\['.$subtree.'\\]';
        }
        $html .= \isLib\Lhtml::fieldset('Trf sequence', $trfSequence, false);
        $html .= '</div>';
        return $html;
    }
}