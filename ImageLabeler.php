<?php
/**
 * @author Michael Kliewe
 * @copyright 2011 Michael Kliewe
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link http://www.phpgangsta.de/
 */
class ImageLabeler
{
    /**
     * @var array
     */
    protected $_options = array(
        'text'               => '',
        'position'           => self::POSITION_BOTTOM_RIGHT,
        'positionX'          => null,
        'positionY'          => null,
        'fontSize'           => '3',
        'fontColor'          => 'e50000',
        'backgroundColor'    => 'ffffff',
        'format'             => 'png',
        'filePath'           => '',
        'fileContent'        => '',
        'targetFileQuality'  => 75, // 1-100, 100 is best (no compression)
        'labelOffsetX'       => 5,
        'labelOffsetY'       => 5,
        'boxPadding'         => 2,
        'boxBorderThickness' => 0,
        'boxBorderColor'     => 'ffffff',
        'boxBackgroundColor' => 'ffffff'
    );

    /**
     * Some constants for positioning the string
     */
    const POSITION_BOTTOM_RIGHT  = 0;
    const POSITION_BOTTOM_LEFT   = 1;
    const POSITION_BOTTOM_CENTER = 2;
    const POSITION_TOP_RIGHT     = 3;
    const POSITION_TOP_LEFT      = 4;
    const POSITION_TOP_CENTER    = 5;
    const POSITION_CENTER        = 6;

    /**
     * supported output image formats
     *
     * @var array
     */
    protected $_supportedFormats = array('png', 'gif', 'jpg');
    /**
     * @var string
     */
    protected $_tempFilePath;
    /**
     * @var string
     */
    protected $_sourceFormat;
    /**
     * @var resource
     */
    protected $_image;

    /**
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        // first check if GD extension is installed
        if (!extension_loaded('gd') || !function_exists('gd_info')) {
            throw new Exception('The GD library/extension is needed, please install it first');
        }

        if (is_array($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * All given options in the array are given to their setters
     *
     * @param array $options
     * @return ImageLabeler
     */
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

    /**
     * The main method which does all the work: read source file and put text onto the image
     *
     * @return ImageLabeler
     */
    public function render()
    {
        $this->_createTempFilePath();

        $this->_readSourceImage();

        $this->_labelImage();

        $this->_writeImageToTargetFile();

        return $this;
    }

    /**
     * Send header and output the image
     *
     * @return ImageLabeler
     */
    public function outputRenderedImage()
    {
        header('Content-Length: '.filesize($this->_tempFilePath));
        header('Content-Type: image/'.$this->_options['format']);

        readfile($this->_tempFilePath);

        return $this;
    }

    /**
     * Get the image as a string
     *
     * @return string
     */
    public function getRenderedFileContent()
    {
        return file_get_contents($this->_tempFilePath);
    }

    /**
     * Get the path to the file after render() has been called
     *
     * @return string
     */
    public function getRenderedFilePath()
    {
        return $this->_tempFilePath;
    }

    /**
     * Set the path to the source image
     *
     * @param string $filePath
     * @return ImageLabeler
     */
    public function setFilePath($filePath)
    {
        $this->_options['filePath'] = $filePath;
        return $this;
    }

    /**
     * Set the source image string
     *
     * @param string $fileContent
     * @return ImageLabeler
     */
    public function setFileContent($fileContent)
    {
        $this->_options['fileContent'] = $fileContent;
        return $this;
    }

    /**
     * Set the text that will be displayed
     *
     * @param string $text
     * @return ImageLabeler
     */
    public function setText($text)
    {
        $this->_options['text'] = $text;
        return $this;
    }

    /**
     * Set font size. This is a number between 1 und 5 (largest)
     *
     * @param string $fontSize
     * @return ImageLabeler
     */
    public function setFontSize($fontSize)
    {
        $this->_options['fontSize'] = $fontSize;
        return $this;
    }

    /**
     * Set the output format, see $this->_supportedFormats
     * @param string $format
     * @return ImageLabeler
     */
    public function setFormat($format)
    {
        if (!in_array($format, $this->_supportedFormats)) {
            throw new Exception('Format not supported.');
        }

        $this->_options['format'] = $format;
        return $this;
    }

    /**
     * Set the front color of the text as RGB values (e.g. 0000ff for blue)
     *
     * @param string $fontColor
     * @return ImageLabeler
     */
    public function setFontColor($fontColor)
    {
        $this->_options['fontColor'] = $fontColor;
        return $this;
    }

    /**
     * Set the background color of the text as RGB values (e.g. 0000ff for blue)
     *
     * @param string $backgroundColor
     * @return ImageLabeler
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->_options['backgroundColor'] = $backgroundColor;
        return $this;
    }

    /**
     * Set the X distance between text and the border of the image
     *
     * @param int $labelOffsetX
     * @return ImageLabeler
     */
    public function setLabelOffsetX($labelOffsetX)
    {
        $this->_options['labelOffsetX'] = $labelOffsetX;
        return $this;
    }

    /**
     * Sets the Y distance between text and the border of the image
     *
     * @param int $labelOffsetY
     * @return ImageLabeler
     */
    public function setLabelOffsetY($labelOffsetY)
    {
        $this->_options['labelOffsetY'] = $labelOffsetY;
        return $this;
    }

    /**
     * Set the quality/compression rate of the output image. 1 is worst quality, 100 highest
     *
     * @param string $percent
     * @return ImageLabeler
     */
    public function setTargetFileQuality($percent)
    {
        $this->_options['targetFileQuality'] = $percent;
        return $this;
    }

    /**
     * Sets the position of the text, see constants POSITION_*
     *
     * @param int $position
     * @return ImageLabeler
     */
    public function setPosition($position)
    {
        $this->_options['position'] = $position;
        return $this;
    }

    /**
     * Set exact X/Y coordinates of the text
     *
     * @param int $positionX
     * @param int $positionY
     * @return ImageLabeler
     */
    public function setPositionXY($positionX, $positionY)
    {
        $this->_options['positionX'] = $positionX;
        $this->_options['positionY'] = $positionY;
        return $this;
    }

    /**
     * Set X position separatly
     *
     * @param int $positionX
     * @return ImageLabeler
     */
    public function setPositionX($positionX)
    {
        $this->_options['positionX'] = $positionX;
        return $this;
    }

    /**
     * Set Y position separatly
     *
     * @param int $positionY
     * @return ImageLabeler
     */
    public function setPositionY($positionY)
    {
        $this->_options['positionY'] = $positionY;
        return $this;
    }

    /**
     * Set the padding of the box around the text
     *
     * @param int $pixel
     * @return ImageLabeler
     */
    public function setBoxPadding($pixel)
    {
        $this->_options['boxPadding'] = $pixel;
        return $this;
    }

    /**
     * Set the border thickness. 0 disables the whole box functionality
     *
     * @param int $pixel
     * @return ImageLabeler
     */
    public function setBoxBorderThickness($pixel)
    {
        $this->_options['boxBorderThickness'] = $pixel;
        return $this;
    }

    /**
     * Set the color of the box around the text
     *
     * @param string $boxBorderColor
     * @return ImageLabeler
     */
    public function setBoxBorderColor($boxBorderColor)
    {
        $this->_options['boxBorderColor'] = $boxBorderColor;
        return $this;
    }

    /**
     * Set the background color of the box behind the text
     *
     * @param string $boxBackgroundColor
     * @return ImageLabeler
     */
    public function setBoxBackgroundColor($boxBackgroundColor)
    {
        $this->_options['boxBackgroundColor'] = $boxBackgroundColor;
        return $this;
    }


    // ====== private and protected methods =============

    /**
     * Get a random file path in the system temp path
     */
    protected function _createTempFilePath()
    {
        $tempFilePath = tempnam('', '') . '.' . $this->_options['format'];

        $this->_tempFilePath = $tempFilePath;
    }

    /**
     * Read the source image
     */
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

    /**
     * Adjust font size if needed, calculate X/Y coordinates if needed and put the text to the image
     */
    protected function _labelImage()
    {
        $this->_adjustFontSizeIfNeeded();

        list($labelX, $labelY) = $this->_getLabelXY();

        $this->_createBoxIfNeeded();

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

        // paint background of the text (shadow around the text)
		imagestring($this->_image, $this->_options['fontSize'], $labelX + 1, $labelY    , $this->_options['text'], $backgroundColor);
		imagestring($this->_image, $this->_options['fontSize'], $labelX + 1, $labelY + 1, $this->_options['text'], $backgroundColor);
		imagestring($this->_image, $this->_options['fontSize'], $labelX    , $labelY + 1, $this->_options['text'], $backgroundColor);
		imagestring($this->_image, $this->_options['fontSize'], $labelX - 1, $labelY + 1, $this->_options['text'], $backgroundColor);
		imagestring($this->_image, $this->_options['fontSize'], $labelX - 1, $labelY    , $this->_options['text'], $backgroundColor);
		imagestring($this->_image, $this->_options['fontSize'], $labelX - 1, $labelY - 1, $this->_options['text'], $backgroundColor);
		imagestring($this->_image, $this->_options['fontSize'], $labelX    , $labelY - 1, $this->_options['text'], $backgroundColor);

		// paint the text itself
		imagestring($this->_image, $this->_options['fontSize'], $labelX, $labelY, $this->_options['text'], $fontColor);
    }

    /**
     * If the text does not fit into the image with the preferred size, we try a smaller size
     */
    protected function _adjustFontSizeIfNeeded()
    {
        list($labelWidth,) = $this->_getLabelWidthAndHeight();

        while ($this->_options['fontSize'] > 1 && $labelWidth + $this->_options['labelOffsetX'] > imagesx($this->_image)) {
            $this->_options['fontSize']--;
            list($labelWidth,) = $this->_getLabelWidthAndHeight();
        }
    }

    /**
     * Calculate the X/Y coordinates of the label
     * @return array
     */
    protected function _getLabelXY()
    {
        if ($this->_options['positionX'] !== null && $this->_options['positionY'] !== null) {
            $labelX = $this->_options['positionX'];
            $labelY = $this->_options['positionY'];
        } else {
            // calc label size
            list($labelWidth, $labelHeight) = $this->_getLabelWidthAndHeight();

            // calc label position
            switch ($this->_options['position']) {
                case self::POSITION_BOTTOM_RIGHT:
                    $labelX = imagesx($this->_image) - $labelWidth - $this->_options['labelOffsetX'];
                    $labelY = imagesy($this->_image) - $labelHeight - $this->_options['labelOffsetY'];
                    break;
                case self::POSITION_BOTTOM_LEFT:
                    $labelX = $this->_options['labelOffsetX'];
                    $labelY = imagesy($this->_image) - $labelHeight - $this->_options['labelOffsetY'];
                    break;
                case self::POSITION_TOP_LEFT:
                    $labelX = $this->_options['labelOffsetX'];
                    $labelY = $this->_options['labelOffsetY'];
                    break;
                case self::POSITION_TOP_RIGHT:
                    $labelX = imagesx($this->_image) - $labelWidth - $this->_options['labelOffsetX'];
                    $labelY = $this->_options['labelOffsetY'];
                    break;
                case self::POSITION_CENTER:
                    $labelX = ceil(imagesx($this->_image)/2 - $labelWidth/2);
                    $labelY = ceil(imagesy($this->_image)/2 - $labelHeight/2);
                    break;
                case self::POSITION_TOP_CENTER:
                    $labelX = ceil(imagesx($this->_image)/2 - $labelWidth/2);
                    $labelY = $this->_options['labelOffsetY'];
                    break;
                case self::POSITION_BOTTOM_CENTER:
                    $labelX = ceil(imagesx($this->_image)/2 - $labelWidth/2);
                    $labelY = imagesy($this->_image) - $labelHeight - $this->_options['labelOffsetY'];
                    break;
                default:
                    throw new Exception('Invalid position used. Check constants ImageLabeler::POSITION_*');
            }
        }

        return array($labelX, $labelY);
    }

    /**
     * Get width and height of the label
     *
     * @return array
     */
    protected function _getLabelWidthAndHeight()
    {
        $labelWidth = strlen($this->_options['text']) * imagefontwidth($this->_options['fontSize']);
        $labelHeight = imagefontheight($this->_options['fontSize']);

        return array($labelWidth, $labelHeight);
    }

    /**
     * Paint box behind text if needed
     */
    protected function _createBoxIfNeeded()
    {
        if ($this->_options['boxBorderThickness'] > 0) {
            list($labelX, $labelY) = $this->_getLabelXY();

            $boxBorderColor = imagecolorallocate(
                $this->_image,
                hexdec(substr($this->_options['boxBorderColor'], 0, 2)),
                hexdec(substr($this->_options['boxBorderColor'], 2, 2)),
                hexdec(substr($this->_options['boxBorderColor'], 4, 2))
            );
            $boxBackgroundColor = imagecolorallocate(
                $this->_image,
                hexdec(substr($this->_options['boxBackgroundColor'], 0, 2)),
                hexdec(substr($this->_options['boxBackgroundColor'], 2, 2)),
                hexdec(substr($this->_options['boxBackgroundColor'], 4, 2))
            );

            list($labelWidth, $labelHeight) = $this->_getLabelWidthAndHeight();
            imagesetthickness($this->_image, $this->_options['boxBorderThickness']);
            imagefilledrectangle(
                $this->_image,
                $labelX-$this->_options['boxPadding'],
                $labelY-$this->_options['boxPadding'],
                $labelX+$labelWidth+$this->_options['boxPadding'],
                $labelY+$labelHeight+$this->_options['boxPadding'],
                $boxBackgroundColor
            );
            imagerectangle(
                $this->_image,
                $labelX-$this->_options['boxPadding'],
                $labelY-$this->_options['boxPadding'],
                $labelX+$labelWidth+$this->_options['boxPadding'],
                $labelY+$labelHeight+$this->_options['boxPadding'],
                $boxBorderColor
            );
        }
    }

    /**
     * write the image to a file depending on the output format
     */
    protected function _writeImageToTargetFile()
    {
        switch ($this->_options['format']) {
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