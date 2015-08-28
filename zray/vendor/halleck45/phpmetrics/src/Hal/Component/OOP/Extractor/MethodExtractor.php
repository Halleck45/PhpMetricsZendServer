<?php

/*
 * (c) Jean-François Lépine <https://twitter.com/Halleck45>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hal\Component\OOP\Extractor;
use Hal\Component\OOP\Reflected\MethodUsage;
use Hal\Component\OOP\Reflected\ReflectedArgument;
use Hal\Component\OOP\Reflected\ReflectedMethod;
use Hal\Component\Token\TokenCollection;


/**
 * Extracts info about classes in one file
 * Remember that one file can contains multiple classes
 *
 * @author Jean-François Lépine <https://twitter.com/Halleck45>
 */
class MethodExtractor implements ExtractorInterface {

    /**
     * @var Searcher
     */
    private $searcher;

    /**
     * Constructor
     *
     * @param Searcher $searcher
     */
    public function __construct(Searcher $searcher)
    {
        $this->searcher = $searcher;
    }

    /**
     * Extract method from position
     *
     * @param int $n
     * @param TokenCollection$tokens
     * @return ReflectedMethod
     * @throws \Exception
     */
    public function extract(&$n, TokenCollection $tokens)
    {
        $declaration = $this->searcher->getUnder(array(')'), $n, $tokens);
        if(!preg_match('!function\s+(.*)\(\s*(.*)!is', $declaration, $matches)) {
            throw new \Exception(sprintf("Closure detected instead of method\nDetails:\n%s", $declaration));
        }
        list(, $name, $args) = $matches;
        $method = new ReflectedMethod($name);

        $arguments = preg_split('!\s*,\s*!m', $args);
        foreach($arguments as $argDecl) {

            if(0 == strlen($argDecl)) {
                continue;
            }

            $elems = preg_split('!([\s=]+)!', $argDecl);
            $isRequired = 2 == sizeof($elems, COUNT_NORMAL);

            if(sizeof($elems, COUNT_NORMAL) == 1) {
                list($name, $type) = array_pad($elems, 2, null);
            } else {
                if('$' == $elems[0][0]) {
                    $name = $elems[0];
                    $type  = null;
                    $isRequired = false;
                } else {
                    list($type, $name) = array_pad($elems, 2, null);
                }
            }

            $argument = new ReflectedArgument($name, $type, $isRequired);
            $method->pushArgument($argument);
        }

        //
        // Body
        $this->extractContent($method, $n, $tokens);

        // Tokens
        $end = $this->searcher->getPositionOfClosingBrace($n, $tokens);
        if($end > 0) {
            $method->setTokens($tokens->extract($n, $end));
        }

        //
        // Dependencies
        $this->extractDependencies($method, $n, $tokens);

        // returns
        $this->extractReturns($method, $method->getContent());

        // usage
        $this->extractUsage($method);

        return $method;
    }

    /**
     * Extracts content of method
     *
     * @param ReflectedMethod $method
     * @param integer $n
     * @param TokenCollection $tokens
     * @return $this
     */
    private function extractContent(ReflectedMethod $method, $n, TokenCollection $tokens) {
        $end = $this->searcher->getPositionOfClosingBrace($n, $tokens);
        if($end > 0) {
            $collection = $tokens->extract($n, $end);
            $method->setContent($collection->asString());
        }
        return $this;
    }

    /**
     * Extracts content of method
     *
     * @param ReflectedMethod $method
     * @param integer $n
     * @param TokenCollection $tokens
     * @return $this
     */
    private function extractDependencies(ReflectedMethod $method, $n, TokenCollection $tokens) {

        //
        // Object creation
        $extractor = new CallExtractor($this->searcher);
        $start = $n;
        $len = sizeof($tokens, COUNT_NORMAL);
        for($i = $start; $i < $len; $i++) {
            $token = $tokens[$i];
            switch($token->getType()) {
                case T_PAAMAYIM_NEKUDOTAYIM:
                case T_NEW:
                    $call = $extractor->extract($i, $tokens);
                    $method->pushDependency($call);
                    break;
            }
        }

        //
        // Parameters in Method API
        foreach($method->getArguments() as $argument) {
            $name = $argument->getType();
            if(!in_array($argument->getType(), array(null, 'array'))) {
                $method->pushDependency($name);
            }
        }

        return $this;
    }

    /**
     * Extract the list of returned values
     *
     * @param ReflectedMethod $method
     * @param string $content
     * @return $this
     */
    private function extractReturns(ReflectedMethod $method, $content) {
        if(preg_match_all('!([\s;]return\s|^return\s)!', $content, $matches)) {
            foreach($matches[1] as $m) {
                $method->pushReturn($m);
            }
        }
        return $this;
    }

    /**
     * Extracts usage of method
     *
     * @param ReflectedMethod $method
     * @return $this
     */
    private function extractUsage(ReflectedMethod $method) {
        $tokens = $method->getTokens();
        $codes = $values = array();
        foreach($tokens as $token) {
            if(in_array($token->getType(), array(T_WHITESPACE, T_BOOL_CAST, T_INT_CAST, T_STRING_CAST, T_DOUBLE_CAST, T_OBJECT_CAST))) {
                continue;
            }
            array_push($codes, $token->getType());
            array_push($values, $token->getValue());
        }
        switch(true) {
            case preg_match('!^(get)|(is)|(has).*!',$method->getName()) && $codes == array(T_RETURN, T_VARIABLE, T_OBJECT_OPERATOR, T_STRING, T_STRING):
                $method->setUsage(MethodUsage::USAGE_GETTER);
                break;
            // basic setter
            case preg_match('!^set.*!',$method->getName()) && $codes == array(T_VARIABLE, T_OBJECT_OPERATOR,T_STRING,T_STRING, T_VARIABLE, T_STRING) && $values[3] == '=':
            // fluent setter
            case preg_match('!^set.*!',$method->getName()) && $codes == array(T_VARIABLE, T_OBJECT_OPERATOR,T_STRING,T_STRING, T_VARIABLE, T_STRING, T_RETURN, T_VARIABLE, T_STRING)
                && $values[3] == '=' && $values[7] == '$this':
                $method->setUsage(MethodUsage::USAGE_SETTER);
                break;
            default:
                $method->setUsage(MethodUsage::USAGE_UNKNWON);
        }
        return $this;
    }
}
