<?php
/**
 * @author Michael Kliewe
 * @copyright 2011 Michael Kliewe
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link http://www.phpgangsta.de/
 */
class ImageLabeler
{
    protected $_options = array(
        'text'              => '',
        'fontSize'          => '3',
        'fontColor'         => 'e50000',
        'backgroundColor'   => 'ffffff',
        'format'            => 'png',
        'filePath'          => '',
        'fileContent'       => '',
        'targetFileQuality' => 75, // 1-100, 100 is best (no compression)
        'labelOffsetX'      => 5,
        'labelOffsetY'      => 5,
    );

    protected $_supportedFormats = array('png', 'gif', 'jpg');
    protected $_tempFilePath;
    protected $_sourceFormat;
    protected $_image;

    public function __construct(array $options = array())
    {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }

    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $normalized = ucfirst($key);

            $method = 'set' . $normalized;
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }

        return $this;
    }

    public function render()
    {
        $this->_createTempFilePath();

        $this->_readSourceImage();

        $this->_labelImage();

        $this->_writeImageToTargetFile();

        return $this;
    }

    public function outputRenderedImage()
    {
        header('Content-Length: '.filesize($this->_tempFilePath));
        header('Content-Type: image/'.$this->_options['format']);

        readfile($this->_tempFilePath);

        return $this;
    }

    public function getRenderedFileContent()
    {
        return file_get_contents($this->_tempFilePath);
    }

    public function getRenderedFilePath()
    {
        return $this->_tempFilePath;
    }

    public function setFilePath($filePath)
    {
        $this->_options['filePath'] = $filePath;
        return $this;
    }

    public function setFileContent($fileContent)
    {
        $this->_options['fileContent'] = $fileContent;
        return $this;
    }

    public function setText($text)
    {
        $this->_options['text'] = $text;
        return $this;
    }

    public function setFontSize($fontSize)
    {
        $this->_options['fontSize'] = $fontSize;
        return $this;
    }

    public function setFormat($format)
    {
        if (!in_array($format, $this->_supportedFormats)) {
            throw new Exception('Format not supported.');
        }

        $this->_options['format'] = $format;
        return $this;
    }

    public function setFontColor($fontColor)
    {
        $this->_options['fontColor'] = $fontColor;
        return $this;
    }

    public function setBackgroundColor($backgroundColor)
    {
        $this->_options['backgroundColor'] = $backgroundColor;
        return $this;
    }

    public function setLabelOffsetX($labelOffsetX)
    {
        $this->_options['labelOffsetX'] = $labelOffsetX;
        return $this;
    }

    public function setLabelOffsetY($labelOffsetY)
    {
        $this->_options['labelOffsetY'] = $labelOffsetY;
        return $this;
    }

    public function setTargetFileQuality($percent)
    {
        $this->_options['targetFileQuality'] = $percent;
        return $this;
    }


    // ====== private and protected methods =============

    protected function _createTempFilePath()
    {
        $tempFilePath = tempnam('', '') . '.' . $this->_options['format'];

        $this->_tempFilePath = $tempFilePath;
    }

    protected function _readSourceImage()
    {
        if (!empty($this->_options['fileContent'])) {
            $this->_image = imagecreatefromstring($this->_options['fileContent']);
        } elseif (!empty($this->_options['filePath'])) {
            $this->_image = imagecreatefromstring(file_get_contents($this->_options['filePath']));
        } else {
            throw new Exception('You have to provide a source image.');
        }
    }

    protected function _labelImage()
    {
        // calc label size
		$labelWidth = strlen($this->_options['text']) * imagefontwidth($this->_options['fontSize']);
		$labelHeight = imagefontheight($this->_options['fontSize']);

		// calc label position
		$labelX = imagesx($this->_image) - $labelWidth - $this->_options['labelOffsetX'];
		$labelY = imagesy($this->_image) - $labelHeight - $this->_options['labelOffsetY'];

        // convert colors to image color values
        $fontColor = imagecolorallocate(
            $this->_image,
			hexdec(substr($this->_options['fontColor'], 0, 2)),
			hexdec(substr($this->_options['fontColor'], 2, 2)),
			hexdec(substr($this->_options['fontColor'], 4, 2))
        );
		$backgroundColor = imagecolorallocate(
            $this->_image,
			hexdec(substr($this->_options['backgroundColor'], 0, 2)),
			hexdec(substr($this->_options['backgroundColor'], 2, 2)),
			hexdec(substr($this->_options['backgroundColor'], 4, 2))
        );


        // paint background of the font (border around letters)
		imagestring($this->_image, $this->_options['fontSize'], $labelX + 1, $labelY    , $this->_options['text'], $backgroundColor);
		imagestring($this->_image, $this->_options['fontSize'], $labelX + 1, $labelY + 1, $this->_options['text'], $backgroundColor);
		imagestring($this->_image, $this->_options['fontSize'], $labelX    , $labelY + 1, $this->_options['text'], $backgroundColor);
		imagestring($this->_image, $this->_options['fontSize'], $labelX - 1, $labelY + 1, $this->_options['text'], $backgroundColor);
		imagestring($this->_image, $this->_options['fontSize'], $labelX - 1, $labelY    , $this->_options['text'], $backgroundColor);
		imagestring($this->_image, $this->_options['fontSize'], $labelX - 1, $labelY - 1, $this->_options['text'], $backgroundColor);
		imagestring($this->_image, $this->_options['fontSize'], $labelX    , $labelY - 1, $this->_options['text'], $backgroundColor);

		// paint the text
		imagestring($this->_image, $this->_options['fontSize'], $labelX, $labelY, $this->_options['text'], $fontColor);
    }

    protected function _writeImageToTargetFile()
    {
        switch ($this->_options['format'])
        {
            case 'png':
                $pngQuality = ($this->_options['targetFileQuality'] - 100) / 11.111111;
                $pngQuality = round(abs($pngQuality));
                imagepng($this->_image, $this->_tempFilePath, $pngQuality);
                break;
            case 'jpg':
                imagejpeg($this->_image, $this->_tempFilePath, $this->_options['targetFileQuality']);
                break;
            case 'gif':
                imagegif($this->_image, $this->_tempFilePath);
                break;
            default:
                throw new Exception('Invalid target format');
        }

        imagedestroy($this->_image);
    }
}