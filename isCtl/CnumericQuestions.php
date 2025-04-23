<?php

namespace isCtl;

use isLib\isMathException;
use PDOException;

/**
 * @abstract
 * Numeric questions are stored as two different files with the same name, which is the name of the task.
 * Questions are stored in \isLib\Lconfig::NUMERIC_QUESTIONS_DIR with the task as name and '.html' as extension
 * Solutions are stored in \isLib\Lconfig::NUMERIC_SOLUTIONS_DIR with the task as name and '.html' as extension
 * 
 * @package isCtl
 */
class CnumericQuestions extends CcontrollerBase {

    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VnumericQuestions':
                $this->VnumericQuestionsHandler();
                break;
            case 'Vconfirmation':
                $this->VconfirmationHandler();
                break;
            case 'VeditNumericQuestion':
                $this->VeditNumericQuestionHandler();
                break;
            case 'Verror':
                $this->VerrorHandler();
                break;
            case 'VnumericAnswer':
                $this->VnumericAnswerHandler();
                break;
            case 'VnumericCorrection':
                $this->VnumericCorrectionHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    public function VnumericQuestionsHandler():void {
        if (isset($_POST['new'])) {
            \isLib\LinstanceStore::setView('VeditNumericQuestion');
        } elseif (isset($_POST['edit'])) {
            \isLib\LinstanceStore::setView('VeditNumericQuestion');
        } elseif (isset($_POST['delete'])) {
            $sql = 'SELECT name FROM Tnumquestions WHERE id=:id';
            $stmt = \isLib\Ldb::prepare($sql);
            $stmt->execute(['id' => $_POST['delete']]);
            $name = $stmt->fetchColumn();
            $_POST['message'] = 'Do You really want to delete "'.$name.'"?'; // $_POST['delete'] is the task that should be deleted
            $_POST['backview'] = 'VnumericQuestions';
            $_POST['propagate'] = 'backview, delete';
            \isLib\LinstanceStore::setView('Vconfirmation');
        } elseif (isset($_POST['solve'])) {
            $_POST['questionid'] = $_POST['solve'];
            // Remove a possible old answe
            \isLib\LinstanceStore::remove('student_answer');
            \isLib\LinstanceStore::setView('VnumericAnswer');
        }
    }

    /**
     * Deletes the images referenced in numeric question with id $questionid
     * 
     * @param string $path
     * @return void 
     */
    private function deleteImages(int $questionid):void {
        $sql = 'SELECT question FROM Tnumquestions WHERE id=:id';
        $stmt = \isLib\Ldb::prepare($sql);
        $stmt->execute(['id' => $questionid]);
        $html = $stmt->fetchColumn();
        $imgPaths = \isLib\Ltools::getImgSrc($html);
        foreach ($imgPaths as $img) {
            $imgid = basename($img);
            $imgfile = \isLib\Lconfig::CLIENT_IMG_DIR.$imgid;
            unlink($imgfile);
        }
    }

    public function VconfirmationHandler():void {
        if (isset($_POST['yes'])) {
            // Remove the images in the question
            $this->deleteImages($_POST['delete']);
            // Remove the question
            $sql = 'DELETE FROM Tnumquestions WHERE id=:id';
            $stmt = \isLib\Ldb::prepare($sql);
            $stmt->execute(['id' => $_POST['delete']]);
        }
        \isLib\LinstanceStore::setView($_POST['backview']);
    }

    public function VerrorHandler():void {
        if (isset($_POST['back'])) {
            \isLib\LinstanceStore::setView($_POST['backview']);
        }
    }

    private function updateQuestion(int $id):void {
        $Mnumquestion = new \isMdl\Mnumquestion('Tnumquestions');
        $Mnumquestion->load($id);
        $Mnumquestion->setName($_POST['question_name']);
        $Mnumquestion->setQuestion($_POST['question']);
        $Mnumquestion->setSolution($_POST['solution']);
        $Mnumquestion->processSolution();
        $Mnumquestion->store();
    }

    /**
     * Returns the id in Tnumquestions of the question, if it was successfully stored
     * 
     * @param string $name te name field in Tnumquestions
     * @return int 
     * @throws PDOException 
     */
    private function storeQuestion(string $name):int {
        $Mnumquestion = new \isMdl\Mnumquestion('Tnumquestions');
        $Mnumquestion->setUser(1);
        $Mnumquestion->setName($name);
        $Mnumquestion->setQuestion($_POST['question']);
        $Mnumquestion->setSolution($_POST['solution']);
        $Mnumquestion->processSolution();
        return $Mnumquestion->store(); 
    }

    public function VeditNumericQuestionHandler():void {
        if (isset($_POST['esc'])) {
            \isLib\LinstanceStore::setView('VnumericQuestions');
        } elseif (isset($_POST['store'])) {
            if (isset($_POST['new_question'])) {
                // We have edited a new question, which we requested by clicking "New question", so check if the question name is admissible
                $stmt = \isLib\Ldb::prepare('SELECT id FROM Tnumquestions WHERE name=:name');
                $stmt->execute(['name' => $_POST['new_question']]);
                if ($stmt->fetch() !== false) {
                    // The requested name already exists. Ask if overwrite.
                    $_POST['errmess'] = 'The question already exists. Choose another name!';
                    $_POST['backview'] = 'VeditNumericQuestion';
                    // Prepare for saving the content
                    $_POST['previous_question'] = $_POST['question'];
                    $_POST['previous_solution'] = $_POST['solution'];
                    $_POST['propagate'] = 'backview, previous_question, previous_solution';
                    \isLib\LinstanceStore::setView('Verror');
                } else {              
                    $questionid = $this->storeQuestion($_POST['new_question']);
                }
            } elseif (isset($_POST['edit'])) {
                // We have edited a question by clicking on the "edit" symbol in the task list, so overwrite the existing task
                $this->updateQuestion($_POST['edit']);
            }
            \isLib\LinstanceStore::setView('VnumericQuestions');
        }
    }

    private function showMathContent(array $mathContent):string {
        $html = '';
        foreach ($mathContent as $formula) {
            $html .= $formula['ascii']."\n";
        }
        return $html;
    }

    private function showEquations(array $equations):string {
        $html = '';
        foreach ($equations as $equation) {
            $html .= $equation."\n";
        }
        return $html;
    }

    private function extractEquations(array $mathContent):array {
        $equations = [];
        foreach ($mathContent as $formula) {
            $parts = explode('=', $formula['ascii']);
            if (count($parts) == 2) {
                $equations[] = $parts[0].'-'.$parts[1];
            }
        }
        return $equations;
    }

    private function processAnswer(string $source):array {
        $Lfilter = new \isLib\Lfilter($source);
        $Lfilter->extractMathContent();
        $mathContent = $Lfilter->getMathContent();
        $equations = $this->extractEquations($mathContent);
        return $equations;
    }

    private function processTeacherAnswer(string $task):void {
        $ressource = fopen(\isLib\Lconfig::NUMERIC_SOLUTIONS_DIR.$_POST['task'].'.html', 'r');
        $source = fgets($ressource);
        try {
            $LmathExpression = new \isLib\LmathExpression($source);
            $equations = $LmathExpression->getEquations();
            $mathContent = $this->processAnswer($source);
            $_POST['teacherFormulas'] = $this->showEquations($mathContent);
        } catch (\Exception $ex) {
            $_POST['errmess'] = $ex->getMessage();
            \isLib\LinstanceStore::setView('Verror');
        }
    }

    /**
     * Sets POST according to the student answer
     * @return void 
     * @throws isMathException 
     */
    private function processStudentAnswer():void {
        $source = \isLib\LinstanceStore::get('student_answer');
        $mathContent = $this->processAnswer($source);
        $_POST['studentFormulas'] = $this->showEquations($mathContent);
    }

    public function VnumericAnswerHandler():void {
        if (isset($_POST['esc'])) {
            \isLib\LinstanceStore::setView('VnumericQuestions');
        } elseif (isset($_POST['correct'])) {
            \isLib\LinstanceStore::setView('VnumericCorrection');
            // $_POST['answer] is the student answer. Store it in a session variable
            \isLib\LinstanceStore::set('student_answer', $_POST['answer']);
            $this->processTeacherAnswer($_POST['task']);
            $this->processStudentAnswer();
        }
    }

    public function VnumericCorrectionHandler():void {
        if (isset($_POST['esc'])) {
            \isLib\LinstanceStore::setView('VnumericAnswer');
        } elseif (isset($_POST['repeat'])) {
            // delete the previous answer
            \isLib\LinstanceStore::remove('student_answer');
            \isLib\LinstanceStore::setView('VnumericAnswer');
        }
    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VnumericQuestions');
    }
}