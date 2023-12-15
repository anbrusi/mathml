<?php

namespace isView;

class VpresentationLexer extends VviewBase {
    
    private function getMathml():array {
        $items = [];
        if (\isLib\LinstanceStore::available('currentFile')) {
            $currentFile = \isLib\LinstanceStore::get('currentFile');
            $ressource = fopen(\isLib\Lconfig::CF_FILES_DIR.$currentFile, 'r');
            $txt = fgets($ressource);
            $items = \isLib\Ltools::extractMathML($txt);
        }
        return $items;
    }

    private function mathmlTable(array $items):string {
        $html = '';
        $html .= '<table class="filetable">';
        for ($i = 0; $i < count($items); $i++) {
            $html .= '<tr>';
            // Radio choosing the current file
            $html .= '<td>';
            if (isset($_POST['available_expressions'])) {
                $currentExpression = $_POST['available_expressions'];
            } else {
                $currentExpression = 0;
            }
            if ($i == $currentExpression) {
                $checked = 'checked="checked"';
            } else {
                $checked = '';
            }
            $html .= '<input type="radio" name="available_expressions" value="'.$i.'" '.$checked.'/>';
            $html .= '</td>';
            $html .= '<td>'.$i.'</td>';
            $html .= '<td>'.$items[$i].'</td>';
            $html .= '<td>'.htmlspecialchars($items[$i]).'</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    public function render():string {
        $html = '';
        $items = $this->getMathml();
        $html .= '<div class="pagecontent">';
        if (count($items) > 0) {
            // Caption
            $html .= 'Available MathML expressions:';
            $html .= '<div class="spacerdiv"></div>';
            // Table of MathML exptressions
            $html .= $this->mathmlTable($items);
            $html .= '<div class="spacerdiv"></div>';
            // action bar
            $html .= \isLib\Lhtml::actionBar(['lexer' => 'Start the lexer']);
        }
        $html .= '</div>';
        return $html;
    }
}