<?php declare(strict_types=1);

namespace KissMVC;

final class ServerRequestParametersProvider implements RequestParametersProviderInterface
{
    public function getRequestParameters(): ?array
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = $scriptName !== '' ? dirname($scriptName) : '';

        if($scriptDir !== '' && $scriptDir !== '/')
        {
            $request = str_replace($scriptDir, '', $requestUri);
        }
        else
        {
            $request = $requestUri;
        }

        $request = trim((string) $request, '/');

        if($request === '')
        {
            return null;
        }

        return $this->urlSegments($request);
    }

    /**
     * Split the request path into segments and strip query strings.
     *
     * @return array<int, string>
     */
    private function urlSegments(string $request): array
    {
        $requestParams = explode('/', $request);
        $params = [];

        foreach($requestParams as $param)
        {
            if($param === '')
            {
                continue;
            }

            $arr = explode('?', $param, 2);

            if(isset($arr[0]) && $arr[0] !== '')
            {
                $params[] = $arr[0];
            }
        }

        return $params;
    }
}
