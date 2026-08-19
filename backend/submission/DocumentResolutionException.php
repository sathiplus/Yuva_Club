<?php
declare(strict_types=1);

namespace YuvaClub\Submission;

use RuntimeException;

final class DocumentResolutionException extends RuntimeException
{
    public function __construct(public readonly string $safeCode)
    {
        parent::__construct('The uploaded document could not be safely analyzed.');
    }
}
