<?php

namespace abdalqader\crudcommand\Lumen;

use abdalqader\crudcommand\FileRepository;

class LumenFileRepository extends FileRepository
{
    /**
     * {@inheritdoc}
     */
    protected function createModule(...$args)
    {
        return new Module(...$args);
    }
}
