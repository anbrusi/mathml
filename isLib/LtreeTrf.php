<?php

namespace isLib;

use Exception;

class LtreeTrf {

    private array $inputTree = [];

    /**
     * Part of $this->normalize, used for debugging
     * 
     * @var array
     */
    private array $summands = [];
    /**
     * Part of $this->dst used for debuggin
     * @var array
     */
    private array $trfSequence = [];

    /**
     * Debug function
     * 
     * @return array 
     */
    public function getSummands():array {
        return $this->summands;
    }

    /**
     * Debug function
     * 
     * @return array 
     */
    public function getTrfSequence():array {
        return $this->trfSequence;
    }

    function __construct(array $inputTree) {
        $this->inputTree = $inputTree;        
    }

    private function isMultNode(array $node):bool {
        if ($node['tk'] == '*' || $node['tk'] == '?') {
            return true;
        }
        return false;
    }

    private function isAddNode(array $node):bool {
        if ($node['tk'] == '+' || $node['tk'] == '-' && !isset($node['u'])) {
            return true;
        }
        return false;
    }

    private function isTerminal(array $node):bool {
        if ($node['type'] == 'number' || $node['type'] == 'mathconst' || $node['type'] == 'variable') {
            return true;
        }
        return false;
    }

    private function isUnaryMinus(array $node):bool {
        return $node['tk'] == '-' && isset($node['u']);
    }

    private function isNumeric($node):bool {
        return ($node['type'] == 'number' || $node['type'] == 'mathconst');
    }

    private function copyNodeHeader(array $node):array {
        // Mandatory values first
        $n = ['tk' => $node['tk'], 'type' => $node['type'], 'restype' => $node['restype']];
        if (isset($node['value'])) {
            $n['value'] = $node['value'];
        }
        return $n;
    }

    /**
     * Converts a PHP float to a string accepted by LasciiLexer
     * The task is not trivial, if we want to be very general and take care of exponential parts
     * 
     * @param float $float 
     * @return string 
     */
    private function floatToStr(float $float):string {
        return "{$float}";
    }

    /**
     * Returns a float from a numeric value accepted by the parser
     * 
     * @param string $str 
     * @return float 
     */
    private function strToFloat(string $str):float {
        return floatval($str);
    }


    /**
     * $summands is an array of arrays, whose elements have a descriptor string as value for key 1
     * These string have either '+' or '-' as first character.
     * changeDesc changes '+' to '-' and '-' to '+' in the descriptor string of each element of $summands and leaves other parts unchanged
     * The original array with changed descriptors is returned
     * 
     * @param array $summands 
     * @return array 
     * @throws isMathException 
     */
    private function changeDescSign(array $summands):array {
        for ($i = 0; $i < count($summands); $i++) {
            if ($summands[$i][1][0] == '+') {
                $summands[$i][1][0] = '-';
            } elseif ($summands[$i][1][0] == '-') {
                $summands[$i][1][0] = '+';
            } else {
                // Illegal sign in summand
                \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 8);
            }
        }
        return $summands;
    }

    /**
     * $node is a parse tree
     * $summands is an array of summands as described in $this->getSummands
     * recAppSummand appends to summands all directly reachable summands of the left and right subtree in case of a summation operator
     * In case of a unary minus the summands of its subtree are appended with inverted sign
     * 
     * @param mixed $node 
     * @param mixed $summands 
     * @return array 
     */
    private function recAppSummands(array $node, array $summands):array {
        if ($node['tk'] == '+') {
            return array_merge($summands, $this->recAppSummands($node['l'], $summands), $this->recAppSummands($node['r'], $summands));
        } elseif ($node['tk'] == '-') {
            if ($this->isUnaryMinus($node)) {
                return array_merge($summands, $this->changeDescSign($this->recAppSummands($node['u'], $summands)));
            } else {
                return array_merge($summands, $this->recAppSummands($node['l'], $summands), 
                                   $this->changeDescSign($this->recAppSummands($node['r'], $summands)));
            }
        } else {
            $summands[] = [$node, '+'];
            return $summands;
        }
    }

    /**
     * $node is a parse tree
     * getSummands returns an array of directly reachable summands in $node
     * A summand is directly reachable if it can be reached in a descent through the tree $node, 
     * which passes only through summation or unary minus nodes. 
     * A summand is itself an array, with a parse tree in position 0 and a descriptor string in position 1
     * The descriptor is a sign, either '+' or '-'
     * The sign determines if the value of the subtree has to be added or subtracted to get the value
     * of the sum of all summands in such a way, that it is the same as the evaluation of $node
     * 
     * @param array $node 
     * @return array 
     */
    private function getDirectSummands(array $node):array {
        return $this->recAppSummands($node, []);
    }

    private function dstMult(array $node, string $gsign):array {
        $isSumL = $this->isAddNode($node['l']);
        $isSumR = $this->isAddNode($node['r']);
        if ($isSumL && $isSumR) {
            // Both factors are sums
            $summandsl = $this->getDirectSummands($node['l']);
            $summandsr = $this->getDirectSummands($node['r']);
            $psummands = [];
            foreach ($summandsl as $sl) {
                foreach ($summandsr as $sr) {
                    $p = ['tk' => '*', 'type' => 'matop', 'restype' => 'float'];
                    $p['r'] = $sr[0];
                    $p['l'] = $sl[0];
                    if ($sl[1] == $sr[1]) {
                        $psummands[] = [$p, '+'];
                    } else {
                        $psummands[] = [$p, '-'];
                    }
                }
            }
        } elseif ($isSumL) {
            // Only the left factor is a sum
            $summandsl = $this->getDirectSummands($node['l']);
            $psl = [];
            // Multiply right subtree by each of $summandsl
            foreach ($summandsl as $sl) {
                $p = ['tk' => '*', 'type' => 'matop', 'restype' => 'float'];
                $p['l'] = $sl[0];
                $p['r'] = $this->dst($node['r'], $gsign);
                $psl[] = [$p, $sl[1]];
            }
            $psummands = $psl;
        } elseif ($isSumR) {
            // Only the right factor is a sum
            $summandsr = $this->getDirectSummands($node['r']);
            $psr = [];
            // Multiply left subtree by each of $summandsr
            foreach ($summandsr as $sr) {
                $p = ['tk' => '*', 'type' => 'matop', 'restype' => 'float'];
                $p['l'] = $this->dst($node['l'], $gsign);
                $p['r'] = $sr[0];
                $psr[] = [$p, $sr[1]];
            }
            $psummands = $psr;
        } else {
            // Neither factor is a sum
            return $node;
        }


        // Build the summation tree from $psummands
        $nr = count($psummands);
        if ($gsign == '-') {
            for ($i = 0; $i < $nr; $i++) {
                if ($psummands[$i][1] == '+') {
                    $psummands[$i][1] = '-';
                } else {
                    $psummands[$i][1] = '+';
                }
            }
        }
        if ($nr < 2) {
            \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 1);
        }

        $sign = $psummands[0][1];
        if ($sign == '-') {
            // We need a unary minus for the deepest summand (the first in conventional notation)
            $l = ['tk' => '-', 'type' => 'matop', 'restype' => 'float', 'u' => $psummands[0][0]];
        } else {
            $l = $psummands[0][0];
        }
        $r = $psummands[1][0];
        $n = ['tk' => $psummands[1][1], 'type' => 'matop', 'restype' => 'float', 'l' => $l, 'r' => $r];

        // Add leading summands at the top of the chain
        for ($i = 2; $i < $nr; $i++) {
            $n = ['tk' => $psummands[$i][1], 'type' => 'matop', 'restype' => 'float', 'l' => $n, 'r' => $psummands[$i][0]];
        }

        // Add the intermediate result to $this->trfSequence for debuggin purposes
        $LlateX = new \isLib\Llatex($n);
        $this->trfSequence[]= $LlateX->getLatex();

        return $n;
    }

    /**
     * Returns a parse tree, which is mathematically equivalent to the parse tree $node,
     * without sums as factors within a product
     * 
     * @param array $node 
     * @return array 
     * @throws isMathException 
     * @throws Exception 
     */
    private function dst(array $node):array {
        if ($this->isTerminal($node)) {
            return $node;
        } elseif ($this->isMultNode($node)) {
            $n = ['tk' => $node['tk'], 'type' => 'matop', 'restype' => 'float'];
            $n['l'] = $this->dst($node['l']);
            $n['r'] = $this->dst($node['r']);          
            return $this->dstMult($n, '');                
        } elseif ($this->isAddNode($node)) {
            // '+', '-' not unary     
            $n = ['tk' => $node['tk'], 'type' => 'matop', 'restype' => 'float'];
            $n['l'] = $this->dst($node['l'], $node['tk']);
            $n['r'] = $this->dst($node['r'], $node['tk']);
            return $n;
        } elseif ($this->isUnaryMinus($node)) {
            // Unary '-'
            $trialN = $this->dst($node['u']);            
            if ($this->isUnaryMinus($trialN)) {
                // Double unary
                return $this->dst($trialN['u']);
            } else {
                $n = ['tk' => '-', 'type' => 'matop', 'restype' => 'float'];
                $n['u'] = $trialN;
            }
            return $n;
        } elseif ($node['type'] == 'function') {
            $n = ['tk' => $node['tk'], 'type' => 'function', 'restype' => 'float'];
            $n['u'] = $this->dst($node['u']);
            return $n;
        } elseif ($node['tk'] == '/' || $node['tk'] == '^') {
            // Quotient or Power. Handle numerator and denominator e.g. base and exponentseparately
            $n = ['tk' => $node['tk'], 'type' => 'matop', 'restype' => 'float'];
            $n['l'] = $this->dst($node['l']);
            $n['r'] = $this->dst($node['r']);
            return $n;
        } else {
            // Unhandled node in dst
            \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 6);
        }
    }

    /**
     * Returns a mathematically equivalen 
     * @return array 
     * @throws isMathException 
     */
    public function applyDistLaw():array {
        $result = $this->dst($this->inputTree);
        return $result;
    }

    private function handleEvalSubtree(array $node):array {
        $n = $this->copyNodeHeader($node);
        if (isset($node['u'])) {
            // unary node
            $n['u'] = $this->eval($node['u']);
        } elseif ($this->isTerminal($node)) {
            // Do nothing, terminal nodes have no link
        } else {
            // binary node
            $n['l'] = $this->eval($node['l']);
            $n['r'] = $this->eval($node['r']);
        }
        return $n;
    }


    /**
     * Partialy evaluates $node by replacing subtrees without variables by their evaluated numeric value
     * 
     * @param array $node 
     * @return array 
     */
    private function eval(array $node):array {
        if ($this->isNumeric($node)) {
            return $node;
        }
        if ($node['tk'] == '*' || $node['tk'] == '?') {
            $l = $this->eval($node['l']);
            $r = $this->eval($node['r']);
            if ($this->isNumeric($l) && $this->isNumeric($r)) {
                // Replace the subtree by a number
                $leftval = $this->strToFloat($l['value']);
                $rightval = $this->strToFloat($r['value']);
                $product = $this->floatToStr($leftval * $rightval);
                $n = ['tk' => $product, 'type' => 'number', 'restype' => 'float', 'value' => $product];
            } else {
                $n = $this->handleEvalSubtree($node);
            }
        } elseif ($node['tk'] == '+' || $node['tk'] == '-' && !isset($node['u'])) {
            $l = $this->eval($node['l']);
            $r = $this->eval($node['r']);
            if ($this->isNumeric($l) && $this->isNumeric($r)) {
                // Replace the subtree by a number
                $leftval = $this->strToFloat($l['value']);
                $rightval = $this->strToFloat($r['value']);
                if ($node['tk'] == '+') {
                    $sum = $this->floatToStr($leftval + $rightval);
                } else {
                    $sum = $this->floatToStr($leftval - $rightval);
                }
                $n = ['tk' => $sum, 'type' => 'number', 'restype' => 'float', 'value' => $sum];
            } else {
                $n = $this->handleEvalSubtree($node);
            }
        } else {
            $n = $this->handleEvalSubtree($node);
        }
        return $n;
    }

    public function partEvaluate(array $node):array {
        return $this->eval($node);
    }

    /**
     * If $a lexicografically preceeds $b returns -1 else +1 or 0 in case they are identical
     * 
     * @param mixed $a 
     * @param mixed $b 
     * @return int 
     */
    private function strCmp($a, $b) {
        $i = 0;
        $la = strlen($a);
        $lb = strlen($b);
        while ($i < $la && $i < $lb) {
            if (ord($a[$i]) < ord($b[$i])) {
                return -1;
            } elseif (ord($a[$i]) > ord($b[$i])) {
                return 1;
            }
        }
        // If we get here common positions are equal, so we prefer the shorter
        if ($la < $lb) {
            return -1;
        } elseif ($la > $lb) {
            return 1;
        }
        // $a and $b are identical
        return 0;
    }

    private function tkCmp($a, $b) {
        return $this->strCmp($a[0]['tk'], $b[0]['tk']);
    }

    private function valCmp($a, $b) {
        $vala = $this->strToFloat($a[0]['value']);
        $valb = $this->strToFloat($b[0]['value']);
        if ($vala < $valb) {
            return -1;
        } elseif ($vala > $valb) {
            return 1;
        }
        return 0;
    }

    /**
     * Returns the array $elements sorted by type as first criterion and value or name as second criterion
     * The single elements are arrays, having the nodes to be sorted in position 0. Position 1 is used by commAss functions 
     * 
     * @param array $elements 
     * @return array 
     */
    private function caoSortMult(array $elements):array {
        $variables = [];
        $functions = [];
        $mathconstants =[];
        $numbers = [];
        $quotients = [];
        $powers = [];
        $additions = [];
        foreach ($elements as $element) {
            switch ($element[0]['type']) {
                case 'variable':
                    $variables[] = $element;
                    break;
                case 'function':
                    $functions[] = $element;
                    break;
                case 'mathconst':
                    $mathconstants[] = $element;
                    break;
                case 'number':
                    $numbers[] = $element;
                    break;
                case 'matop':
                    if ($element[0]['tk'] == '/') {
                        $quotients[] = $element;
                    } elseif ($element[0]['tk'] == '^') {
                        $powers[] = $element;
                    } elseif ($this->isAddNode($element[0])) {
                        $additions[] = $element;
                    } elseif ($this->isMultNode($element[0])) {
                        // Unexpected mult node
                        \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 4);
                    }
            }
        }
        usort($variables, [$this, 'tkCmp']);
        usort($functions, [$this, 'tkCmp']);
        usort($mathconstants, [$this, 'tkCmp']);
        usort($numbers, [$this, 'valCmp']);
        return array_merge($numbers, $mathconstants, $additions, $functions, $quotients, $powers, $variables);
    }

    private function recAppendFactors(array $node, array $collection, bool &$even):array {
        if ($this->isNumeric($node) || $node['type'] == 'variable') {
            // Terminal
            $collection[] = [$node, ''];
        } elseif ($this->isMultNode($node)) {
            $collection = array_merge($collection, $this->recAppendFactors($node['l'], $collection, $even), $this->recAppendFactors($node['r'], $collection, $even));
        } elseif ($this->isAddNode($node)) {
            $collection[] = [$this->commAssOrd($node), ''];
        } elseif ($node['type'] == 'function') {
            $collection[] = [$this->commAssOrd($node), ''];
        } elseif ($node['tk'] == '-' && isset($node['u'])) {
            $even = !$even;
            $collection = array_merge($collection, $this->recAppendFactors($node['u'], $collection, $even));
        } elseif ($node['tk'] == '/' || $node['tk'] == '^') {
            $n = ['tk' => $node['tk'], 'type' => 'matop', 'restype' => 'float'];
            $n['l'] = $this->commAssOrd($node['l']);
            $n['r'] = $this->commAssOrd($node['r']);
            $collection[] = [$n, ''];
        } else {
            // Unhandled factor
            $collection[] = [$this->commAssOrd($node), ''];
        }
        return $collection;
    }

    private function collectFactors(array $node, bool &$even):array {
        return $this->recAppendFactors($node, [], $even);
    }

    /**
     * $node is a parse tree beginning with a 'mult' node i.e. '*' or '?'
     * 
     * $this->caoMult returns a mathematically equivalent tree in which adjacent factors are ordered
     * Every factor is itself a parse tree beginning with a 'number', 'mathconst', 'var', 'function', node 
     * or a 'mult' node i.e. '*' or '?'
     * 
     * REMARK matop nodes like '+', '-' (binary), '/', '^' cannot be handled and throw an exception
     * 
     * @param array $node 
     * @return array 
     * @throws isMathException 
     */
    private function caoMult(array $node):array {
        $even = true;
        $factors = $this->collectFactors($node, $even);

        // Debugging
        $this->summands = $factors;

        $factors = $this->caoSortMult($factors);
        $nr = count($factors);

        // Build the consolidatet tree
        // ===========================

        // The first two factors constitute the end of the product chain
        if ($nr < 2) {
            // Factor array below 2
            \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 2);
        }
        $l = $factors[0][0];
        $r = $factors[1][0];
        $n = ['tk' => '*', 'type' => 'matop', 'restype' => 'float', 'l' => $l, 'r' => $r];

        // Add leading factors at the top of the chain
        for ($i = 2; $i < $nr; $i++) {
            $n = ['tk' => '*', 'type' => 'matop', 'restype' => 'float', 'l' => $n, 'r' => $factors[$i][0]];
        }
        if ($even) {
            return $n;
        } else {
            return ['tk' => '-', 'type' => 'matop', 'restype' => 'float', 'u' => $n];
        }
    }

    /**
     * Returns the array $elements sorted by type as first criterion and value or name as second criterion
     * The single elements are arrays, having the nodes to be sorted in position 0. Position 1 is used by commAss functions 
     * 
     * @param array $elements 
     * @return array 
     */
    private function caoSortAdd(array $elements):array {
        $variables = [];
        $functions = [];
        $mathconstants =[];
        $numbers = [];
        $products = [];
        $quotients = [];
        $powers = [];
        foreach ($elements as $element) {
            switch ($element[0]['type']) {
                case 'variable':
                    $variables[] = $element;
                    break;
                case 'function':
                    $functions[] = $element;
                    break;
                case 'mathconst':
                    $mathconstants[] = $element;
                    break;
                case 'number':
                    $numbers[] = $element;
                    break;
                case 'matop':
                    if ($this->isMultNode($element[0])) {
                        $products[] = $element;
                    } elseif ($this->isUnaryMinus($element[0]) && $this->isMultNode($element[0]['u'])) {
                        // negated product
                        $products[] = [$element[0]['u'], '-'];
                    } elseif ($element[0]['tk'] == '/') {
                        $quotioents[] = $element;
                    } elseif ($element[0]['tk'] == '^') {
                        $powers[] = $element;
                    } elseif ($this->isAddNode($element[0])) {
                        // Unexpected add node
                        \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 5);
                    } else {
                        // Unhandled node in caoSortAdd
                        \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 7);
                    }
                    break;
                default:
                    // Unhandled node in caoSortAdd
                    \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 7);
            }
        }
        usort($variables, [$this, 'tkCmp']);
        usort($functions, [$this, 'tkCmp']);
        usort($mathconstants, [$this, 'tkCmp']);
        usort($numbers, [$this, 'valCmp']);
        return array_merge($numbers, $mathconstants, $products, $functions, $quotients, $powers, $variables);
    }

    /*
    private function changeDescSign(array $summands):array {
        foreach($summands as $key => $summand) {
            if ($summand[1][0] == '+') {
                $new = '-';
            } else {
                $new = '+';
            }
            $summands[$key][1][0] = $new;
        }
        return $summands;
    }
        */

    private function recAppendSummands(array $node, array $collection):array {
        if ($this->isNumeric($node) || $node['type'] == 'variable') {
            // Terminal
            $collection[] = [$node, '+'];
        } elseif ($node['tk'] == '+') {
            // Sum: append summands of left and of right subtree
            $collection = array_merge($collection, $this->recAppendSummands($node['l'], $collection), $this->recAppendSummands($node['r'], $collection));
        } elseif ($node['tk'] == '-') {
            // Difference or unary minus
            if (isset($node['u'])) {
                // Negated subtree: remove negation, but change signs of subtree summands
                $negated = $this->changeDescSign($this->recAppendSummands($node['u'], $collection));
                $collection = array_merge($collection, $negated);
            } else {
                // Difference: append summands of left subtree and sign inverted summands of right subtree
                // NOTE: It is essential that both left and right node be merged in the same array_merge, 
                // because the second factor ($collection) must be tehe same in both recAppendSummands.
                // If we want to split the merger in two mergers, we must first register the old $collection, lest we merge the left tree twice
                $collection = array_merge($collection, $this->recAppendSummands($node['l'], $collection),
                                          $this->changeDescSign($this->recAppendSummands($node['r'], $collection)));
            }
        } elseif ($this->isMultNode($node)) {
            // The summand itself is a product
            $collection[] = [$this->commAssOrd($node), '+'];
        } elseif ($node['type'] == 'function') {
            $func = [$node, '+'];
            $collection[] = $func;
        }
        return $collection;
    }

    /**
     * $node is a parse tree. 
     * collectSummands returns an array of the summands in $node by bottom up traversation.
     * A summand is an array of a terminal summand in position 0 and a descriptor in position 1.
     * A terminal summand is a parse tree with a start node that is one of 'number', 'mathconst', 'variable' or a function summand.
     * In case of 'number', 'mathconst', 'variable' the terminal command is a parse tree. The descriptor is the sign i.e. either '+' or '-'
     * In case of a function summand the terminal command is the array of summands of the argument. 
     * The descriptor is the name of the function preceeded by a '+' or a '-'.
     * Unary minus in $node are handled, by changin the sign in descriptors
     * 
     * @param array $node 
     * @return array 
     */
    private function collectSummands(array $node):array {
        return $this->recAppendSummands($node, []);
    }

    /**
     * $node is a parse tree starting with an 'add' node i.e. '+' or '-' (binary)
     * caoAdd returns a parse tree, with all additions consolidated to a left associative ordered addition tree.
     * 
     * 
     * @param array $node 
     * @return array 
     */
    private function caoAdd(array $node):array {
        $summands = $this->collectSummands($node);
        $nr = count($summands);

        // For debugging display
        $this->summands = $summands;

        // Handle arguments of summands, which are one parameter functions
        for ($i = 2; $i < $nr; $i++) {
            if ($summands[$i][0]['type'] == 'function' && isset($summands[$i][0]['u'])) {
                // Replace argument
                $handledArg = $this->commAssOrd($summands[$i][0]['u']);
                $summands[$i][0]['u'] = $handledArg;
            }
        }

        // Order the summands
        $summands = $this->caoSortAdd($summands);
        $nr = count($summands);

        // Build the consolidatet tree
        // ===========================

        // The first two summands constitute the end of the addition chain
        if ($nr < 2) {
            // Summand array below 2
            \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 1);
        }
        $sign = $summands[0][1];
        if ($sign == '-') {
            // We need a unary minus for the deepest summand (the first in conventional notation)
            $l = ['tk' => '-', 'type' => 'matop', 'restype' => 'float', 'u' => $summands[0][0]];
        } else {
            $l = $summands[0][0];
        }
        $r = $summands[1][0];
        $n = ['tk' => $summands[1][1], 'type' => 'matop', 'restype' => 'float', 'l' => $l, 'r' => $r];

        // Add leading summands at the top of the chain
        for ($i = 2; $i < $nr; $i++) {
            $n = ['tk' => $summands[$i][1], 'type' => 'matop', 'restype' => 'float', 'l' => $n, 'r' => $summands[$i][0]];
        }
        return $n;
    }

    /**
     * Transforms the parse tree $node into a mathematically aequivalent tree 
     * using the commutative and associative property of addition and multiplication.
     * In the result sums and products are left associative and ordered.
     * 
     * @param array $node 
     * @return array 
     */
    public function commAssOrd(array $node):array {
        if ($this->isTerminal($node)) {
            // Variable, number, mathconst
            return $node;
        } elseif ($this->isMultNode($node)) {
            // '*', '?'
            return $this->caoMult($node);
        } elseif ($this->isAddNode($node)) {
            // '+', '-' not unary     
            return $this->caoAdd($node);
        } elseif ($this->isUnaryMinus($node)) {
            // Unary '-'
            $trialN = $this->commAssOrd($node['u']);            
            if ($this->isUnaryMinus($trialN)) {
                // Double unary
                return $this->commAssOrd($trialN['u']);
            } else {
                $n = ['tk' => '-', 'type' => 'matop', 'restype' => 'float'];
                $n['u'] = $trialN;
            }
            return $n;
        } elseif ($node['type'] == 'function') {
            $n = ['tk' => $node['tk'], 'type' => 'function', 'restype' => 'float'];
            $n['u'] = $this->commAssOrd($node['u']);
            return $n;
        } elseif ($node['tk'] == '/' || $node['tk'] == '^') {
            // Quotient or Power. Handle numerator and denominator e.g. base and exponentseparately
            $n = ['tk' => $node['tk'], 'type' => 'matop', 'restype' => 'float'];
            $n['l'] = $this->commAssOrd($node['l']);
            $n['r'] = $this->commAssOrd($node['r']);
            return $n;
        } else {
            // Unhandled node in commAssOrd
            \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 3);
        }
    }

    private function recAppFactors(array $node, array $factors, bool &$even):array {
        if ($this->isMultNode($node)) {
            $lf = $this->recAppFactors($node['l'], $factors, $even);
            $rf = $this->recAppFactors($node['r'], $factors, $even);
            $merger = array_merge($factors, $lf, $rf);
            return $merger;
        } elseif ($this->isUnaryMinus($node)) {
            $uf = $this->recAppFactors($node['u'], $factors, $even);
            $even = $even == true ? false : true;
            $merger = array_merge($factors, $uf);
            return $merger;
        } else {
            $factors[] = [$node, ''];
            return $factors;
        }
    }

    private function getDirectFactors(array $node, bool &$even):array {
        return $this->recAppFactors($node, [], $even);
    }

    /**
     * Returns a parse tree mathematically equivalent to the parse tree $node, in which all products are ordered
     * The first node in $node is a product
     * 
     * @param array $node 
     * @return array 
     */
    private function ordProd(array $node):array {
        $even = true;
        $factors = $this->getDirectFactors($node, $even);
        $factors = $this->caoSortMult($factors);

        $nr = count($factors);

        // Build the consolidatet tree
        // ===========================

        // The first two factors constitute the end of the product chain
        if ($nr < 2) {
            // Factor array below 2
            \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 2);
        }
        $l = $factors[0][0];
        $r = $factors[1][0];
        $n = ['tk' => '*', 'type' => 'matop', 'restype' => 'float', 'l' => $l, 'r' => $r];

        // Add leading factors at the top of the chain
        for ($i = 2; $i < $nr; $i++) {
            $n = ['tk' => '*', 'type' => 'matop', 'restype' => 'float', 'l' => $n, 'r' => $factors[$i][0]];
        }
        if ($even) {
            return $n;
        } else {
            return ['tk' => '-', 'type' => 'matop', 'restype' => 'float', 'u' => $n];
        }
        return $node;
    }

    /**
     * Returns a parse tree mathematically equivalent to the parse tree $node, with ordered products
     * 
     * @param array $node 
     * @return array 
     */
    public function ordProducts(array $node):array {
        if ($this->isTerminal($node)) {
            return $node;
        } elseif ($this->isMultNode($node)) {
            return $this->ordProd($node);
        } elseif ($this->isAddNode($node)) {
            $n = ['tk' => $node['tk'], 'type' => 'matop', 'restype' => 'float'];
            $n['l'] = $this->ordProducts($node['l']);
            $n['r'] = $this->ordProducts($node['r']);
            return $n;
        } else {
            // Unhandled node in commAssOrd
            \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 3);
        }
    }
}