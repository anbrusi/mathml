<?php

namespace isView;

/**
 * The number of the question is passed in $_POST['questionid'].
 * The student answer is stored as session variable 'student_answer'
 * The reason is, that it cannot be propagated by a hidden POST variables becuse of mathml
 * 
 * @package isView
 */
class VnumericCorrection extends VviewBase {

    public function render():string {
        $answer = \isLib\LinstanceStore::get('student_answer');
        $html = '';
        $Mnumquestion = new \isMdl\Mnumquestion('Tnumquestions');
        if ($Mnumquestion->load($_POST['questionid'])) {
            $questioncontent = $Mnumquestion->getQuestion();
        } else {
            throw new \Exception('Cannot load question '.$_POST['questionid']);
        }
        // Display the question
        $html .= '<h3>Question</h3>';
        $html .= \isLib\Leditor::editor(\isLib\Leditor::ED_TP_FORMULA_AND_IMG, 'question', $questioncontent);
        $html .= '<h3>Student answer</h3>';
        $studentAnswer = \isLib\LinstanceStore::get('student_answer');
        $html .= \isLib\Leditor::editor(\isLib\Leditor::ED_TP_FORMULA_ONLY, 'solution', $studentAnswer);
        $html .= '<div class ="spacerdiv"></div>';
        // propagate the question id
        $html .= \isLib\Lhtml::propagatePost('questionid');
        // buttons
        $html .= '<div class="spacerdiv"></div>';
        $html .= \isLib\Lhtml::actionBar(['esc' => 'Escape', 'repeat' => 'New student answer']);
        $html .= '</div>';
        $html .= \isLib\Lhtml::propagatePost('task');
        return $html;
    }
}