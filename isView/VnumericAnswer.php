<?php

namespace isView;

/**
 * The name of the task is passed by $_POST['questionid']
 * If the answer if it exists is stored in the session variable 'student_answe'
 * 
 * @package isView
 */
class VnumericAnswer extends VviewBase {

    public function render():string {
        if (\isLib\LinstanceStore::available('student_answer')) {
            $answer = \isLib\LinstanceStore::get('student_answer');
        } else {
            $answer = '';
        }
        $html = '';
        // Question
        $sql = 'SELECT question FROM Tnumquestions WHERE id=:id';
        $stmt = \isLib\Ldb::prepare($sql);
        $stmt->execute(['id' => $_POST['questionid']]);
        $questioncontent = $stmt->fetchColumn();
        $wrapped = \isLib\Ltools::wrapContent5($questioncontent);
        $html .= \isLib\Lhtml::fieldset('Question', $wrapped, false);
        // Answer
        $html .= '<h3>Answer</h3>';
        $html .= \isLib\Leditor::editor(\isLib\Leditor::ED_TP_FORMULA_ONLY, 'answer', $answer);
        // buttons
        $html .= '<div class="spacerdiv"></div>';
        $html .= \isLib\Lhtml::actionBar(['esc' => 'Escape', 'correct' => 'Correct']);
        $html .= '</div>';
        $html .= \isLib\Lhtml::propagatePost('task');
        return $html;
    }
}