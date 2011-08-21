<?php

require_once 'ImageLabeler.php';

$imageLabeler = new ImageLabeler();
$imageLabeler->setFileContent('SourceCodeOfTheImageHere')
             ->setText('PHPGangsta')
             ->setFontSize(3)
             ->setFormat('gif')
             ->setFontColor('00ff00')
             ->setBackgroundColor('000000')
             ->setTargetFileQuality(80)
             ->render()
             ->outputRenderedImage();