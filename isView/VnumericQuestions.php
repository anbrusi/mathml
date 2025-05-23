<?php

namespace isView;

class VnumericQuestions extends VviewBase {

    private function availableQuestions():string {
        $html = '';
        try {
            // files
            /*
            $files = \isLib\Lhtml::getFileArray(\isLib\Lconfig::NUMERIC_QUESTIONS_DIR);
            */
            $html .= '<table class="filetable">';
            // header
            $html .= '<tr>';
            $html .= '<th>!</th>';
            $html .= '<th>?</th>';
            $html .= '<th>x</th>';
            $html .= '<th>&</th>';
            $html .= '<th>file</th>';
            $html .= '<th>question</th>';
            $html .= '</tr>';
            $stmt = \isLib\Ldb::prepare('SELECT id, name, question FROM Tnumquestions WHERE user=:user');
            $stmt->execute(['user' => 1]);
            foreach ($stmt as $row) {
                $html .= '<tr>';
                // Solve button
                $html .= '<td><button type="submit" name="solve" class="linkbutton" value="'.$row['id'].'">';
                $html .= '<img src="isImg/isActionGrey.png" class="linkimage" title="Store student answer"/>';
                $html .= '</button></td>';
                // Edit button
                $html .= '<td><button type="submit" name="edit" class="linkbutton" value="'.$row['id'].'">';
                $html .= '<img src="isImg/isPencilGrey.png" class="linkimage" title="Edit"/>';
                $html .= '</button></td>';
                // Delete button
                $html .= '<td><button type="submit" name="delete" class="linkbutton" value="'.$row['id'].'">';
                $html .= '<img src="isImg/isDestroyGrey.png" class="linkimage" title="Delete"/>';
                $html .= '</button></td>';
                // Correct button
                $html .= '<td><button type="submit" name="correct" class="linkbutton" value="'.$row['id'].'">';
                $html .= '<img src="isImg/isComment.png" class="linkimage" title="Correct student answer"/>';
                $html .= '</button></td>';
                // Question name
                $html .= '<td>'.$row['name'].'</td>';
                // question
                $wrapped = \isLib\Ltools::wrapContent5($row['question']);
                $html .= '<td>'.$wrapped.'</td>';
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