<?php
/**
 * @abstract
 * Implements arithmetics on the set of integer numbers. This set is usually denoted Z 
 * The implementation is an extension of \isLib\LncNaturalNumbers taking care of signs
 * 
 * Integer numbers are numbers in base radix. The "digits" have values from 0 to radix - 1. Digits are themselves non negative PHP integers.
 * The digits are stored in a numeric PHP array. Element 0 is positive for positive numbers and negative for negative numbers.
 * Its absolute value is the number of digits. If it is 0, it denotes the number zero and there are no digits.
 * The digits are stored from 1 up, least significant first.
 */
namespace isLib;
class LncIntegers {
    /**
     * An instance of LncNaturalNumbers
     * 
     * @var \isLib\LncNaturalNumbers
     */
    private $LncNaturalNumbers;

    function __construct(int $radix) {
        $this->LncNaturalNumbers = new \isLib\LncNaturalNumbers($radix);
    }
    /**
     * Returns a living instance of LncNaturalNumbers
     * 
     * @return LncNaturalNumbers 
     */
    public function natNumbers():LncNaturalNumbers {
        return $this->LncNaturalNumbers;
    }
    /**
     * Changes the sign of an integer number
     * 
     * @param array $u 
     * @return void 
     */
    public function intChgSign(array &$u) {
        if ($u[0] != 0) {
            $u[0] = -$u[0];
        }
    }
    public function strToInt(string $str):array {
        $str = trim($str);
        // Look for a leading minus sign
        if (strpos($str, '-') === 0) {
            $negative = true;
            // Eliminate the leading minus
            $str = substr($str, 1);
        } else {
            $negative = false;
        }
        $u = $this->LncNaturalNumbers->strToNn($str);
        if ($negative) {
            $this->intChgSign($u);
        }
        return $u;
    }
    public function intToStr(array $in):string {
        if ($in[0] < 0) {
            $this->intChgSign($in);
            $negative = true;
        } else {
            $negative = false;
        }
        $str = $this->LncNaturalNumbers->nnToStr($in);
        if ($negative) {
            $str = '-'.$str;
        }
        return $str;
    }
    public function showInt(array $in):string {
        // Display function for natural numbers works as well for integer numbers
        return $this->LncNaturalNumbers->showNn($in);
    }
    /**
     * Algebraic sum of $u and $v
     * 
     * @param array $u 
     * @param array $v 
     * @return array 
     */
    public function intAdd(array $u, array $v):array {
        if ($u[0] < 0) {
            $unegative = true;
            $this->intChgSign($u);
        } else {
            $unegative = false;
        }
        if ($v[0] < 0) {
            $vnegative = true;
            $this->intChgSign($v);
        } else {
            $vnegative = false;
        }
        if (($unegative && $vnegative) || (!$unegative && !$vnegative)) {
            // same sign
            $sum = $this->LncNaturalNumbers->nnAdd($u, $v);
            if ($unegative) {
                // Both summands are negative, change the sum
                $this->intChgSign($sum);
            }
        } else {
            // sign is different
            $comparison = $this->LncNaturalNumbers->nnCmp($u, $v);
            if ($comparison == 1) {
                // $u > $v
                $sum = $this->LncNaturalNumbers->nnSub($u, $v);
                // The sign of $u wins
                if ($unegative) {
                    $this->intChgSign($sum);
                }
            } elseif ($comparison == 0) {
                // $u and $v have different sign, but same absolute value
                $sum = $this->strToInt('0');
            } else {
                // $u < $v
                $sum = $this->LncNaturalNumbers->nnSub($v, $u); 
                // The sign of $v wins
                if ($vnegative) {
                    $this->intChgSign($sum);;
                }
            }
        }
        return $sum;
    }
    /**
     * Returns $u - $v
     * 
     * @param array $u 
     * @param array $v 
     * @return array 
     */
    public function intSub(array $u, array $v):array {
        // Change the sign of $v and add
        $this->intChgSign($v);
        return $this->intAdd($u, $v);
    }
    /**
     * Returns the absolute value of $u
     * 
     * @param array $u 
     * @return array 
     */
    public function intAbs(array $u):array {
        if ($u[0] < 0) {
            $u[0] = -$u[0];
        }
        return $u;
    }
    /**
     * $u and $v are natural numbers as encoded in this class. 
     * nnCmp returns +1, if $u > $v, -1 if $u < $v and 0 if $u = $v
     */
    public function intCmp(array $u, array $v):int {
        if ($u[0] < 0) {
            $unegative = true;
            $this->intChgSign($u);
        } else {
            $unegative = false;
        }
        if ($v[0] < 0) {
            $vnegative = true;
            $this->intChgSign($v);
        } else {
            $vnegative = false;
        }
        if ($unegative && $vnegative) {
            return -$this->LncNaturalNumbers->nnCmp($u,$v);
        } elseif (!$unegative && !$vnegative) {
            return $this->LncNaturalNumbers->nnCmp($u,$v);
        } else {
            // $u and $v have different sign. Note that they cannot be equal, since zero is always counted as positive
            if ($unegative) {
                return -1;
            } else {
                return 1;
            }
        }
    }
    /**
     * Returns the algebraic product of $u and $v
     * 
     * @param array $u 
     * @param array $v 
     * @return array 
     */
    public function intMult(array $u, array $v):array {
        if ($u[0] < 0) {
            $unegative = true;
            $this->intChgSign($u);
        } else {
            $unegative = false;
        }
        if ($v[0] < 0) {
            $vnegative = true;
            $this->intChgSign($v);
        } else {
            $vnegative = false;
        }
        if (($unegative && $vnegative) || (!$unegative && !$vnegative)) {
            // Equal sign, product positive
            return $this->LncNaturalNumbers->nnMult($u,$v);
        } else {
            // Different sign, product negative
            $product =  $this->LncNaturalNumbers->nnMult($u,$v);
            if ($product[0] != 0) {
                // The product could be zero. e.g. -2 * 0
                $this->intChgSign($product);
            }
            return $product;
        }
    }
    /**
     * Divides an integer $u by an integer $v
     * The quotient is positive if $u and $v have the same sign, negative if they have different sign.
     * The absolute value of the remainder is less than the absolute value of the divisor.
     *  7: 3= 2 r= 1
     * -7: 3=-2 r=-1
     *  7:-3=-2 r= 1
     * -7:-3= 2 r=-1
     * 
     * Note that this is not the mathematical convention, where the sign of the remainder is equal to the sign of the divisor
     *  7: 3= 2 r= 1
     * -7: 3=-3 r= 2
     *  7:-3=-3 r=-2
     * -7:-3= 2 r=-1
     * 
     * @param array $u 
     * @param array $v 
     * @return array 
     */
    public function intDivMod(array $u, array $v):array {
        if ($u[0] < 0) {
            $unegative = true;
            $this->intChgSign($u);
        } else {
            $unegative = false;
        }
        if ($v[0] < 0) {
            $vnegative = true;
            $this->intChgSign($v);
        } else {
            $vnegative = false;
        }
        $divMod = $this->LncNaturalNumbers->nnDivMod($u,$v);
        if ($unegative) {
            if ($vnegative) {
                // -7:-3=2 r=-1
                // Change sign of remainder
                $this->intChgSign($divMod['remainder']);
            } else {
                // -7:3=-2 r=-1
                // Change sign of both quotient and remainder
                $this->intChgSign($divMod['quotient']);
                $this->intChgSign($divMod['remainder']);
            }
        } else {
            if ($vnegative) {
                // 7:-3=-2 r=1
                // change sign of quotient
                $this->intChgSign($divMod['quotient']);
            } else {
                // 7:3=2 r=1
                // Nothing to change
            }
        }
        return $divMod;
    }
}