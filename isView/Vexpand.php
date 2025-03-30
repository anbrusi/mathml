<?php

namespace isView;

class Vexpand extends VviewBase {

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= \isLib\Lhtml::currentFile();
        $html .= \isLib\Lhtml::fieldset('Original expression', $_POST['input']);
        $html .= \isLib\Lhtml::fieldset('Original parse tree', $_POST['originalTree']);
        $html .= \isLib\Lhtml::fieldset('Transformed parse tree', $_POST['parseTree']);
        $html .= \isLib\Lhtml::fieldset('LateX', '\\['.$_POST['latex'].'\\]', false);
        $html .= \isLib\Lhtml::fieldset('Computed values', 'Original value: '.$_POST['originalValue']."\n".'Transformed value: '.$_POST['trfValue']);
        $trfSequence = '';
        foreach ($_POST['trfSequence'] as $subtree) {
            $trfSequence .= '\\['.$subtree.'\\]';
        }
        $html .= \isLib\Lhtml::fieldset('Trf sequence', $trfSequence, false);
        $html .= '</div>';
        return $html;
    }
}