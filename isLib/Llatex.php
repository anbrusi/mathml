<?php
namespace isLib;

use Exception;

/**
 * Generates a LateX representation from a parse tree following the syntax of Lparser
 * 
 * INPUT: parse tree generated by Lparser as parameter of the constructor
 * 
 * OUTPUT: $this->getLatex a string with LateX code for the syntax tree in the input
 * 
 * ERRORS: throws an isMath exception. The optional array info is not used 
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

    function __construct(array $parseTree) {
        $this->parseTree = $parseTree;
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
                // Unknown operator precedence
                \isLib\LmathError::setError(\isLib\LmathError::ORI_LATEX, 1);
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
            default:
                // Unhandled node type
                \isLib\LmathError::setError(\isLib\LmathError::ORI_LATEX, 1);
        }
    }

    private function wrapWithParen(string $latex):string {
        return '\left('.$latex.'\right)';
    }

    private function wrapWithSpace(string $latex):string {
        return '\:'.$latex.'\:';
    }

    private function multiplication(array $node, bool $implicit=false):string {
        $precedence = $this->operatorPrecedence($node['tk']);
        $leftPrecedence = $this->nodePrecedence($node['l']);
        $rightPrecedence = $this->nodePrecedence($node['r']);
        $leftTree = $this->nodeToLatex($node['l']);
        $rightTree = $this->nodeToLatex($node['r']);
        if ($implicit) {
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
     * Likewise a parentheses is added if the base is the result of a mathematical operation like sum or product
     * 
     * @param mixed $node 
     * @return string 
     * @throws Exception 
     */
    private function exponentiation($node):string {
        $leftTree = $this->nodeToLatex($node['l']);
        $rightTree = $this->nodeToLatex($node['r']);
        if ($node['l']['type'] == 'matop') {
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
                if ($right[0] == '-') {
                    $right = $this->wrapWithParen($right);
                }
                return $left.'+'.$right;
            case '-': // subtraction
                if ($right[0] == '-' || $node['r']['tk'] == '+' || $node['r']['tk'] == '-') {
                    $right = $this->wrapWithParen($right);
                }
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

    private function numberNode(array $node):string {
        // Numbers are always positive. Negative numbers are handled with a unary minus operator
        // The format is --> digit {digit} [. digit {digit}] [ 'E' [ '-' ] digit { digit} ]
        $value = $node['value'];
        $parts = explode('E', $value);
        if (count($parts) == 0 || count($parts) > 2) {
            // Invalid number format
            \isLib\LmathError::setError(\isLib\LmathError::ORI_LATEX, 3);
        }
        $latex = $parts['0'];
        if (isset($parts[1])) {
            // Add scale
            $latex .= '\cdot 10 ^ {'.$parts[1].'}';
        }
        return $latex;
    }

    private function variableNode(array $node):string {
        return $node['tk'];
    }

    private function functionNode(array $node):string {
        if (isset($node['u']) ) {
            // function with one argumenz
            $argument = $this->nodeToLatex($node['u']);
            $funcName = $node['tk'];
            // Adjust function names
            switch ($funcName) {
                case 'asin':
                    $funcName = 'arcsin';
                    break;
                case 'acos':
                    $funcName = 'arccos';
                    break;
                case 'atan':
                    $funcName = 'arctan';
                    break;
            }
            if ($funcName == 'sqrt') {
                // Trim parenthesis
                return '\\'.$funcName.'{'.$argument.'}';
            } else {
                return '\\'.$funcName.'{\left('.$argument.'\right)}';
            }
        } elseif (isset($node['l']) && isset($node['r'])) {
            // function with two arguments
            $argument1 = $this->nodeToLatex($node['l']);
            $argument2 = $this->nodeToLatex($node['r']);
            $funcName = $node['tk'];
            if ($funcName == 'rand' || $funcName == 'round') {
                return $funcName.'{\left('.$argument1.'\:,\:'.$argument2.'\right)}';
            } else {
                // min and max are defined in LateX
                return '\\'.$node['tk'].'{\left('.$argument1.'\:,\:'.$argument2.'\right)}';
            }
        } else {
            return 'Unhandled function node';
        }
    }

    private function valueNode(array $node):string {
        return $node['value'];
    }

    private function mathconstNode(array $node):string {
        switch ($node['tk']) {
            case 'e':
                return 'e';
            case 'pi':
                return '\pi';
            default:
                return 'Unknown constant';
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
                return $this->numberNode($node);
            case 'mathconst':
                return $this->mathconstNode($node);
            case 'variable':
                return $this->variableNode($node);
            case 'function':
                return $this->functionNode($node);
            case 'boolvalue':
                return '{'.$this->valueNode($node).'}';
            default:
                // unimplemented node type
                \isLib\LmathError::setError(\isLib\LmathError::ORI_LATEX, 2);
        }
    }

    public function getLatex():string {
        return $this->nodeToLatex($this->parseTree);
    }
}