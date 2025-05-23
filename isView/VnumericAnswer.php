<?php

namespace isView;

/**
 * The number of the question is passed by $_POST['questionid']
 * If the answer exists, it is stored in Tnumanswers
 * 
 * @package isView
 */
class VnumericAnswer extends VviewBase {

    public function render():string {
        $html = '';
        // Question
        $sql = 'SELECT question FROM Tnumquestions WHERE id=:id';
        $stmt = \isLib\Ldb::prepare($sql);
        if (!$stmt->execute(['id' => $_POST['questionid']])) {
            throw new \Exception('Cannot load numquestion '.$_POST['questionid']);
        }
        $questioncontent = $stmt->fetchColumn();
        $wrapped = \isLib\Ltools::wrapContent5($questioncontent);
        $html .= \isLib\Lhtml::fieldset('Question', $wrapped, false);
        // Answer
        $sql = 'SELECT answer FROM Tnumanswers WHERE user=:user AND questionid=:questionid';
        $stmt = \isLib\Ldb::prepare($sql);
        if ($stmt->execute(['user' => 2, 'questionid' => $_POST['questionid']])) {
            $answer = $stmt->fetchColumn();
        } else {
            $answer = '';
        }
        $html .= '<h3>Answer</h3>';
        $html .= \isLib\Leditor::editor(\isLib\Leditor::ED_TP_FORMULA_ONLY, 'answer', $answer);
        // buttons
        $html .= '<div class="spacerdiv"></div>';
        $html .= \isLib\Lhtml::actionBar(['esc' => 'Escape', 'store' => 'Store']);
        $html .= '</div>';
        $html .= \isLib\Lhtml::propagatePost('questionid');
        return $html;
    }
}