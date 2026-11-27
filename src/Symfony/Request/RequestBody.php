<?php

namespace AtpCore\Symfony\Request;

use Symfony\Component\HttpFoundation\Request;

final class RequestBody
{
    /**
     * Get request-body as array (JSON, form-POST or url-encoded body)
     */
    public static function fromRequest(Request $request): array
    {
        if (str_contains((string) $request->headers->get('Content-Type'), 'json')) {
            $data = json_decode($request->getContent(), true);
            return is_array($data) ? $data : [];
        }

        if ($request->isMethod(Request::METHOD_POST)) {
            return $request->request->all();
        }

        parse_str($request->getContent(), $data);
        return $data;
    }
}
