<?php

namespace Folklore\Mediatheque\Http\Controllers;

use Folklore\Mediatheque\Contracts\Models\Document as DocumentContract;

class DocumentController extends ResourceController
{
    protected function getModel()
    {
        return app(DocumentContract::class);
    }
}
