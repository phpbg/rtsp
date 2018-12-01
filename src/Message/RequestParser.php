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

use Evenement\EventEmitter;
use PhpBg\Rtsp\Message\Enum\RequestMethod;
use PhpBg\Rtsp\Message\Enum\RtspVersion;

/**
 * Parse RTSP request
 *
 * @event request
 *    Emitted when a request is available.
 *    Params : [Request $request]
 * @event error
 *    Emitted when an error occur while parsing
 *    Params : [\Exception $e]
 */
class RequestParser extends EventEmitter
{
    /**
     * @var string
     */
    protected $buffer;

    /**
     * @var Request
     */
    protected $request;

    public function __construct()
    {
        $this->reset();
    }

    /**
     * Handle incoming data from connection
     * @param string $data
     */
    public function feed(string $data)
    {
        try {
            if ($this->request === null) {
                $this->feedHeaders($data);
            } else {
                $this->feedBody($data);
            }
        } catch (MessageException $e) {
            $this->emit('error', [$e]);
        }
    }

    protected function reset()
    {
        $this->buffer = '';
        $this->request = null;
    }

    /**
     * @param string $data
     * @throws MessageException
     */
    protected function feedHeaders(string $data)
    {
        $this->buffer .= $data;
        $endOfHeaders = strpos($this->buffer, "\r\n\r\n");
        if ($endOfHeaders !== false) {
            $headersString = substr($this->buffer, 0, $endOfHeaders);
            $this->buffer = substr($this->buffer, $endOfHeaders + 4);
            if ($this->buffer === false) {
                $this->buffer = '';
            }
            $this->request = $this->getRequestFromHeaders($headersString);
            $this->feedBody();
        }
    }

    /**
     * @param string $headersString
     * @return Request
     * @throws MessageException
     */
    protected function getRequestFromHeaders(string $headersString): Request
    {
        $messageExploded = explode("\r\n", $headersString);
        if (empty($messageExploded)) {
            throw new MessageException('Dropping empty/invalid message');
        }

        $messageExploded = array_map('trim', $messageExploded);
        $firstLine = array_shift($messageExploded);
        $firstLineExploded = explode(' ', $firstLine);
        if (count($firstLineExploded) !== 3) {
            throw new MessageException('Dropping invalid first line');
        }
        $method = $firstLineExploded[0];
        $version = $firstLineExploded[2];
        if (!RequestMethod::isValid($method)) {
            throw new MessageException('Dropping invalid method');
        }
        if (!RtspVersion::isValid($version)) {
            throw new MessageException('Dropping unsupported RTSP version');
        }

        $request = new Request();
        $request->method = $method;
        $request->uri = $firstLineExploded[1];
        $request->rtspVersion = $version;

        foreach ($messageExploded as $headerLine) {
            $headerLine = trim($headerLine);
            if (empty($headerLine)) {
                // End of headers
                break;
            }
            $pos = strpos($headerLine, ':');
            if ($pos === false || $pos === 0) {
                throw new MessageException('Dropping invalid message header line');
            }

            //Keys are case insensitive
            $key = strtolower(trim(substr($headerLine, 0, $pos)));
            $value = trim(substr($headerLine, $pos + 1));
            $request->headers[$key] = $value;
        }

        if (!$request->hasHeader('cseq')) {
            throw new MessageException('Dropping message missing cseq');
        }
        return $request;
    }

    protected function feedBody(string $data = null)
    {
        if ($data !== null) {
            $this->buffer .= $data;
        }
        if (!$this->request->hasHeader('Content-Length')) {
            $this->emit('request', [$this->request]);
            $remainingData = $this->buffer;
            $this->reset();
            if (!empty($remainingData)) {
                $this->feed($remainingData);
            }
            return;
        }
        $length = (int)$this->request->getHeader('Content-Length');
        // TODO security check : $length should not be too big...
        if (strlen($this->buffer) < $length) {
            //Wait for buffer to fill
            return;
        }
        if ($length > 0) {
            $body = substr($this->buffer, 0, $length);
            $this->request->body = $body;
        }
        $this->emit('request', [$this->request]);
        $remainingData = substr($this->buffer, $length);
        $this->reset();
        if (!empty($remainingData)) {
            $this->feed($remainingData);
        }
        return;
    }
}