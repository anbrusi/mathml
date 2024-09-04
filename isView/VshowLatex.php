<?php

namespace isView;

class VshowLatex extends VviewBase {

    private function fieldset(string $title, string $content, bool $usePre = true):string {
        $html = '';
        $html .= '<fieldset>';
        $html .= '<legend>'.$title.'</legend>';
        $html .= '<div>';
        if ($usePre) $html .= '<pre>';
        $html .= $content;
        if ($usePre) $html .= '</pre>';
        $html .= '</div>';
        $html .= '</fieldset>';
        return $html;
    }

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= $this->fieldset('Parse tree', $_POST['parseTree']);
        $html .= '<div class="spacerdiv"></div>';
        if (isset($_POST['errors'])) {
            $html .= $this->fieldset('Errors', $_POST['errors']);
        } elseif (isset($_POST['latex'])) {
            $html .= $this->fieldset('LateX code', $_POST['latex']);
            $html .= '<div class="spacerdiv"></div>';
            $html .= $this->fieldset('LateX representation', '\\[ '.$_POST['latex'].' \\]', false);
        }
        $html .= '</div>';
        return $html;
    }
}