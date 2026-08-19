<?php
declare(strict_types=1);

namespace YuvaClub\Delivery;

interface DeliveryCoachingProvider
{
    /** @return array{ok:bool,output?:array<string,mixed>,error_code?:string} */
    public function generate(string $prompt): array;
    public function modelName(): string;
}
