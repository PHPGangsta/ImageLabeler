<?php

require_once 'ImageLabeler.php';

$imageLabeler = new ImageLabeler();
$imageLabeler->setFileContent('SourceCodeOfTheImageHere')
             ->setText('PHPGangsta')
             ->setPosition(ImageLabeler::POSITION_BOTTOM_RIGHT)
             ->setFontSize(3)
             ->setLabelOffsetX(10)
             ->setLabelOffsetY(10)
             ->setFormat('gif')
             ->setFontColor('00ff00')
             ->setBackgroundColor('000000')
             ->setTargetFileQuality(80)
             ->setBoxBorderThickness(2)
             ->setBoxBorderColor('ff0000')
             ->render()
             ->outputRenderedImage();