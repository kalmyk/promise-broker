<?php

namespace Kalmyk\Queue;

class StreamParser
{
    private $inBuffer = '';
    private $dataToRead = 0;
    private $lineToRead = 0;
    private $lineFound = array();
    
    public function testBuffer()
    {
        return $this->inBuffer;
    }
    
    public function parse($data)
    {
//echo ">->->$data<-<-<\n";
        $this->inBuffer .= $data;
        $result = array();
        
        while (true)
        {
            if ($this->lineToRead == 0 and $this->dataToRead > 0)
            {
                if ($this->dataToRead+2 > strlen($this->inBuffer))
                    return $result;

                $this->lineFound[] = substr($this->inBuffer, 0, $this->dataToRead);
                $this->inBuffer = substr($this->inBuffer, $this->dataToRead+2);

                $this->dataToRead = 0;

                $result[] = $this->lineFound;
                $this->lineFound = array();
            }
            else
            {
                $lineEnd = strpos($this->inBuffer, "\r\n");
                if (false === $lineEnd)
                    return $result;
                
                $line = substr($this->inBuffer, 0, $lineEnd);
                $this->inBuffer = substr($this->inBuffer, $lineEnd+2);
                
                if ($this->lineToRead > 0)
                {
                    $this->lineToRead--;
                    $this->lineFound[] = $line;
                    if ($this->lineToRead == 0 && $this->dataToRead == 0)
                    {
                        $result[] = $this->lineFound;
                        $this->lineFound = array();
                    }
                }
                else
                {
                    if (strlen($line) > 1 && $line[0] == 'l')
                        $this->lineToRead = (int)$line[1];
                    else
                        throw new \Exception('protocol parse error');

                    if (strlen($line) > 2)
                    {
                        if ($line[2] == 'd')
                            $this->dataToRead = (int)substr($line, 3);
                        else
                            throw new \Exception('protocol parse error');
                    }
                }
            }
        }
    }

    public function serialize($message, $data)
    {
        $result = 'l'.count($message);
        if (strlen($data) > 0)
        {
            $result .= 'd'.strlen($data);
        }
        $result .= "\r\n";
        foreach($message as $line)
        {
            $result .= "$line\r\n";
        }
        if (strlen($data) > 0)
        {
            $result .= $data."\r\n";
        }
//echo ">>>$result<<<\n";
        return $result;
    }
}
