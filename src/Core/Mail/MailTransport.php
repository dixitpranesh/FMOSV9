<?php

declare(strict_types=1);

namespace Fmos\Core\Mail;

interface MailTransport
{
    /**
     * @param list<string> $to
     * @param array{from?:string,from_name?:string,reply_to?:?string} $meta
     * @return array{ok:bool,path?:string,error?:string,error_type?:string,driver?:string,duration_ms?:int,fallback?:string}
     */
    public function send(array $to, string $subject, string $htmlBody, string $textBody = '', array $meta = []): array;
}
