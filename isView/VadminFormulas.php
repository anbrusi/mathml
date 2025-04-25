<?php

namespace isView;

class VadminFormulas extends VviewBase {

    private string $currentFile = '';

    function __construct(string $name) {
        parent::__construct($name);
        if (\isLib\LinstanceStore::available('currentFile')) {
            $this->currentFile = \isLib\LinstanceStore::get('currentFile');
        } else {
            $this->currentFile = 'No file has been set to current';
        }
    } 

    private function currentFile():string {
        $html = '';
        $html .= '<div>';
        $html .= 'current file: <strong>'.$this->currentFile.'</strong>';
        $html .= '</div>';
        return $html;
    }

    private function availableFiles():string {
        $html = '';
        $html .= '<table class="filetable">';
        // header
        $html .= '<tr>';
        $html .= '<th>!</th>';
        $html .= '<th>?</th>';
        $html .= '<th>x</th>';
        $html .= '<th>file</th>';
        $html .= '<th>type</th>';
        $html .= '<th>formula</th>';
        $html .= '</tr>';
        // files
        $files = \isLib\Lhtml::getFileArray(\isLib\Lconfig::CF_FILES_DIR);
        foreach ($files as $file) {
            $html .= '<tr>';
            // Radio choosing the current file
            $html .= '<td>';
            if ($file == $this->currentFile) {
                $checked = 'checked="checked"';
            } else {
                $checked = '';
            }

            $html .= '<input type="radio" name="available_files" value="'.$file.'" '.$checked.' onClick="
                const mainform = document.getElementById(\'mainform\');
                console.log(\'submit\', this.value);
                mainform.submit();
            "/>';

            $html .= '</td>';
            // Edit button
            $html .= '<td><button type="submit" name="editFile" class="linkbutton" value="'.$file.'">';
            $html .= '<img src="isImg/isPencilGrey.png" class="linkimage" />';
            $html .= '</button></td>';
            // Delete button
            $html .= '<td><button type="submit" name="delete" class="linkbutton" value="'.$file.'">';
            $html .= '<img src="isImg/isDestroyGrey.png" class="linkimage" />';
            $html .= '</button></td>';
            // File name
            $html .= '<td>'.$file.'</td>';
            // File type
            if (\isLib\Ltools::isMathMlFile(\isLib\Lconfig::CF_FILES_DIR.$file)) {
                $type = 'mathML';
            } else {
                $type = 'ascii';
            }
            $html .= '<td>'.$type.'</td>';
            // Formula
            $ressource = fopen(\isLib\Lconfig::CF_FILES_DIR.$file, 'r');
            $formula = fgets($ressource);
            $html .= '<td>'.$formula.'</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        // Display the current file
        $html .= $this->currentFile();
        $html .= '<div class="spacerdiv"></div>';
        // Display table of available formulas
        $html .= $this->availableFiles();
        $html .= '<div class="spacerdiv"></div>';
        // Display the action buttons
        $html .= \isLib\Lhtml::actionBar(['new' => 'New File']);
        $html .= '</div>';
        return $html;
    }
}