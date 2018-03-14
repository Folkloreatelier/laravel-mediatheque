<?php

namespace Folklore\Mediatheque\Services;

use Folklore\Mediatheque\Contracts\Getter\FamilyName as FamilyNameGetter;

use Illuminate\Support\Facades\Log;
use Exception;

class OtfInfo implements FamilyNameGetter
{
    /**
     * Get family name from a file
     *
     * @param  string  $path
     * @return string
     */
    public function getFamilyName($path)
    {
        try {
            $command = [
                config('mediatheque.services.otfinfo.bin'),
                '-a',
                escapeshellarg($path),
                '2>&1'
            ];

            $output = [];
            $return = 0;
            exec(implode(' ', $command), $output, $return);

            if ($return !== 0) {
                throw new Exception('otfinfo failed return code :'.$return.' '.implode(PHP_EOL, $output));
            }

            return trim(implode(' ', $output));
        } catch (Exception $e) {
            if (config('mediatheque.debug')) {
                throw $e;
            } else {
                Log::error($e);
            }
            return null;
        }
    }
}
