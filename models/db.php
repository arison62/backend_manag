<?php

class MyDB extends SQLite3{
    function __construct(string $path)
    {
        parent::__construct($path);
    }
}