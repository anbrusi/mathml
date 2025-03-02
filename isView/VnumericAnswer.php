<?php

namespace isView;

/**
 * The name of the task is passed by $_POST['task']
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
        $ressource = fopen(\isLib\Lconfig::NUMERIC_QUESTIONS_DIR.$_POST['task'].'.html', 'r');
        $questioncontent = fgets($ressource);
        $html .= \isLib\Lhtml::fieldset('Question', $questioncontent, false);
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