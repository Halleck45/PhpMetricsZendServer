<?php
namespace Test\Hal\Component\OOP;

use Hal\Metrics\Design\Component\MaintainabilityIndex\MaintainabilityIndex;
use Hal\Metrics\Design\Component\MaintainabilityIndex\Result;
use Hal\Component\OOP\Extractor\Extractor;

/**
 * @group oop
 */
class OOPExtractorTest extends \PHPUnit_Framework_TestCase {


    /**
     * @dataProvider providesForClassnames
     */
    public function testClassnameIsFound($filename, $expected) {

        $extractor = new Extractor(new \Hal\Component\Token\Tokenizer());
        $result = $extractor->extract($filename);

        $this->assertCount(sizeof($expected), $result->getClasses());
        foreach($result->getClasses() as $index => $class) {
            $this->assertEquals($expected[$index], $class->getFullname());
        }

    }

    public function providesForClassnames() {
        return array(
            array(__DIR__.'/../../../resources/oop/f1.php', array('\Titi'))
            , array(__DIR__.'/../../../resources/oop/f2.php', array('\My\Example\Titi'))
            , array(__DIR__.'/../../../resources/oop/f3.php', array('\My\Example\Titi1', '\My\Example\Titi2'))
        );
    }

    /**
     * @dataProvider providesForDependenciesWithoutAlias
     */
    public function testDependenciesAreGivenWithoutAlias($file, $expected) {

        $result = new \Hal\Component\OOP\Extractor\Result();
        $extractor = new Extractor(new \Hal\Component\Token\Tokenizer());
        $result = $extractor->extract($file);

        $classes = $result->getClasses();
        $this->assertCount(1, $classes, 'all classes are found');

        $class = $classes[0];
        $dependencies = $class->getDependencies();

        $this->assertEquals($expected, $dependencies, 'Dependencies are given without alias');
    }

    public function providesForDependenciesWithoutAlias() {
        return array(
            array(__DIR__.'/../../../resources/oop/f7.php', array('Symfony\Component\Config\Definition\Processor'))
            , array(__DIR__.'/../../../resources/oop/f4.php', array('\Full\AliasedClass', '\My\Example\Toto'))
        );
    }

    public function testCallsAreFoundAsDependencies() {
        $file = __DIR__.'/../../../resources/oop/f5.php';
        $extractor = new Extractor(new \Hal\Component\Token\Tokenizer());
        $result = $extractor->extract($file);
        $classes = $result->getClasses();
        $this->assertCount(1, $classes, 'all classes are found');
        $class = $classes[0];
        $dependencies = $class->getDependencies();

        $expected = array('\Example\IAmCalled', '\My\Example\IAmCalled');

        $this->assertEquals($expected, $dependencies, 'Direct dependencies (calls) are found');

    }

    public function testClassesThatDoesNotExtendOtherClassesShouldNotHaveAParentClass()
    {
        $file = __DIR__.'/../../../resources/oop/f1.php';
        $extractor = new Extractor(new \Hal\Component\Token\Tokenizer());
        $result = $extractor->extract($file);
        $this->assertCount(1, $result->getClasses());

        $class = current($result->getClasses());
        $this->assertNull($class->getParent());
    }

}