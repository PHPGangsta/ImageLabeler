<?php

require_once 'ImageLabeler.php';

$options = array(
    'filePath'           => '/path/to/image.jpg',
    'text'               => 'PHPGangsta',
    'position'           => ImageLabeler::POSITION_BOTTOM_RIGHT,
    'fontSize'           => 3,
    'labelOffsetX'       => 10,
    'labelOffsetY'       => 10,
    'format'             => 'jpg',
    'fontColor'          => '00ff00',
    'backgroundColor'    => '000000',
    'targetFileQuality'  => 80,
    'boxBorderThickness' => 2,
    'boxBorderColor'     => 'ff0000',
);

$imageLabeler = new ImageLabeler($options);
$imageLabeler->render();

$imageSource = $imageLabeler->getRenderedFileContent();