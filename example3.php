<?php

require_once 'ImageLabeler.php';

$options = array(
    'filePath'          => '/path/to/image.jpg',
    'text'              => 'PHPGangsta',
    'position'          => ImageLabeler::POSITION_BOTTOM_RIGHT,
    'fontSize'          => 3,
    'format'            => 'jpg',
    'fontColor'         => '00ff00',
    'backgroundColor'   => '000000',
    'targetFileQuality' => 80,
);

$imageLabeler = new ImageLabeler($options);
$imageLabeler->render();

$imageSource = $imageLabeler->getRenderedFileContent();