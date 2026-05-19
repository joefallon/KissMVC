<?php
declare(strict_types=1);

namespace KissMVC;

function headers_sent(?string &$file = null, ?int &$line = null): bool
{
    return true;
}
