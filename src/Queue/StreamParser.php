<?php

namespace Kalmyk\Queue;

class StreamParser
{
    private $inBuffer = '';
    private $dataToRead = 0;
    private $linesToRead = 0;
    private $msgFound = array();

    public function testBuffer()
    {
        return $this->inBuffer;
    }

    public function parse($buffer, $decodeData)
    {
        $this->inBuffer .= $buffer;
        $result = array();

        while (true)
        {
            if ($this->linesToRead == 0 and $this->dataToRead > 0)
            {
                if ($this->dataToRead+2 > strlen($this->inBuffer))
                    return $result;

                $line = substr($this->inBuffer, 0, $this->dataToRead);
                if ($decodeData)
                    $this->msgFound[] = json_decode($line, true);
                else
                    $this->msgFound[] = $line;

                $this->inBuffer = substr($this->inBuffer, $this->dataToRead+2);
                $this->dataToRead = 0;

                $result[] = $this->msgFound;
                $this->msgFound = array();
            }
            else
            {
                $lineEnd = strpos($this->inBuffer, "\r\n");
                if (false === $lineEnd)
                    return $result;

                $line = substr($this->inBuffer, 0, $lineEnd);
                $this->inBuffer = substr($this->inBuffer, $lineEnd+2);

                if ($this->linesToRead > 0)
                {
                    $this->linesToRead--;
                    $this->msgFound[] = json_decode($line, true);
                    if ($this->linesToRead == 0 && $this->dataToRead == 0)
                    {
                        $result[] = $this->msgFound;
                        $this->msgFound = array();
                    }
                }
                else
                {
                    if (strlen($line) > 1 && $line[0] == 'l')
                        $this->linesToRead = (int)$line[1];
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

    public function serialize($header, $data, $doEncodeData)
    {
        $result = 'l1';  // .count(array($header));
        if ($data)
        {
            if ($doEncodeData)
                $data = json_encode($data);

            $result .= 'd'.strlen($data);
        }

        $result .= "\r\n".json_encode($header)."\r\n";
        if ($data)
        {
            $result .= "$data\r\n";
        }

        return $result;
    }
}

