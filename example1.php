<?php

require_once 'ImageLabeler.php';

$imageLabeler = new ImageLabeler();
$imageLabeler->setFilePath('/path/to/image.jpg')
             ->setText('PHPGangsta')
             ->render();
echo $imageLabeler->getRenderedFilePath();
// outputs something like /tmp/63D6.tmp.png