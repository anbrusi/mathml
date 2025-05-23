<?php

namespace isLib;

class Lconfig {

    const CF_FILES_DIR = 'files/';
    const CF_VARS_DIR = 'vars/';
    const CF_PROBLEMS_DIR = 'problems/';
    const CF_SOLUTIONS_DIR = 'solutions/';
    const CF_EQUATIONS_DIR = 'equations/';
    const NUMERIC_QUESTIONS_DIR = 'numericQuestions/';
    const NUMERIC_SOLUTIONS_DIR = 'numericSolutions/';
    const CLIENT_IMG_DIR = 'clientImages/';
    
    const CF_TRIG_UNIT = 'deg';

    /**
     * The radix used for NanoCAS numbers. Must be a power of 10.
     */
    const CF_NC_RADIX = 1000;

    public static function getDbName():string {
        return 'iststch_mathml';
    }
    
}