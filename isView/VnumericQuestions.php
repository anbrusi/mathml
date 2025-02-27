<?php

namespace isView;

class VnumericQuestions extends VviewBase {

    private function availableQuestions():string {
        $html = '';
        try {
            // files
            $files = \isLib\Lhtml::getFileArray(\isLib\Lconfig::NUMERIC_QUESTIONS_DIR);
            $html .= '<table class="filetable">';
            // header
            $html .= '<tr>';
            $html .= '<th>?</th>';
            $html .= '<th>x</th>';
            $html .= '<th>file</th>';
            $html .= '<th>question</th>';
            $html .= '</tr>';
            foreach ($files as $file) {
                $task = substr($file, 0, strrpos($file, '.'));
                $html .= '<tr>';
                // Edit button
                $html .= '<td><button type="submit" name="edit" class="linkbutton" value="'.$task.'">';
                $html .= '<img src="isImg/isPencilGrey.png" class="linkimage" />';
                $html .= '</button></td>';
                // Delete button
                $html .= '<td><button type="submit" name="delete" class="linkbutton" value="'.$task.'">';
                $html .= '<img src="isImg/isDestroyGrey.png" class="linkimage" />';
                $html .= '</button></td>';
                // Task name
                $html .= '<td>'.$task.'</td>';
                // question
                $ressource = fopen(\isLib\Lconfig::NUMERIC_QUESTIONS_DIR.$file, 'r');
                $question = fgets($ressource);
                $html .= '<td>'.$question.'</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        } catch (\Exception $ex) {
            $html .= '<h2>No questions available</h2>';
        }
        return $html;
    }

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        // Display table of available tasks
        $html .= $this->availableQuestions();
        $html .= '<div class="spacerdiv"></div>';
        // Display the action buttons
        $html .= \isLib\Lhtml::actionBar(['new' => 'New question']);
        $html .= '</div>';
        return $html;
    }
}