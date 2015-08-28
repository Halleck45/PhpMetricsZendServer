<?php
namespace PhpMetrics;

use Hal\Component\Token\Tokenizer;
use Hal\Component\Token\TokenType;
use Hal\Metrics\Complexity\Component\McCabe\McCabe;
use Hal\Metrics\Complexity\Text\Halstead\Halstead;
use Hal\Metrics\Complexity\Text\Length\Loc;
use Hal\Metrics\Design\Component\MaintainabilityIndex\MaintainabilityIndex;

class Collector {

    public function collect($context, &$storage) {

        // filter loaded files
        $files = get_included_files();
        $files = array_filter($files, function ($v) {
            $excludes = array('zray',  'vendor', 'pear', '/usr/local/zend/var/plugins', '\\.phar', 'bootstrap\.php', 'Test', '/app', 'AppKernel.php', 'autoload.php', 'cache/', 'app.php', 'app_dev.php', 'Form', 'PhpMetrics', 'classes.php');
            return !preg_match('!' . implode('|', $excludes) . '!', $v);
        });

        $scoreByFile = array();
        foreach ($files as $file) {
            // run PhpMetrics
            $tokenizer = new Tokenizer();
            $tokenType = new TokenType();

            // halstead
            $halstead = new Halstead($tokenizer, $tokenType);
            $rHalstead = $halstead->calculate($file);

            // loc
            $loc = new Loc($tokenizer);
            $rLoc = $loc->calculate($file);

            // complexity
            $complexity = new McCabe($tokenizer);
            $rComplexity = $complexity->calculate($file);

            // maintainability
            $maintainability = new MaintainabilityIndex();
            $rMaintenability = $maintainability->calculate($rHalstead, $rLoc, $rComplexity);

            // store result

            $row['maintainability'] = $rMaintenability->getMaintainabilityIndex();
            $row['commentWeight'] = $rMaintenability->getCommentWeight();
            $row['complexity'] = $rComplexity->getCyclomaticComplexityNumber();
            $row['loc'] = $rLoc->getLoc();
            $row['lloc'] = $rLoc->getLogicalLoc();
            $row['cloc'] = $rLoc->getCommentLoc();
            $row['bugs'] = $rHalstead->getBugs();
            $row['difficulty'] = $rHalstead->getDifficulty();
            $row['intelligentContent'] = $rHalstead->getIntelligentContent();
            $row['vocabulary'] = $rHalstead->getVocabulary();
            $row['filename'] = $file;
            $scoreByFile[] = $row;

        }

        $storage['files'] = $scoreByFile;
    }
}