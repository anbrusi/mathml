<?php

namespace isView;

class VeditFile extends VviewBase {

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        if (!isset($_POST['editFile']) || $_POST['editFile'] === '') {
            // A new file is requested

            // The new file name
            $html .= '<div>Enter a name for the new file: <input type="text" name="new_file" autofocus="autofocus"/></div>'; // editor
            $html .= '<div class ="spacerdiv"></div>';
            // editor
            if (isset($_POST['previous_content'])) {
                $content = $_POST['previous_content'];
            } else {
                $content = '';
            }
            $html .= \isLib\Leditor::editor(\isLib\Leditor::ED_TP_FORMULA_ONLY, 'n_ckeditor', $content);
        } else {
            // The current file is edited

            // editor
            $controller = \isLib\LinstanceStore::getController();
            if ($controller == 'Cformula') {
                $directory = \isLib\Lconfig::CF_FILES_DIR;
            } elseif ($controller == 'CadminEquations') {
                $directory = \isLib\Lconfig::CF_EQUATIONS_DIR;
            }
            $ressource = fopen($directory.$_POST['editFile'], 'r');
            $content = fgets($ressource);
            $html .= \isLib\Leditor::editor(\isLib\Leditor::ED_TP_FORMULA_ONLY, 'n_ckeditor', $content);
        }
        // propagate the file name
        $html .= \isLib\Lhtml::propagatePost('file');
        // buttons
        $html .= '<div class="spacerdiv"></div>';
        $html .= \isLib\Lhtml::actionBar(['esc' => 'Escape', 'store' => 'Store']);
        $html .= '</div>';
        return $html;
    }
}