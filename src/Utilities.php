<?php declare(strict_types=1);

namespace Alantiller;

use Alantiller\Utilities\Helpers\ArrayHelper;
use Alantiller\Utilities\Helpers\DateTimeHelper;
use Alantiller\Utilities\Helpers\EncodingHelper;
use Alantiller\Utilities\Helpers\HttpHelper;
use Alantiller\Utilities\Helpers\StringHelper;
use Alantiller\Utilities\Helpers\UuidHelper;
use Alantiller\Utilities\Helpers\ValidationHelper;

/** Access independent utility helpers through one configured entry point. */
final class Utilities
{
    public readonly DateTimeHelper $dates;
    public readonly StringHelper $strings;
    public readonly UuidHelper $uuids;
    public readonly EncodingHelper $encoding;
    public readonly ValidationHelper $validation;
    public readonly ArrayHelper $arrays;
    public readonly HttpHelper $http;

    /**
     * Configure date defaults and create stateless helpers without side effects.
     * Helpers may also be instantiated independently. No global timezone, logger,
     * HTTP request, database, or mutable validation state is owned by this class.
     * @param string $timezone Default date timezone; UTC unless explicitly configured.
     * @param string $date_format Default PHP date output format.
     * @throws \InvalidArgumentException If the configured timezone is invalid.
     */
    public function __construct(string $timezone = 'UTC', string $date_format = 'Y-m-d H:i:s')
    {
        $this->dates = new DateTimeHelper($timezone, $date_format);
        $this->strings = new StringHelper();
        $this->uuids = new UuidHelper();
        $this->encoding = new EncodingHelper();
        $this->validation = new ValidationHelper();
        $this->arrays = new ArrayHelper();
        $this->http = new HttpHelper();
    }
}
