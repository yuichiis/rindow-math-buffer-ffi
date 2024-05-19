<?php
namespace Rindow\Math\Buffer\FFI;

use FFI;

class BufferFactory
{
    public function isAvailable() : bool
    {
        return class_exists(FFI::class);
    }

    public function Buffer(int $size, int $dtype) : Buffer
    {
        //if(PHP_OS=='Darwin') {
        //    return new BufferMacOS($size, $dtype);
        //} else {
            return new Buffer($size, $dtype);
        //}
    }
}