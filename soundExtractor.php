<?php
/**
 * MiND
 *
 * @category   Mind
 * @copyright  Copyright (c) 2009 Axel ETCHEVERRY. (http://www.axel-etcheverry.com)
 * @license    http://creativecommons.org/licenses/by/3.0/     Creative Commons 3.0
 */
error_reporting(E_ALL & E_STRICT);

require_once 'library/Swift/Xml.php';
require_once 'library/Swift/Console/Color.php';

class SoundExtractor
{
    protected $_version = '0.1';
    protected $_fileInfo;
    protected $_isVerbose = true;
    protected $_data = array();
    protected $_logPath = '.';
    protected $_isInfoExtracted = false;
    
    /**
     *@param string $file
     *@return SoundExtractor
     */
    public function __construct($file)
    {
        echo 'SoundExtracktor.php version ' . $this->_version;
        echo " By Axel Etcheverry <axel@etcheverry.biz>" . PHP_EOL;
        
        $this->_checking();
        
        if(!file_exists($file)) {
            throw new \InvalidArgumentException($file . " is not file.");
        }
        
        if(!is_readable($file)) {
            throw new \InvalidArgumentException("The file '" . $file . "' is not readable.");
        }
    
        $this->_label('Retrieve information from the video...', PHP_EOL);
        
        $this->_data = pathinfo($file);
        
        exec("mediainfo --Output=XML " . escapeshellarg($file), $xml);
        
        $this->_fileInfo = new \Swift\Xml(implode(PHP_EOL, $xml));
    }
    
    /**
     * Get infos track from video file
     *
     *@return array
     */
    public function getInfos()
    {
        if(!$this->_isInfoExtracted) {
            $this->extractInfos();
        }
        return $this->_data;
    }
    
    /**
     * Extract infos from video file
     *
     *@return void
     */
    public function extractInfos()
    {
        foreach($this->_fileInfo->File->track as $track) {
            
            switch($track->getAttribute('type')) {
                case 'Video':
                    $this->_data['videoId'] = (string)$track->ID;
                    $this->_data['videoFrameRate'] = (string)$track->Frame_rate;
                    break;
                case 'Audio':
                    if($track->hasAttribute('streamid')) {
                        $formats = array();
                        foreach($track as $index => $stream) {
                            $formats[$index] = $stream->Format;
                        }
                        
                        $index = null;
                        
                        if(in_array($this->_priorityFormat, $formats)) {
                            $index = (int)array_search($this->_priorityFormat, $formats);
                        }
                        
                        if(is_int($index)) {
                            $_track = $track[$index];
                        } else {
                            $_track = $track[0];
                        }
                        
                        if(isset($track->Codec_ID_Hint)) {
                            $format = (string)$track->Codec_ID_Hint;
                        } else {
                            $format = (string)$track->Codec_ID;
                        }
                        
                        $this->_data['audioId']         = (string)$_track->ID;
                        $this->_data['audioFormat']     = strtolower(str_replace('A_', '', $format));
                        $this->_data['audioBitRate']    = (string)$track->Bit_rate;
                        
                    } else {
                        
                        if(isset($track->Codec_ID_Hint)) {
                            $format = (string)$track->Codec_ID_Hint;
                        } else {
                            $format = (string)$track->Codec_ID;
                        }
                        
                        $this->_data['audioId']         = (string)$track->ID;
                        $this->_data['audioFormat']     = strtolower(str_replace('A_', '', $format));
                        $this->_data['audioBitRate']    = (string)$track->Bit_rate;
                    }
                    
            }
        }
        
        if(!isset($this->_data['audioId'])) {
            throw new \RuntimeException("The audio track cannot be found.");
        }
        
        if(!isset($this->_data['videoId'])) {
            throw new \RuntimeException("The video track cannot be found.");
        }
        
        $this->_isInfoExtracted = true;
    }
    
    /**
     * Extract sound track from video file
     *
     *@param string|null $outputPath
     *@return void
     */
    public function extract($outputPath = null)
    {
        if(!$this->_isInfoExtracted) {
            $this->extractInfos();
        }
        
        if(empty($outputPath)) {
            $outputPath = $this->_data['dirname'];
        } else {
            $outputPath = implode('/', explode('/', $outputPath));
        }
        
        if($this->_data['extension'] == 'avi') {
            
            $this->_label("Extracting " . $this->_data['audioFormat']);
            $ops = array(
                '-o ' . escapeshellarg($outputPath . '/' . $this->_data['filename'] . '.mp3'),
                '-a 1',
                '-D',
                '-S ' . escapeshellarg($this->_data['dirname'] . '/' . $this->_data['basename']),
                '--track-order 0:1'
            );
            exec('mkvmerge ' . implode(' ', $ops) . ' 1>/dev/null', $out, $status);
            $this->_status($status);
            
        } elseif($this->_data['audioFormat'] == 'ac3') {
            
            $this->_label("Extracting " . $this->_data['audioFormat']);
            $ops = array(
                'tracks ' . escapeshellarg($this->_data['dirname'] . '/' . $this->_data['basename']),
                $this->_data['audioId'] . ':' . escapeshellarg($outputPath . '/' . $this->_data['filename'] . '.' . $this->_data['audioFormat'])
            );
            
            exec('mkvextract ' . implode(' ', $ops) . ' 1>/dev/null', $out, $status);
            $this->_status($status);
            
            $this->_label("Converting");
            $ops = array(
                '-i ' . escapeshellarg($outputPath . '/' . $this->_data['filename'] . '.' . $this->_data['audioFormat']),
                '-f ac3',
                '-acodec ac3',
                '-ab "192000"',
                escapeshellarg($outputPath . '/' . $this->_data['filename'] . '.192.ac3'),
                '2>' . escapeshellarg($this->_logPath . '/' . $outputPath . '/' . $this->_data['filename'] . '.log')
            );
            exec('ffmpeg ' . implode(' ', $ops) . ' 1>/dev/null', $out, $status);
            $this->_status($status);
            
        } elseif($this->_data['audioFormat'] == 'dts') {
            
            $this->_label("Extracting " . $this->_data['audioFormat']);
            $ops = array(
                'tracks ' . escapeshellarg($this->_data['dirname'] . '/' . $this->_data['basename']),
                $this->_data['audioId'] . ':' . escapeshellarg($outputPath . '/' . $this->_data['filename'] . '.' . $this->_data['audioFormat'])
            );
            
            exec('mkvextract ' . implode(' ', $ops) . ' 1>/dev/null', $out, $status);
            $this->_status($status);
            
            $this->_label("Converting");
            $ops = array(
                '-o wavall ' . escapeshellarg($outputPath . '/' . $this->_data['filename'] . '.' . $this->_data['audioFormat']),
                '2>/dev/null 1>/dev/null',
                '|',
                'aften',
                '-b "192"',
                '- ' . escapeshellarg($outputPath . '/' . $this->_data['filename'] . '.ac3'),
                '2>/dev/null'
            );
            exec('dcadec ' . implode(' ', $ops) . ' 1>/dev/null', $out, $status);
            $this->_status($status);
            
        } elseif($this->_data['audioFormat'] == 'aac') {
            
            $this->_label("Extracting " . $this->_data['audioFormat']);
            $ops = array(
                'tracks ' . escapeshellarg($this->_data['dirname'] . '/' . $this->_data['basename']),
                $this->_data['audioId'] . ':' . escapeshellarg($outputPath . '/' . $this->_data['filename'] . '.' . $this->_data['audioFormat'])
            );
            
            exec('mkvextract ' . implode(' ', $ops) . ' 1>/dev/null', $out, $status);
            $this->_status($status);
            
        } else {
            throw new \UnexpectedValueException("Sound format is not supported.");
        }
        
        $this->_label('Done', PHP_EOL);
    }
    
    /**
     * Show steps messages
     *
     *@param string $str
     *@param string|null $eol
     *@return void
     */
    protected function _label($str, $eol = null)
    {
        if($this->_isVerbose) {
            echo $str . $eol;
        }
    }
    
    /**
     * Show steps status
     *
     *@param int $status
     *@return void
     */
    protected function _status($status) 
    {
        if($this->_isVerbose) {
            if($status == "0" || $status == "1") {
                echo "\t". \Swift\Console\Color::colored("[  ok  ]", 'green') . PHP_EOL;
            } else {
                echo "\t". \Swift\Console\Color::colored("[  no  ]", 'red') . PHP_EOL;
            }
        }
    }
    
    /**
     * Check requires commands
     *
     *@return void
     */
    protected function _checking()
    {
        $this->_commandExists('mkvmerge');
        $this->_commandExists('mediainfo');
        $this->_commandExists('aften');
        $this->_commandExists('dcadec');
        $this->_commandExists('ffmpeg');
        $this->_commandExists('mkvextract');
    }
    
    /**
     * Check is command exist
     *
     *@param string $command
     *@return void
     */
    protected function _commandExists($command)
    {
        $r = exec("which " . $command);
        
        if(empty($r)) {
            throw new \RuntimeException("Command '{$command}' does not exists.");
        }
    }
    
    /**
     * Destruct object
     *
     *@return void
     */
    protected function __destruct()
    {
        unset($this->_fileInfo);
    }
}

try {
    $se = new SoundExtractor($argv[1]);
    $se->extract();
    exit(0);
} catch(\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
    exit(2);
}