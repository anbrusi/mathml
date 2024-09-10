<?php
namespace isLib;

use Exception;

/**
 * Input is a parse tree, output its LateX representation
 * The parse tree must follow the specs of a the parse tree given in LasciiParser
 * 
 * @package isLib
 */
class Llatex {

    /**
     * The original tree representation of the formula for which a LateX representation is required
     * 
     * @var array
     */
    private array $parseTree;

    /**
     * The LateX representation of the parse tree
     * 
     * @var string
     */
    private string $lateX = '';

    private string $errtext = '';

    function __construct(array $parseTree) {
        $this->parseTree = $parseTree;
    }

    /**
     * Only the first error is retained. Stores $txt as error text if no previous error has been stored.
     * 
     * @param string $txt 
     * @return void 
     */
    private function setError(string $txt) {
        if ($this->errtext === '') {
            $this->errtext = $txt;
        }
    }

    /**
     * Highest precedence first
     * 
     * @param string $operator 
     * @return int 
     * @throws Exception 
     */
    private function operatorPrecedence(string $operator):int {
        switch ($operator) {
            // matop
            case '^':
                return 1;
            case '*':
            case '?':
            case '/':
                return 2;
            case '+':
            case '-':
                return 3;
            // boolop
            case '!':
                return 11;
            case '&':
                return 12;
            case '|':
                return 13;
            default:
                throw new \Exception('Missing operator precedence for operator '.$operator);
        }
    }

    private function nodePrecedence(array $node):int {
        switch ($node['type']) {
            case 'cmpop';
            case 'matop':
                return $this->operatorPrecedence($node['tk']);
            case 'number':
            case 'mathconst':
            case 'variable':
            case 'function':
                return 0;
        }
    }

    private function wrapWithParen(string $latex):string {
        return '\left('.$latex.'\right)';
    }

    private function wrapWithSpace(string $latex):string {
        return '\:'.$latex.'\:';
    }

    private function isalpha(array $node):bool {
        return $node['type'] == 'variable' || $node['type'] == 'function';
    }

    private function multiplication(array $node, bool $implicit=false):string {
        $precedence = $this->operatorPrecedence($node['tk']);
        $leftPrecedence = $this->nodePrecedence($node['l']);
        $rightPrecedence = $this->nodePrecedence($node['r']);
        $leftTree = $this->nodeToLatex($node['l']);
        $rightTree = $this->nodeToLatex($node['r']);
        if ($implicit && $this->isAlpha($node['r'])) {
            // No operator sign if the right node is alpha
            $mulop = '';
        } else {
            $mulop = ' \cdot ';
        }
        if ($leftPrecedence > $precedence) {
            // Multiplication is left associative, so no parenthesis around left subtree is required due to operator precedence
            $leftTree = $this->wrapWithParen($leftTree);
        }
        if ($rightPrecedence > $precedence || $node['r']['tk'] == '*' || $node['r']['tk'] == '?') {
            $rightTree = $this->wrapWithParen($rightTree);
        }
        return $leftTree.$mulop.$rightTree;
    }

    /**
     * Exponentiation is by definition right associative. This means that 2^3^4 is 2^(3^4) and not (2^3)^4
     * LateX takes care of this by making exponents increasingly smaller if right associativity is required
     * If deviating from the normal case left associativity is required exponents are all of the same size,
     * which might be unclear. This functions adds a parentheses to make clear the deviation.
     * 
     * @param mixed $node 
     * @return string 
     * @throws Exception 
     */
    private function exponentiation($node):string {
        $leftTree = $this->nodeToLatex($node['l']);
        $rightTree = $this->nodeToLatex($node['r']);
        if ($node['l']['tk'] == '^') {
            $leftTree = $this->wrapWithParen($leftTree);
        }
        return '{'.$leftTree.'}^{'.$rightTree.'}';
    }

    private function binopNode(array $node):string {
        $left = $this->nodeToLatex($node['l']);
        $right = $this->nodeToLatex($node['r']);
        switch ($node['tk']) {
            // matop cases
            case '+': // addition
                return $left.'+'.$right;
            case '-': // subtraction
                return $left.'-'.$right;
            case '*': // explicit multiplication
                return $this->multiplication($node, false);
            case '?': // implicit multiplication
                return $this->multiplication($node, true);
            case '/': // division
                return '\frac{'.$left.'}{'.$right.'}';
            case '^': // exponentiation
                // return '{'.$left.'}^{'.$right.'}';
                // LateX might be misinterpreted in case left associativity is required, so we prefer to add a parentheses
                return $this->exponentiation($node);
            // cmpop cases
            case '=':
                return $left.'='.$right;
            case '<>':
                return $left.'\neq'.$right;
            case '<':
                return $left.'<'.$right;
            case '<=':
                return $left.'\le'.$right;
            case '>':
                return $left.'>'.$right;
            case '>=':
                return $left.'\ge'.$right;
            // boolop cases
            case '|':
                return $left.$this->wrapWithSpace('{\lor}').$right;
            case '&':
                return $left.$this->wrapWithSpace('{\land}').$right;
            default:
                return $left.'??'.$right;
        }
    } 

    private function unopNode(array $node):string {
        $child = $this->nodeToLatex($node['u']);
        switch ($node['tk']) {
            case '-':
                // The only case needing clarification is when the child has addition or subtraction at top level
                if ($node['u']['tk'] == '+' || $node['u']['tk'] == '-') {
                    $child = $this->wrapWithParen($child);
                }
                return '-'.$child;
            case '!':
                if ($node['u']['tk'] == '|' || $node['u']['tk'] == '&') {
                    $child = $this->wrapWithParen($child);
                }
                return $this->wrapWithSpace('\neg').$child;
            default:
                return '??';
        }
    }

    private function valueNode(array $node):string {
        return $node['value'];
    }

    private function variableNode(array $node):string {
        return $node['tk'];
    }

    private function functionNode(array $node):string {
        if (isset($node['u']) ) {
            // function with one argumenz
            $argument = $this->nodeToLatex($node['u']);
            return '\\'.$node['tk'].'{\left('.$argument.'\right)}';
        } elseif (isset($node['l']) && isset($node['r'])) {
            // function with two arguments
            $argument1 = $this->nodeToLatex($node['l']);
            $argument2 = $this->nodeToLatex($node['r']);
            return '\\'.$node['tk'].'{\left('.$argument1.'\:,\:'.$argument2.'\right)}';
        } else {
            return 'Unhandled function node';
        }
    }

	public function nodeToLatex(array $node):string {
        switch ($node['type']) {
            case 'cmpop':
            case 'matop':
                // The only unary node is unary minus
                if ($node['tk'] == '-' && isset($node['u']) ) {
                    // Unary minus
                    return $this->unopNode($node);
                } else {
                    return $this->binopNode($node);
                }
            case 'boolop':
                // The only unary node is negation
                if ($node['tk'] == '!' && isset($node['u'])) {
                    return $this->unopNode($node);
                } else {
                    return $this->binopNode($node);
                }
            case 'number':
            case 'mathconst':
                return $this->valueNode($node);
            case 'variable':
                return $this->variableNode($node);
            case 'function':
                return $this->functionNode($node);
            case 'boolvalue':
                return '{'.$this->valueNode($node).'}';
            default:
                $this->setError('unimplemented node type '.$node['type']);
                return '';
        }
    }

    public function showErrors():string {
        return $this->errtext;
    }

    public function makeLateX():bool {
        $this->lateX = $this->nodeToLatex($this->parseTree);
        return $this->errtext === '';
    }

    public function getLateX():string {
        return $this->lateX;
    }
}