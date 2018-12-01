<?php

/**
 * MIT License
 *
 * Copyright (c) 2018 Samuel CHEMLA
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace PhpBg\Rtsp\Message;

abstract class AbstractMessage
{
    public $headers = [];
    public $body = null;

    abstract public function getFirstLine(): string;

    /**
     * Return true is header $name is set (case insensitive)
     * @param string $name
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    /**
     * Return header $name (case insensitive)
     * @param string $name
     * @return mixed|null Null is returned if header is not set
     */
    public function getHeader(string $name)
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function setHeader(string $name, $value)
    {
        $this->headers[strtolower($name)] = $value;
    }

    public function __toString()
    {
        $str = "{$this->getFirstLine()}\r\n";
        foreach ($this->headers as $key => $value) {
            $str .= "\t\t{$key}: {$value}\r\n";
        }
        $str .= "\r\n";
        if ($this->body !== null) {
            $str .= $this->body;
            $str .= "\r\n";
        }

        return $str;
    }

    /**
     * Generate string message representation to be used on connection transport
     * @return string
     */
    public function toTransport(): string
    {
        $str = "{$this->getFirstLine()}\r\n";
        foreach ($this->headers as $key => $value) {
            $str .= "{$key}: {$value}\r\n";
        }
        $str .= "\r\n";
        if ($this->body !== null) {
            $str .= $this->body;
        }
        return $str;
    }
}