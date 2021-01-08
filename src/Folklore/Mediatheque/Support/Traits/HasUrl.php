<?php
namespace Folklore\Mediatheque\Support\Traits;

use Folklore\Mediatheque\Contracts\Support\HasFiles as HasFilesInterface;

trait HasUrl
{
    public function getUrl(): string
    {
        if ($this instanceof HasFilesInterface) {
            $originalFile = $this->getOriginalFile();
            return $originalFile ? $originalFile->getUrl() : null;
        }
        $source = $this->getSource();
        return $source->getUrl($this->path);
    }
}
